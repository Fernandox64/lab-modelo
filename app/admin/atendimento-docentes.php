<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $action = (string)($_POST['action'] ?? 'save');
        try {
            if ($action === 'seed_people') {
                atendimento_docentes_seed_from_people();
                $success = 'Tabela preenchida automaticamente com docentes cadastrados.';
            } else {
                atendimento_docentes_save([
                    'title' => (string)($_POST['title'] ?? ''),
                    'summary' => (string)($_POST['summary'] ?? ''),
                    'intro_html' => (string)($_POST['intro_html'] ?? ''),
                    'table_html' => (string)($_POST['table_html'] ?? ''),
                    'notes_html' => (string)($_POST['notes_html'] ?? ''),
                    'source_url' => (string)($_POST['source_url'] ?? ''),
                ]);
                $success = 'Horarios de atendimento salvos com sucesso.';
            }
        } catch (Throwable $e) {
            $error = 'Falha ao salvar dados de atendimento.';
            error_log('Admin atendimento-docentes error: ' . $e->getMessage());
        }
    }
}

$data = atendimento_docentes_get();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Atendimento Docentes</title>
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

    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="/admin/dashboard.php" class="brand-link text-decoration-none"><span class="brand-text fw-light">DECOM Admin</span></a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                    <li class="nav-item"><a href="/admin/dashboard.php" class="nav-link"><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="/admin/content.php?type=noticias" class="nav-link"><p>Noticias</p></a></li>
                    <li class="nav-item"><a href="/admin/content.php?type=editais" class="nav-link"><p>Editais</p></a></li>
                    <li class="nav-item"><a href="/admin/content.php?type=defesas" class="nav-link"><p>Defesas</p></a></li>
                    <li class="nav-item"><a href="/admin/content.php?type=estagios" class="nav-link"><p>Estagios e Empregos</p></a></li>
                    <li class="nav-item"><a href="/admin/pessoal.php" class="nav-link"><p>Pessoal</p></a></li>
                    <li class="nav-item"><a href="/admin/atendimento-docentes.php" class="nav-link active"><p>Atendimento Docentes</p></a></li>
                    <li class="nav-item"><a href="/admin/menu.php" class="nav-link"><p>Menu Principal</p></a></li>
                    <li class="nav-item"><a href="/admin/carousel.php" class="nav-link"><p>Carrossel Home</p></a></li>
                    <li class="nav-item"><a href="/admin/horarios.php" class="nav-link"><p>Horarios de Aula</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-graduacao.php" class="nav-link"><p>Pos-graduacao</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-publicacoes.php?tipo=noticias" class="nav-link"><p>Noticias/Editais Pos</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-subsite.php" class="nav-link"><p>Subsite Pos</p></a></li>
                    <li class="nav-item"><a href="/health.php" class="nav-link" target="_blank" rel="noopener"><p>Health</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Horarios de Atendimento dos Docentes</h3>
                    <a class="btn btn-dark btn-sm" href="/pessoal/atendimento-docentes.php" target="_blank" rel="noopener">Ver pagina publica</a>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Preencher automaticamente</h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="seed_people">
                            <button class="btn btn-outline-primary" type="submit">Gerar tabela com docentes cadastrados</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Conteudo editavel</h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="save">
                            <div class="row g-3">
                                <div class="col-md-8"><label class="form-label">Titulo</label><input class="form-control" name="title" value="<?= e((string)$data['title']) ?>"></div>
                                <div class="col-md-4"><label class="form-label">URL de referencia</label><input class="form-control" name="source_url" value="<?= e((string)$data['source_url']) ?>"></div>
                                <div class="col-12"><label class="form-label">Resumo</label><textarea class="form-control" name="summary" rows="2"><?= e((string)$data['summary']) ?></textarea></div>
                                <div class="col-12"><label class="form-label">Introducao</label><textarea class="form-control editor" name="intro_html" rows="5"><?= e((string)$data['intro_html']) ?></textarea></div>
                                <div class="col-12"><label class="form-label">Tabela semanal (HTML editavel)</label><textarea class="form-control editor" name="table_html" rows="18"><?= e((string)$data['table_html']) ?></textarea></div>
                                <div class="col-12"><label class="form-label">Observacoes</label><textarea class="form-control editor" name="notes_html" rows="6"><?= e((string)$data['notes_html']) ?></textarea></div>
                            </div>
                            <div class="mt-3"><button class="btn btn-primary" type="submit">Salvar</button></div>
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
  height: 300,
  menubar: false,
  plugins: 'lists link table code',
  toolbar: 'undo redo | bold italic | bullist numlist | link | table | code',
  branding: false
});
</script>
</body>
</html>

