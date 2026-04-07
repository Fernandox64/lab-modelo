<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_schedule');

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $action = (string)($_POST['action'] ?? 'save');
        try {
            if ($action === 'import') {
                $result = horarios_import_from_legacy((string)($_POST['source_url'] ?? ''));
                if (($result['ok'] ?? false) === true) {
                    $success = 'Importacao concluida. Links encontrados: ' . (string)($result['count'] ?? 0) . '.';
                } else {
                    $error = (string)($result['message'] ?? 'Falha na importacao.');
                }
            } elseif ($action === 'load_example') {
                horarios_page_save([
                    'title' => (string)($_POST['title'] ?? 'Horarios de Aula'),
                    'summary' => (string)($_POST['summary'] ?? 'Consulta organizada dos horarios de aula por curso, periodo e turma.'),
                    'intro_html' => (string)($_POST['intro_html'] ?? ''),
                    'schedule_html' => horarios_cc_2026_template_html(),
                    'other_electives_html' => horarios_cc_2026_outras_eletivas_html(),
                    'links_html' => (string)($_POST['links_html'] ?? ''),
                    'source_url' => (string)($_POST['source_url'] ?? ''),
                ]);
                $success = 'Modelo completo 2026-1 carregado com sucesso.';
            } else {
                horarios_page_save([
                    'title' => (string)($_POST['title'] ?? ''),
                    'summary' => (string)($_POST['summary'] ?? ''),
                    'intro_html' => (string)($_POST['intro_html'] ?? ''),
                    'schedule_html' => (string)($_POST['schedule_html'] ?? ''),
                    'other_electives_html' => (string)($_POST['other_electives_html'] ?? ''),
                    'links_html' => (string)($_POST['links_html'] ?? ''),
                    'source_url' => (string)($_POST['source_url'] ?? ''),
                ]);
                $success = 'Conteudo de horarios salvo com sucesso.';
            }
        } catch (Throwable $e) {
            $error = 'Nao foi possivel processar a solicitacao.';
            error_log('Admin horarios error: ' . $e->getMessage());
        }
    }
}

$horarios = horarios_page_get();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Horarios de Aula</title>
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
    <?php render_admin_sidebar('horarios'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Gerenciar Horarios de Aula</h3>
                    <a class="btn btn-dark btn-sm" href="/ensino/horarios-de-aula.php" target="_blank" rel="noopener">Ver pagina publica</a>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Importar links da pagina antiga</h3></div>
                    <div class="card-body">
                        <form method="post" class="row g-2 align-items-end">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="import">
                            <div class="col-md-9">
                                <label class="form-label">URL de origem</label>
                                <input class="form-control" name="source_url" value="<?= e((string)$horarios['source_url']) ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-outline-primary w-100">Importar automatico</button>
                            </div>
                        </form>
                        <form method="post" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="load_example">
                            <input type="hidden" name="title" value="<?= e((string)$horarios['title']) ?>">
                            <input type="hidden" name="summary" value="<?= e((string)$horarios['summary']) ?>">
                            <input type="hidden" name="intro_html" value="<?= e((string)$horarios['intro_html']) ?>">
                            <input type="hidden" name="links_html" value="<?= e((string)$horarios['links_html']) ?>">
                            <input type="hidden" name="source_url" value="<?= e((string)$horarios['source_url']) ?>">
                            <button type="submit" class="btn btn-outline-dark">Carregar modelo de tabela 2026-1</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Conteudo da pagina</h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="save">

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Titulo</label>
                                    <input class="form-control" name="title" required value="<?= e((string)$horarios['title']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">URL de referencia</label>
                                    <input class="form-control" name="source_url" value="<?= e((string)$horarios['source_url']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Resumo</label>
                                    <textarea class="form-control" name="summary" rows="2" required><?= e((string)$horarios['summary']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Texto de introducao</label>
                                    <textarea class="form-control editor" name="intro_html" rows="6"><?= e((string)$horarios['intro_html']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Tabela de horarios das disciplinas (HTML editavel)</label>
                                    <textarea class="form-control editor" name="schedule_html" rows="20"><?= e((string)$horarios['schedule_html']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Outras eletivas (HTML editavel)</label>
                                    <textarea class="form-control editor" name="other_electives_html" rows="12"><?= e((string)$horarios['other_electives_html']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Lista de horarios/links</label>
                                    <textarea class="form-control editor" name="links_html" rows="12"><?= e((string)$horarios['links_html']) ?></textarea>
                                    <small class="text-muted">Dica: use lista com links para PDFs e planilhas de horarios.</small>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Salvar conteudo</button>
                            </div>
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
  height: 320,
  menubar: false,
  plugins: 'lists link table code',
  toolbar: 'undo redo | bold italic | bullist numlist | link | code',
  branding: false
});
</script>
</body>
</html>



