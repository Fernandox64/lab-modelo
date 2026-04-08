<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_pos');
ensure_ppgcc_tables();

$error = null;
$success = null;
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'import') {
                $result = ppgcc_import_subsite_pages();
                if (($result['ok'] ?? false) === true) {
                    $success = 'Subsite importado com sucesso. Paginas importadas: ' . (string)($result['imported'] ?? 0) . '.';
                } else {
                    $error = (string)($result['message'] ?? 'Falha na importacao do subsite.');
                }
            } elseif ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $title = trim((string)($_POST['title'] ?? ''));
                $slug = trim((string)($_POST['slug'] ?? ''));
                $summary = trim((string)($_POST['summary'] ?? ''));
                $content = (string)($_POST['content_html'] ?? '');
                $source = trim((string)($_POST['source_url'] ?? ''));
                $sort = (int)($_POST['sort_order'] ?? 0);
                $active = isset($_POST['is_active']) ? 1 : 0;
                if ($title === '') {
                    $error = 'Titulo da pagina e obrigatorio.';
                } else {
                    ppgcc_page_save([
                        'slug' => $slug !== '' ? $slug : $title,
                        'title' => $title,
                        'summary' => $summary,
                        'content_html' => $content,
                        'source_url' => $source,
                        'sort_order' => $sort,
                        'is_active' => $active,
                    ], $id > 0 ? $id : null);
                    $success = 'Pagina do subsite salva.';
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = db()->prepare('DELETE FROM ppgcc_pages WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    $success = 'Pagina removida.';
                }
            }
        } catch (Throwable $e) {
            $error = 'Falha ao processar requisicao do subsite da pos.';
            error_log('Admin pos-subsite error: ' . $e->getMessage());
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM ppgcc_pages WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $editing = $stmt->fetch() ?: null;
}

$pages = ppgcc_pages_list(false);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Subsite Pos</title>
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
    <?php render_admin_sidebar('pos_subsite'); ?>

    <main class="app-main">
        <div class="app-content-header"><div class="container-fluid"><h3 class="mb-0">Gerenciar Subsite da Pos</h3></div></div>
        <div class="app-content"><div class="container-fluid">
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">Importacao do site antigo da Pos</h3></div>
                <div class="card-body">
                    <p class="mb-2">Importa paginas institucionais de <code>https://www3.decom.ufop.br/pos/inicio/</code> para o subsite interno <code>/pos/inicio.php</code>.</p>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="import">
                        <button class="btn btn-dark" type="submit">Importar subsite agora</button>
                    </form>
                    <a class="btn btn-outline-primary ms-2" href="/pos/inicio.php" target="_blank" rel="noopener">Ver subsite publico</a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title"><?= $editing ? 'Editar pagina' : 'Nova pagina do subsite' ?></h3></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? 0)) ?>">
                        <div class="row g-3">
                            <div class="col-md-8"><label class="form-label">Titulo</label><input class="form-control" name="title" required value="<?= e((string)($editing['title'] ?? '')) ?>"></div>
                            <div class="col-md-4"><label class="form-label">Slug</label><input class="form-control" name="slug" value="<?= e((string)($editing['slug'] ?? '')) ?>"></div>
                            <div class="col-md-8"><label class="form-label">Resumo</label><input class="form-control" name="summary" value="<?= e((string)($editing['summary'] ?? '')) ?>"></div>
                            <div class="col-md-4"><label class="form-label">Ordem</label><input class="form-control" type="number" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? 0)) ?>"></div>
                            <div class="col-12"><label class="form-label">Fonte (URL)</label><input class="form-control" name="source_url" value="<?= e((string)($editing['source_url'] ?? '')) ?>"></div>
                            <div class="col-12"><label class="form-label">Conteudo</label><textarea class="form-control editor" name="content_html" rows="10"><?= e((string)($editing['content_html'] ?? '')) ?></textarea></div>
                            <div class="col-12">
                                <?php $active = !isset($editing['is_active']) || (int)$editing['is_active'] === 1; ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active"<?= $active ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="isActive">Pagina ativa</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary" type="submit"><?= $editing ? 'Salvar alteracoes' : 'Criar pagina' ?></button>
                            <?php if ($editing): ?><a class="btn btn-outline-secondary" href="/admin/pos-subsite.php">Cancelar</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Paginas importadas/cadastradas</h3></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Titulo</th><th>Slug</th><th>Status</th><th class="text-end">Acoes</th></tr></thead>
                        <tbody>
                            <?php foreach ($pages as $p): ?>
                                <tr>
                                    <td><?= e((string)$p['title']) ?></td>
                                    <td><code><?= e((string)$p['slug']) ?></code></td>
                                    <td><?= (int)$p['is_active'] === 1 ? 'Ativa' : 'Oculta' ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-outline-primary btn-sm" href="/admin/pos-subsite.php?edit=<?= e((string)$p['id']) ?>">Editar</a>
                                        <a class="btn btn-outline-secondary btn-sm" href="/pos/pagina.php?slug=<?= urlencode((string)$p['slug']) ?>" target="_blank" rel="noopener">Ver</a>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= e((string)$p['id']) ?>">
                                            <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('Excluir esta pagina?');">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div></div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>
<script>
tinymce.init({
  selector: '.editor',
  height: 320,
  menubar: false,
  plugins: 'lists link table code',
  toolbar: 'undo redo | bold italic | bullist numlist | link | code',
  branding: false
});
</script>
</body>
</html>



