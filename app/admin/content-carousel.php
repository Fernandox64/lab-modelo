<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_content');
ensure_content_carousel_images_table();

function content_carousel_ini_size_to_bytes(string $value): int {
    $v = trim($value);
    if ($v === '') {
        return 0;
    }
    $unit = strtolower(substr($v, -1));
    $num = (float)$v;
    if ($unit === 'g') {
        return (int)($num * 1024 * 1024 * 1024);
    }
    if ($unit === 'm') {
        return (int)($num * 1024 * 1024);
    }
    if ($unit === 'k') {
        return (int)($num * 1024);
    }
    return (int)$num;
}

function content_carousel_store_uploaded_image(array $files, int $index): string {
    $errorCode = (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload da imagem do carrossel.');
    }

    $tmp = (string)($files['tmp_name'][$index] ?? '');
    $size = (int)($files['size'][$index] ?? 0);
    if ($tmp === '' || !is_uploaded_file($tmp) || $size <= 0 || $size > 8 * 1024 * 1024) {
        throw new RuntimeException('Arquivo invalido. Use imagens ate 8MB.');
    }

    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false) {
        throw new RuntimeException('O arquivo enviado nao e uma imagem valida.');
    }

    $mime = strtolower((string)($imageInfo['mime'] ?? ''));
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($extMap[$mime])) {
        throw new RuntimeException('Formato nao suportado. Use JPG, PNG, GIF ou WEBP.');
    }

    $year = date('Y');
    $month = date('m');
    $relativeDir = "/uploads/content-carousel/{$year}/{$month}";
    $absoluteDir = __DIR__ . '/../uploads/content-carousel/' . $year . '/' . $month;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Nao foi possivel criar a pasta de upload do carrossel.');
    }

    $filename = 'carousel_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $extMap[$mime];
    $destination = $absoluteDir . '/' . $filename;
    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Nao foi possivel salvar a imagem enviada.');
    }
    return $relativeDir . '/' . $filename;
}

function content_carousel_meta(string $type): array {
    if ($type === 'editais') {
        return [
            'type' => 'editais',
            'table' => 'edital_items',
            'label' => 'Editais',
            'public_url' => '/noticias/editais.php',
        ];
    }
    return [
        'type' => 'noticias',
        'table' => 'news_items',
        'label' => 'Noticias',
        'public_url' => '/noticias/index.php',
    ];
}

$allowedTypes = ['noticias', 'editais'];
$typeInput = (string)($_GET['type'] ?? $_POST['type'] ?? 'noticias');
$type = in_array($typeInput, $allowedTypes, true) ? $typeInput : 'noticias';
$meta = content_carousel_meta($type);

$error = null;
$success = null;

$itemsStmt = db()->query("SELECT id, slug, title, published_at FROM {$meta['table']} ORDER BY published_at DESC, id DESC LIMIT 300");
$items = $itemsStmt->fetchAll();
$itemIds = array_map(static fn(array $r): int => (int)$r['id'], $items);
$selectedItemId = (int)($_GET['item_id'] ?? $_POST['item_id'] ?? ($itemIds[0] ?? 0));
if (!in_array($selectedItemId, $itemIds, true)) {
    $selectedItemId = (int)($itemIds[0] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        try {
            $imageUrls = (array)($_POST['image_url'] ?? []);
            $captions = (array)($_POST['caption'] ?? []);
            $sortOrders = (array)($_POST['sort_order'] ?? []);
            $uploads = (isset($_FILES['upload']) && is_array($_FILES['upload'])) ? $_FILES['upload'] : null;
            $slides = [];
            foreach ($imageUrls as $idx => $url) {
                $uploadedUrl = '';
                if ($uploads !== null) {
                    $uploadedUrl = content_carousel_store_uploaded_image($uploads, (int)$idx);
                }
                $finalUrl = $uploadedUrl !== '' ? $uploadedUrl : (string)$url;
                $slides[] = [
                    'image_url' => $finalUrl,
                    'caption' => (string)($captions[$idx] ?? ''),
                    'sort_order' => (int)($sortOrders[$idx] ?? ($idx + 1)),
                ];
            }
            content_carousel_images_replace($type, $selectedItemId, $slides);
            admin_audit_log('content_carousel_update', ['type' => $type, 'content_id' => $selectedItemId, 'slides' => count($slides)], 'content_carousel_images');
            $success = 'Carrossel salvo com sucesso.';
        } catch (Throwable $e) {
            $error = 'Nao foi possivel salvar o carrossel.';
            error_log('Admin content-carousel error: ' . $e->getMessage());
        }
    }
}

$currentSlides = $selectedItemId > 0 ? content_carousel_images_get($type, $selectedItemId) : [];
if (empty($currentSlides)) {
    $currentSlides = [['image_url' => '', 'caption' => '']];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Carrossel <?= e($meta['label']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">Menu</a></li>
                <li class="nav-item d-none d-md-block"><a href="/admin/dashboard.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item d-none d-md-block"><a href="/" class="nav-link" target="_blank" rel="noopener">Ir para o site</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <form method="post" action="/admin/logout.php" class="m-0">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Sair</button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>
    <?php render_admin_sidebar('content_carousel'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Carrossel de <?= e($meta['label']) ?></h3>
                    <a class="btn btn-dark btn-sm" href="<?= e((string)$meta['public_url']) ?>" target="_blank" rel="noopener">Ver pagina publica</a>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Selecionar tipo e item</h3></div>
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="type" onchange="this.form.submit()">
                                    <option value="noticias"<?= $type === 'noticias' ? ' selected' : '' ?>>Noticias</option>
                                    <option value="editais"<?= $type === 'editais' ? ' selected' : '' ?>>Editais</option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Post</label>
                                <select class="form-select" name="item_id" onchange="this.form.submit()">
                                    <?php foreach ($items as $it): ?>
                                        <option value="<?= e((string)$it['id']) ?>"<?= (int)$it['id'] === $selectedItemId ? ' selected' : '' ?>>
                                            <?= e((string)$it['title']) ?> (<?= e((string)$it['slug']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selectedItemId > 0): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">Imagens do carrossel</h3>
                            <a class="btn btn-outline-primary btn-sm" href="/admin/content.php?type=<?= e($type) ?>&edit=<?= e((string)$selectedItemId) ?>">Editar texto do post</a>
                        </div>
                        <div class="card-body">
                            <form method="post" id="carouselForm" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="type" value="<?= e($type) ?>">
                                <input type="hidden" name="item_id" value="<?= e((string)$selectedItemId) ?>">
                                <div id="slidesWrap">
                                    <?php foreach ($currentSlides as $idx => $slide): ?>
                                        <div class="row g-2 align-items-end mb-2 slide-row">
                                            <div class="col-md-4">
                                                <label class="form-label">Imagem (URL)</label>
                                                <input class="form-control" name="image_url[]" value="<?= e((string)($slide['image_url'] ?? '')) ?>" placeholder="/assets/images/...">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Upload (opcional)</label>
                                                <input type="file" class="form-control" name="upload[]" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Legenda (opcional)</label>
                                                <input class="form-control" name="caption[]" value="<?= e((string)($slide['caption'] ?? '')) ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">Ordem</label>
                                                <input type="number" class="form-control" name="sort_order[]" value="<?= e((string)($idx + 1)) ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-outline-danger w-100 remove-slide">X</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button type="button" id="addSlide" class="btn btn-outline-secondary">Adicionar imagem</button>
                                    <button type="submit" class="btn btn-primary">Salvar carrossel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>
<script>
(() => {
  const wrap = document.getElementById('slidesWrap');
  const add = document.getElementById('addSlide');
  if (!wrap || !add) return;
  add.addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-end mb-2 slide-row';
    row.innerHTML = `
      <div class="col-md-5">
        <label class="form-label">Imagem (URL)</label>
        <input class="form-control" name="image_url[]" placeholder="/assets/images/...">
      </div>
      <div class="col-md-3">
        <label class="form-label">Upload (opcional)</label>
        <input type="file" class="form-control" name="upload[]" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
      </div>
      <div class="col-md-2">
        <label class="form-label">Legenda (opcional)</label>
        <input class="form-control" name="caption[]">
      </div>
      <div class="col-md-1">
        <label class="form-label">Ordem</label>
        <input type="number" class="form-control" name="sort_order[]" value="${wrap.querySelectorAll('.slide-row').length + 1}">
      </div>
      <div class="col-md-1">
        <button type="button" class="btn btn-outline-danger w-100 remove-slide">X</button>
      </div>`;
    wrap.appendChild(row);
  });
  wrap.addEventListener('click', (ev) => {
    const btn = ev.target.closest('.remove-slide');
    if (!btn) return;
    const rows = wrap.querySelectorAll('.slide-row');
    if (rows.length <= 1) return;
    btn.closest('.slide-row')?.remove();
  });
})();
</script>
</body>
</html>
