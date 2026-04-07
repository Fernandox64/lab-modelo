<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_pos');

$error = null;
$success = null;
$activeTab = (string)($_GET['tab'] ?? ($_POST['section'] ?? 'pesquisa'));
$activeTab = $activeTab === 'extensao' ? 'extensao' : 'pesquisa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        try {
            $section = (string)($_POST['section'] ?? 'pesquisa');
            $section = $section === 'extensao' ? 'extensao' : 'pesquisa';
            ppgcc_section_save($section, [
                'title' => (string)($_POST['title'] ?? ''),
                'summary' => (string)($_POST['summary'] ?? ''),
                'content_html' => (string)($_POST['content_html'] ?? ''),
            ]);
            $success = 'Conteudo da aba ' . ($section === 'pesquisa' ? 'Pesquisa' : 'Extensao') . ' salvo com sucesso.';
            $activeTab = $section;
        } catch (Throwable $e) {
            $error = 'Falha ao salvar conteudo da secao da pos.';
            error_log('Admin pos-secoes error: ' . $e->getMessage());
        }
    }
}

$pesquisa = ppgcc_section_get('pesquisa');
$extensao = ppgcc_section_get('extensao');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Pesquisa e Extensao da Pos</title>
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

    <?php render_admin_sidebar('pos_sections'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <h3 class="mb-0">Pos-graduacao - Abas Pesquisa e Extensao</h3>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-header border-bottom-0">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link<?= $activeTab === 'pesquisa' ? ' active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabPesquisa" type="button" role="tab">Pesquisa</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link<?= $activeTab === 'extensao' ? ' active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabExtensao" type="button" role="tab">Extensao</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade<?= $activeTab === 'pesquisa' ? ' show active' : '' ?>" id="tabPesquisa" role="tabpanel">
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="section" value="pesquisa">
                                    <div class="mb-3"><label class="form-label">Titulo</label><input class="form-control" name="title" value="<?= e((string)$pesquisa['title']) ?>"></div>
                                    <div class="mb-3"><label class="form-label">Resumo</label><textarea class="form-control" name="summary" rows="2"><?= e((string)$pesquisa['summary']) ?></textarea></div>
                                    <div class="mb-3"><label class="form-label">Conteudo</label><textarea class="form-control editor" name="content_html" rows="10"><?= e((string)$pesquisa['content_html']) ?></textarea></div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary" type="submit">Salvar Pesquisa</button>
                                        <a class="btn btn-outline-secondary" href="/pos/pesquisa.php" target="_blank" rel="noopener">Ver pagina publica</a>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade<?= $activeTab === 'extensao' ? ' show active' : '' ?>" id="tabExtensao" role="tabpanel">
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="section" value="extensao">
                                    <div class="mb-3"><label class="form-label">Titulo</label><input class="form-control" name="title" value="<?= e((string)$extensao['title']) ?>"></div>
                                    <div class="mb-3"><label class="form-label">Resumo</label><textarea class="form-control" name="summary" rows="2"><?= e((string)$extensao['summary']) ?></textarea></div>
                                    <div class="mb-3"><label class="form-label">Conteudo</label><textarea class="form-control editor" name="content_html" rows="10"><?= e((string)$extensao['content_html']) ?></textarea></div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary" type="submit">Salvar Extensao</button>
                                        <a class="btn btn-outline-secondary" href="/pos/extensao.php" target="_blank" rel="noopener">Ver pagina publica</a>
                                    </div>
                                </form>
                            </div>
                        </div>
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
