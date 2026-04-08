<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_content');

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        try {
            laboratory_contact_save($_POST);
            admin_audit_log('laboratorio_contact_update', ['page' => 'contato/index.php'], 'site_settings');
            $success = 'Pagina "Contato" atualizada com sucesso.';
        } catch (Throwable $e) {
            $error = 'Nao foi possivel salvar o conteudo da pagina.';
            error_log('Admin laboratorio-contato error: ' . $e->getMessage());
        }
    }
}

$content = laboratory_contact_get();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Pagina Contato</title>
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
    <?php render_admin_sidebar('lab_contact'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Editar Pagina Contato</h3>
                    <a class="btn btn-dark btn-sm" href="/contato/index.php" target="_blank" rel="noopener">Ver pagina publica</a>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Conteudo da pagina</h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Titulo</label>
                                    <input class="form-control" name="title" required value="<?= e((string)$content['title']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Resumo</label>
                                    <textarea class="form-control" rows="3" name="summary" required><?= e((string)$content['summary']) ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">E-mail</label>
                                    <input class="form-control" name="email" value="<?= e((string)$content['email']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Telefone</label>
                                    <input class="form-control" name="phone" value="<?= e((string)$content['phone']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Endereco</label>
                                    <input class="form-control" name="address" value="<?= e((string)$content['address']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Conteudo complementar</label>
                                    <textarea class="form-control editor" rows="8" name="content_html"><?= e((string)$content['content_html']) ?></textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">Salvar alteracoes</button>
                        </form>
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

