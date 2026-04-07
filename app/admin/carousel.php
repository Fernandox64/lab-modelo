<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_carousel');

function carousel_store_uploaded_image(array $file): string {
    $errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload da imagem.');
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);
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

    $relativeDir = '/assets/images/carousel/uploads';
    $absoluteDir = __DIR__ . '/../assets/images/carousel/uploads';
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Nao foi possivel criar a pasta de upload do carrossel.');
    }

    $filename = 'slide_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extMap[$mime];
    $destination = $absoluteDir . '/' . $filename;
    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Nao foi possivel salvar a imagem enviada.');
    }

    return $relativeDir . '/' . $filename;
}

function carousel_find_slide_index(array $slides, string $slideId): int {
    foreach ($slides as $idx => $slide) {
        if ((string)($slide['id'] ?? '') === $slideId) {
            return (int)$idx;
        }
    }
    return -1;
}

$error = null;
$notice = trim((string)($_GET['ok'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        try {
            $action = (string)($_POST['action'] ?? '');
            $slides = hero_carousel_get();

            if ($action === 'save') {
                $slideId = trim((string)($_POST['slide_id'] ?? ''));
                $isEditing = $slideId !== '';

                $badge = trim((string)($_POST['badge'] ?? ''));
                $title = trim((string)($_POST['title'] ?? ''));
                $text = trim((string)($_POST['text'] ?? ''));
                $image = trim((string)($_POST['image'] ?? ''));

                if (isset($_FILES['upload']) && is_array($_FILES['upload'])) {
                    $uploadError = (int)($_FILES['upload']['error'] ?? UPLOAD_ERR_NO_FILE);
                    if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                        $image = carousel_store_uploaded_image($_FILES['upload']);
                    }
                }

                if ($image === '' && $badge === '' && $title === '' && $text === '') {
                    throw new RuntimeException('Preencha ao menos um campo para salvar o slide.');
                }

                if ($isEditing) {
                    $idx = carousel_find_slide_index($slides, $slideId);
                    if ($idx < 0) {
                        throw new RuntimeException('Slide nao encontrado para edicao.');
                    }
                    $current = $slides[$idx];
                    $slides[$idx] = [
                        'id' => (string)($current['id'] ?? $slideId),
                        'image' => $image,
                        'badge' => $badge,
                        'title' => $title,
                        'text' => $text,
                    ];
                    hero_carousel_save($slides);
                    admin_audit_log('carousel_update_slide', ['slide_id' => $slideId, 'title' => $title], 'site_settings');
                    redirect('/admin/carousel.php?ok=updated');
                }

                $newSlide = [
                    'id' => 'slide-' . bin2hex(random_bytes(6)),
                    'image' => $image,
                    'badge' => $badge,
                    'title' => $title,
                    'text' => $text,
                ];
                $slides[] = $newSlide;
                hero_carousel_save($slides);
                admin_audit_log('carousel_add_slide', ['slide_id' => $newSlide['id'], 'title' => $title], 'site_settings');
                redirect('/admin/carousel.php?ok=added');
            }

            if ($action === 'delete') {
                $slideId = trim((string)($_POST['slide_id'] ?? ''));
                if ($slideId === '') {
                    throw new RuntimeException('Slide invalido para exclusao.');
                }

                if (count($slides) <= 1) {
                    throw new RuntimeException('O carrossel precisa manter ao menos 1 slide.');
                }

                $idx = carousel_find_slide_index($slides, $slideId);
                if ($idx < 0) {
                    throw new RuntimeException('Slide nao encontrado para exclusao.');
                }

                array_splice($slides, $idx, 1);
                hero_carousel_save($slides);
                admin_audit_log('carousel_delete_slide', ['slide_id' => $slideId], 'site_settings');
                redirect('/admin/carousel.php?ok=deleted');
            }

            throw new RuntimeException('Acao invalida.');
        } catch (Throwable $e) {
            $error = $e->getMessage() !== '' ? $e->getMessage() : 'Falha ao salvar configuracao do carrossel.';
            error_log('Admin carousel error: ' . $e->getMessage());
        }
    }
}

$slides = hero_carousel_get();
$editId = trim((string)($_GET['edit'] ?? ''));
$editingSlide = null;
if ($editId !== '') {
    $idx = carousel_find_slide_index($slides, $editId);
    if ($idx >= 0) {
        $editingSlide = $slides[$idx];
    }
}

$form = [
    'slide_id' => (string)($editingSlide['id'] ?? ''),
    'image' => (string)($editingSlide['image'] ?? ''),
    'badge' => (string)($editingSlide['badge'] ?? 'Subtitulo de exemplo'),
    'title' => (string)($editingSlide['title'] ?? 'Titulo de exemplo do slide'),
    'text' => (string)($editingSlide['text'] ?? ''),
];

$success = null;
if ($notice === 'added') {
    $success = 'Slide adicionado com sucesso.';
} elseif ($notice === 'updated') {
    $success = 'Slide salvo com sucesso.';
} elseif ($notice === 'deleted') {
    $success = 'Slide removido com sucesso.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Carrossel da Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
    <style>
        .carousel-upload-preview {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: .5rem;
            border: 1px solid #ced4da;
            background: #f8f9fa;
        }
        .thumb-mini {
            width: 84px;
            height: 46px;
            object-fit: cover;
            border-radius: .35rem;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">Menu</a></li>
                <li class="nav-item d-none d-md-block"><a href="/admin/dashboard.php" class="nav-link">Dashboard</a></li>
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
    <?php render_admin_sidebar('carousel'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Editar Carrossel da Home</h3>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge text-bg-secondary">Total de slides: <?= e((string)count($slides)) ?></span>
                        <a class="btn btn-dark btn-sm" href="/" target="_blank" rel="noopener">Ver Home</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title"><?= $form['slide_id'] !== '' ? 'Editar 1 slide' : 'Adicionar 1 slide' ?></h3></div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Use somente este formulario. Cada envio salva 1 slide por vez.</p>
                        <form method="post" enctype="multipart/form-data" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="slide_id" value="<?= e($form['slide_id']) ?>">

                            <div class="col-md-4">
                                <label class="form-label">Upload de imagem</label>
                                <input class="form-control js-slide-upload" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,image/*" name="upload" data-preview-id="slide-main-preview">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Ou caminho da imagem (opcional)</label>
                                <input class="form-control" name="image" value="<?= e($form['image']) ?>" placeholder="/assets/images/carousel/minha-imagem.jpg">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Subtitulo</label>
                                <input class="form-control" name="badge" value="<?= e($form['badge']) ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Titulo</label>
                                <input class="form-control" name="title" value="<?= e($form['title']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Texto</label>
                                <textarea class="form-control" rows="3" name="text"><?= e($form['text']) ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Preview</label>
                                <img id="slide-main-preview" class="carousel-upload-preview" src="<?= e($form['image'] !== '' ? $form['image'] : '/assets/images/carousel/lab-1.png') ?>" alt="Preview slide">
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit"><?= $form['slide_id'] !== '' ? 'Salvar slide' : 'Adicionar slide' ?></button>
                                <?php if ($form['slide_id'] !== ''): ?><a class="btn btn-outline-secondary" href="/admin/carousel.php">Cancelar edicao</a><?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Slides cadastrados</h3></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Imagem</th>
                                    <th>Titulo</th>
                                    <th>Subtitulo</th>
                                    <th class="text-end">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($slides as $i => $s): ?>
                                    <tr>
                                        <td><?= e((string)($i + 1)) ?></td>
                                        <td><img class="thumb-mini" src="<?= e((string)($s['image'] ?? '/assets/images/carousel/lab-1.png')) ?>" alt="Thumb"></td>
                                        <td><?= e((string)($s['title'] ?? '')) ?></td>
                                        <td><?= e((string)($s['badge'] ?? '')) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-outline-primary btn-sm" href="/admin/carousel.php?edit=<?= urlencode((string)($s['id'] ?? '')) ?>">Editar</a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Remover este slide?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="slide_id" value="<?= e((string)($s['id'] ?? '')) ?>">
                                                <button class="btn btn-outline-danger btn-sm" type="submit" <?= count($slides) <= 1 ? 'disabled' : '' ?>>Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>
<script>
    document.querySelectorAll('.js-slide-upload').forEach(function (input) {
        input.addEventListener('change', function () {
            var previewId = input.getAttribute('data-preview-id');
            var preview = document.getElementById(previewId);
            if (!preview || !input.files || !input.files[0]) {
                return;
            }
            var file = input.files[0];
            var url = URL.createObjectURL(file);
            preview.src = url;
            preview.onload = function () { URL.revokeObjectURL(url); };
        });
    });
</script>
</body>
</html>
