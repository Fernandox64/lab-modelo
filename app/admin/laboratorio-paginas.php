<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_content');
ensure_laboratory_page_items_table();

$catalog = laboratory_page_catalog();
$selectedSlug = laboratory_page_slug_normalize((string)($_GET['slug'] ?? 'projetos'));
$error = null;
$success = null;
$editingItem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $saveSlug = laboratory_page_slug_normalize((string)($_POST['page_slug'] ?? $selectedSlug));
        $selectedSlug = $saveSlug;
        try {
            if ($action === 'save_page') {
                laboratory_page_save($saveSlug, $_POST);
                admin_audit_log('laboratorio_page_update', ['slug' => $saveSlug], 'site_settings');
                $success = 'Dados da pagina atualizados com sucesso.';
            } elseif ($action === 'save_item') {
                $id = (int)($_POST['id'] ?? 0);
                $title = trim((string)($_POST['title'] ?? ''));
                $summary = trim((string)($_POST['summary'] ?? ''));
                $category = trim((string)($_POST['category'] ?? ''));
                $contentHtml = sanitize_rich_text((string)($_POST['content_html'] ?? ''));
                $imageUrl = trim((string)($_POST['image_url'] ?? ''));
                $externalUrl = trim((string)($_POST['external_url'] ?? ''));
                $publishedAt = trim((string)($_POST['published_at'] ?? ''));
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                $slugInput = trim((string)($_POST['slug'] ?? ''));

                if ($title === '' || $summary === '' || $contentHtml === '') {
                    $error = 'Titulo, resumo e conteudo sao obrigatorios para o item.';
                } else {
                    $baseSlug = $slugInput !== '' ? $slugInput : $title;
                    $slug = laboratory_page_item_unique_slug($saveSlug, $baseSlug, $id > 0 ? $id : null);
                    $publishedAtValue = $publishedAt !== '' ? str_replace('T', ' ', $publishedAt) . ':00' : date('Y-m-d H:i:s');
                    $category = $category !== '' ? $category : 'Laboratorio';

                    if ($id > 0) {
                        $stmt = db()->prepare(
                            'UPDATE laboratory_page_items
                             SET slug = :slug, title = :title, summary = :summary, category = :category,
                                 content_html = :content_html, image_url = :image_url, external_url = :external_url,
                                 is_active = :is_active, sort_order = :sort_order, published_at = :published_at
                             WHERE id = :id AND page_slug = :page_slug'
                        );
                        $stmt->execute([
                            ':slug' => $slug,
                            ':title' => $title,
                            ':summary' => $summary,
                            ':category' => $category,
                            ':content_html' => $contentHtml,
                            ':image_url' => $imageUrl,
                            ':external_url' => $externalUrl,
                            ':is_active' => $isActive,
                            ':sort_order' => $sortOrder,
                            ':published_at' => $publishedAtValue,
                            ':id' => $id,
                            ':page_slug' => $saveSlug,
                        ]);
                        admin_audit_log('laboratorio_item_update', ['slug' => $saveSlug, 'id' => $id], 'laboratory_page_items');
                        $success = 'Item atualizado com sucesso.';
                    } else {
                        $stmt = db()->prepare(
                            'INSERT INTO laboratory_page_items
                             (page_slug, slug, title, summary, category, content_html, image_url, external_url, is_active, sort_order, published_at)
                             VALUES
                             (:page_slug, :slug, :title, :summary, :category, :content_html, :image_url, :external_url, :is_active, :sort_order, :published_at)'
                        );
                        $stmt->execute([
                            ':page_slug' => $saveSlug,
                            ':slug' => $slug,
                            ':title' => $title,
                            ':summary' => $summary,
                            ':category' => $category,
                            ':content_html' => $contentHtml,
                            ':image_url' => $imageUrl,
                            ':external_url' => $externalUrl,
                            ':is_active' => $isActive,
                            ':sort_order' => $sortOrder,
                            ':published_at' => $publishedAtValue,
                        ]);
                        $newId = (int)db()->lastInsertId();
                        admin_audit_log('laboratorio_item_create', ['slug' => $saveSlug, 'id' => $newId], 'laboratory_page_items');
                        $success = 'Item adicionado com sucesso.';
                    }
                }
            } elseif ($action === 'delete_item') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = db()->prepare('DELETE FROM laboratory_page_items WHERE id = :id AND page_slug = :page_slug');
                    $stmt->execute([':id' => $id, ':page_slug' => $saveSlug]);
                    admin_audit_log('laboratorio_item_delete', ['slug' => $saveSlug, 'id' => $id], 'laboratory_page_items');
                    $success = 'Item removido com sucesso.';
                }
            }
        } catch (Throwable $e) {
            $error = 'Nao foi possivel processar a requisicao.';
            error_log('Admin laboratorio-paginas error: ' . $e->getMessage());
        }
    }
}

$content = laboratory_page_get($selectedSlug);

$editId = (int)($_GET['edit_item'] ?? 0);
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM laboratory_page_items WHERE id = :id AND page_slug = :page_slug');
    $stmt->execute([':id' => $editId, ':page_slug' => $selectedSlug]);
    $editingItem = $stmt->fetch() ?: null;
}

$listStmt = db()->prepare('SELECT id, slug, title, category, is_active, sort_order, published_at FROM laboratory_page_items WHERE page_slug = :page_slug ORDER BY sort_order ASC, published_at DESC, id DESC');
$listStmt->execute([':page_slug' => $selectedSlug]);
$items = $listStmt->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Paginas do Laboratorio</title>
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
    <?php render_admin_sidebar('lab_pages'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Paginas do Laboratorio</h3>
                    <a class="btn btn-dark btn-sm" href="<?= e((string)$content['public_url']) ?>" target="_blank" rel="noopener">Ver pagina publica</a>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Selecionar pagina</h3></div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($catalog as $slug => $meta): ?>
                                <a class="btn btn-sm <?= $slug === $selectedSlug ? 'btn-primary' : 'btn-outline-primary' ?>" href="/admin/laboratorio-paginas.php?slug=<?= e($slug) ?>">
                                    <?= e((string)$meta['label']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Configuracao da pagina <?= e((string)$content['label']) ?></h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="save_page">
                            <input type="hidden" name="page_slug" value="<?= e((string)$content['slug']) ?>">

                            <div class="mb-3">
                                <label class="form-label">Titulo</label>
                                <input class="form-control" name="title" required value="<?= e((string)$content['title']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Resumo</label>
                                <textarea class="form-control" name="summary" rows="3" required><?= e((string)$content['summary']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Conteudo introdutorio</label>
                                <textarea class="form-control editor" name="content_html" rows="6"><?= e((string)$content['content_html']) ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Salvar configuracao</button>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title"><?= $editingItem ? 'Editar item' : 'Adicionar item' ?></h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="save_item">
                            <input type="hidden" name="page_slug" value="<?= e((string)$content['slug']) ?>">
                            <input type="hidden" name="id" value="<?= e((string)($editingItem['id'] ?? 0)) ?>">

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Titulo</label>
                                    <input class="form-control" name="title" required value="<?= e((string)($editingItem['title'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Categoria</label>
                                    <input class="form-control" name="category" value="<?= e((string)($editingItem['category'] ?? 'Laboratorio')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Slug (opcional)</label>
                                    <input class="form-control" name="slug" value="<?= e((string)($editingItem['slug'] ?? '')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ordem</label>
                                    <input class="form-control" type="number" name="sort_order" value="<?= e((string)($editingItem['sort_order'] ?? 0)) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Data de publicacao</label>
                                    <input class="form-control" type="datetime-local" name="published_at" value="<?= e(isset($editingItem['published_at']) ? str_replace(' ', 'T', substr((string)$editingItem['published_at'], 0, 16)) : '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Resumo</label>
                                    <textarea class="form-control" name="summary" rows="2" required><?= e((string)($editingItem['summary'] ?? '')) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Conteudo</label>
                                    <textarea class="form-control editor" name="content_html" rows="8" required><?= e((string)($editingItem['content_html'] ?? '')) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Imagem (URL)</label>
                                    <input class="form-control" name="image_url" value="<?= e((string)($editingItem['image_url'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Link externo (opcional)</label>
                                    <input class="form-control" name="external_url" value="<?= e((string)($editingItem['external_url'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <?php $active = !isset($editingItem['is_active']) || (int)$editingItem['is_active'] === 1; ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="isActiveItem" value="1"<?= $active ? ' checked' : '' ?>>
                                        <label class="form-check-label" for="isActiveItem">Item ativo na pagina publica</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary"><?= $editingItem ? 'Salvar item' : 'Adicionar item' ?></button>
                                <?php if ($editingItem): ?><a class="btn btn-outline-secondary" href="/admin/laboratorio-paginas.php?slug=<?= e($selectedSlug) ?>">Cancelar</a><?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Itens cadastrados (<?= e((string)count($items)) ?>)</h3></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titulo</th>
                                    <th>Categoria</th>
                                    <th>Status</th>
                                    <th>Publicacao</th>
                                    <th>Ordem</th>
                                    <th class="text-end">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $row): ?>
                                    <tr>
                                        <td><?= e((string)$row['id']) ?></td>
                                        <td><?= e((string)$row['title']) ?></td>
                                        <td><?= e((string)$row['category']) ?></td>
                                        <td><?= (int)$row['is_active'] === 1 ? 'Ativo' : 'Oculto' ?></td>
                                        <td><?= e((string)$row['published_at']) ?></td>
                                        <td><?= e((string)$row['sort_order']) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-outline-primary btn-sm" href="/admin/laboratorio-paginas.php?slug=<?= e($selectedSlug) ?>&edit_item=<?= e((string)$row['id']) ?>">Editar</a>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete_item">
                                                <input type="hidden" name="page_slug" value="<?= e($selectedSlug) ?>">
                                                <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Excluir este item?');">Excluir</button>
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
  selector: '.editor',
  height: 260,
  menubar: false,
  plugins: 'lists link table code',
  toolbar: 'undo redo | bold italic | bullist numlist | link | code',
  branding: false
});
</script>
</body>
</html>
