<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_content');

function admin_content_meta(string $type): array {
    if ($type === 'defesas') {
        return [
            'type' => 'defesas',
            'table' => 'defesa_items',
            'title' => 'Defesas',
            'default_category' => 'Defesas',
            'badge_class' => 'text-bg-info',
            'public_url' => '/noticias/defesas.php',
        ];
    }
    if ($type === 'estagios') {
        return [
            'type' => 'estagios',
            'table' => 'job_items',
            'title' => 'Estagios e Empregos',
            'default_category' => 'Carreiras',
            'badge_class' => 'text-bg-dark',
            'public_url' => '/noticias/estagios-empregos.php',
        ];
    }
    if ($type === 'editais') {
        return [
            'type' => 'editais',
            'table' => 'edital_items',
            'title' => 'Editais',
            'default_category' => 'Editais',
            'badge_class' => 'text-bg-secondary',
            'public_url' => '/noticias/editais.php',
        ];
    }
    return [
        'type' => 'noticias',
        'table' => 'news_items',
        'title' => 'Noticias',
        'default_category' => 'Departamento',
        'badge_class' => 'text-bg-primary',
        'public_url' => '/noticias/index.php',
    ];
}

function admin_slugify(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? substr($text, 0, 140) : 'item-' . bin2hex(random_bytes(4));
}

function admin_unique_slug(PDO $pdo, string $table, string $baseSlug, ?int $ignoreId = null): string {
    $slug = $baseSlug;
    $i = 1;
    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = :slug";
        $params = [':slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $i++;
        $slug = $baseSlug . '-' . $i;
    }
}

$allowedTypes = ['noticias', 'editais', 'defesas', 'estagios'];
$typeInput = (string)($_GET['type'] ?? 'noticias');
$type = in_array($typeInput, $allowedTypes, true) ? $typeInput : 'noticias';
$sidebarActive = 'content_' . $type;
$meta = admin_content_meta($type);
$pdo = db();

$error = null;
$success = null;
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare("DELETE FROM {$meta['table']} WHERE id = :id");
            $stmt->execute([':id' => $id]);
            admin_audit_log('content_delete', ['type' => $type, 'id' => $id], $meta['table']);
            $success = 'Registro removido com sucesso.';
        }

        if ($action === 'save') {
            $title = trim((string)($_POST['title'] ?? ''));
            $summary = trim((string)($_POST['summary'] ?? ''));
            $category = trim((string)($_POST['category'] ?? ''));
            $content = sanitize_rich_text(trim((string)($_POST['content'] ?? '')));
            $image = trim((string)($_POST['image'] ?? ''));
            $publishedAtInput = trim((string)($_POST['published_at'] ?? ''));
            $slugInput = trim((string)($_POST['slug'] ?? ''));

            if ($title === '' || $summary === '' || $content === '') {
                $error = 'Titulo, resumo e conteudo sao obrigatorios.';
            } else {
                $baseSlug = $slugInput !== '' ? admin_slugify($slugInput) : admin_slugify($title);
                $slug = admin_unique_slug($pdo, $meta['table'], $baseSlug, $id > 0 ? $id : null);
                $publishedAt = $publishedAtInput !== '' ? str_replace('T', ' ', $publishedAtInput) . ':00' : date('Y-m-d H:i:s');
                $category = $category !== '' ? $category : $meta['default_category'];

                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        "UPDATE {$meta['table']}
                         SET slug = :slug, title = :title, summary = :summary, category = :category, content = :content, image = :image, published_at = :published_at
                         WHERE id = :id"
                    );
                    $stmt->execute([
                        ':slug' => $slug,
                        ':title' => $title,
                        ':summary' => $summary,
                        ':category' => $category,
                        ':content' => $content,
                        ':image' => $image,
                        ':published_at' => $publishedAt,
                        ':id' => $id,
                    ]);
                    admin_audit_log('content_update', ['type' => $type, 'id' => $id, 'slug' => $slug, 'title' => $title], $meta['table']);
                    $success = 'Registro atualizado com sucesso.';
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO {$meta['table']} (slug, title, summary, category, content, image, published_at)
                         VALUES (:slug, :title, :summary, :category, :content, :image, :published_at)"
                    );
                    $stmt->execute([
                        ':slug' => $slug,
                        ':title' => $title,
                        ':summary' => $summary,
                        ':category' => $category,
                        ':content' => $content,
                        ':image' => $image,
                        ':published_at' => $publishedAt,
                    ]);
                    $newId = (int)$pdo->lastInsertId();
                    admin_audit_log('content_create', ['type' => $type, 'id' => $newId, 'slug' => $slug, 'title' => $title], $meta['table']);
                    $success = 'Registro criado com sucesso.';
                }
            }
        }
    }
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM {$meta['table']} WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $editing = $stmt->fetch();
}

$itemsStmt = $pdo->query("SELECT id, slug, title, summary, category, image, published_at FROM {$meta['table']} ORDER BY published_at DESC, id DESC LIMIT 200");
$items = $itemsStmt->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - <?= e($meta['title']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7.9.1/tinymce.min.js" referrerpolicy="origin"></script>
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

    <?php render_admin_sidebar($sidebarActive); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Gerenciar <?= e($meta['title']) ?></h3>
                    <a class="btn btn-dark btn-sm" href="<?= e($meta['public_url']) ?>" target="_blank" rel="noopener">Ver pagina publica</a>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title"><?= $editing ? 'Editar registro' : 'Novo registro' ?></h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '0')) ?>">

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Titulo</label>
                                    <input class="form-control" name="title" required value="<?= e((string)($editing['title'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Categoria</label>
                                    <input class="form-control" name="category" value="<?= e((string)($editing['category'] ?? $meta['default_category'])) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Slug (opcional)</label>
                                    <input class="form-control" name="slug" value="<?= e((string)($editing['slug'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Data de publicacao</label>
                                    <input class="form-control" type="datetime-local" name="published_at" value="<?= e(isset($editing['published_at']) ? str_replace(' ', 'T', substr((string)$editing['published_at'], 0, 16)) : '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Resumo</label>
                                    <textarea class="form-control" rows="2" name="summary" required><?= e((string)($editing['summary'] ?? '')) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Conteudo</label>
                                    <textarea id="content-editor" class="form-control" rows="12" name="content" required><?= e((string)($editing['content'] ?? '')) ?></textarea>
                                    <small class="text-muted">Editor TinyMCE habilitado com imagens, tabelas e links.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Imagem (URL ou /assets/...)</label>
                                    <input class="form-control" name="image" placeholder="/assets/cards/noticia-default.svg" value="<?= e((string)($editing['image'] ?? '')) ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-primary" type="submit"><?= $editing ? 'Salvar alteracoes' : 'Criar registro' ?></button>
                                <?php if ($editing): ?><a class="btn btn-outline-secondary" href="/admin/content.php?type=<?= e($type) ?>">Cancelar</a><?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Ultimos registros (<?= e((string)count($items)) ?>)</h3></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titulo</th>
                                    <th>Categoria</th>
                                    <th>Publicacao</th>
                                    <th>Slug</th>
                                    <th class="text-end">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $row): ?>
                                    <tr>
                                        <td><?= e((string)$row['id']) ?></td>
                                        <td><?= e((string)$row['title']) ?></td>
                                        <td><span class="badge <?= e($meta['badge_class']) ?>"><?= e((string)$row['category']) ?></span></td>
                                        <td><?= e((string)$row['published_at']) ?></td>
                                        <td><code><?= e((string)$row['slug']) ?></code></td>
                                        <td class="text-end">
                                            <a class="btn btn-outline-primary btn-sm" href="/admin/content.php?type=<?= e($type) ?>&edit=<?= e((string)$row['id']) ?>">Editar</a>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Excluir este registro?');">Excluir</button>
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
tinymce.init({
  selector: '#content-editor',
  height: 420,
  menubar: 'file edit view insert format tools table help',
  plugins: 'advlist autolink lists link image table code fullscreen preview autoresize wordcount anchor charmap visualblocks paste',
  toolbar: 'undo redo | blocks fontsize | bold italic underline removeformat | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | blockquote hr | pastetext code preview fullscreen',
  branding: false,
  browser_spellcheck: true,
  relative_urls: false,
  remove_script_host: false,
  convert_urls: false,
  paste_as_text: false,
  paste_block_drop: true,
  paste_data_images: false,
  paste_webkit_styles: 'none',
  paste_merge_formats: true,
  paste_preprocess: function (plugin, args) {
    // Limpa marcas comuns do Word/Docs mantendo estrutura semÃƒÆ’Ã‚Â¢ntica.
    args.content = args.content
      .replace(/<!--[\s\S]*?-->/g, '')
      .replace(/\sclass=("|\')[^"\']*("|\')/gi, '')
      .replace(/\sstyle=("|\')[^"\']*("|\')/gi, '')
      .replace(/\sdata-[a-z0-9-]+=("|\')[^"\']*("|\')/gi, '')
      .replace(/\sid=("|\')[^"\']*("|\')/gi, '')
      .replace(/<(\/?)(span|font|o:p)[^>]*>/gi, '<$1span>');
  },
  images_upload_url: '/admin/upload-image.php',
  images_reuse_filename: false,
  file_picker_types: 'image',
  image_title: true,
  automatic_uploads: true,
  content_style: 'body { font-family: Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.6; }',
  valid_elements: 'p,br,strong/b,em/i,u,ul,ol,li,a[href|target|rel],h2,h3,h4,blockquote,img[src|alt|title|width|height],table,thead,tbody,tr,td,th,hr'
});
</script>
</body>
</html>



