<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin();

function admin_count_table(string $table): int {
    try {
        return (int)db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$newsCount = admin_count_table('news_items');
$editaisCount = admin_count_table('edital_items');
$defesasCount = admin_count_table('defesa_items');
$jobsCount = admin_count_table('job_items');
$peopleCount = admin_count_table('people_items');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">Menu</a></li>
                <li class="nav-item d-none d-md-block"><a href="/" class="nav-link">Site</a></li>
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
                    <li class="nav-item"><a href="/admin/dashboard.php" class="nav-link active"><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="/admin/content.php?type=noticias" class="nav-link"><p>Noticias</p></a></li>
                    <li class="nav-item"><a href="/admin/content.php?type=editais" class="nav-link"><p>Editais</p></a></li>
                    <li class="nav-item"><a href="/admin/content.php?type=defesas" class="nav-link"><p>Defesas</p></a></li>
                    <li class="nav-item"><a href="/admin/content.php?type=estagios" class="nav-link"><p>Estagios e Empregos</p></a></li>
                    <li class="nav-item"><a href="/admin/pessoal.php" class="nav-link"><p>Pessoal</p></a></li>
                    <li class="nav-item"><a href="/admin/atendimento-docentes.php" class="nav-link"><p>Atendimento Docentes</p></a></li>
                    <li class="nav-item"><a href="/admin/menu.php" class="nav-link"><p>Menu Principal</p></a></li>
                    <li class="nav-item"><a href="/admin/carousel.php" class="nav-link"><p>Carrossel Home</p></a></li>
                    <li class="nav-item"><a href="/admin/horarios.php" class="nav-link"><p>Horarios de Aula</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-graduacao.php" class="nav-link"><p>Pos-graduacao</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-publicacoes.php?tipo=noticias" class="nav-link"><p>Noticias/Editais Pos</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-subsite.php" class="nav-link"><p>Subsite Pos</p></a></li>
                    <li class="nav-item"><a href="/health.php" class="nav-link" target="_blank" rel="noopener"><p>Health</p></a></li>
                    <li class="nav-item"><a href="/noticias/index.php" class="nav-link" target="_blank" rel="noopener"><p>Ver Noticias</p></a></li>
                    <li class="nav-item"><a href="/noticias/editais.php" class="nav-link" target="_blank" rel="noopener"><p>Ver Editais</p></a></li>
                    <li class="nav-item"><a href="/noticias/defesas.php" class="nav-link" target="_blank" rel="noopener"><p>Ver Defesas</p></a></li>
                    <li class="nav-item"><a href="/noticias/estagios-empregos.php" class="nav-link" target="_blank" rel="noopener"><p>Ver Estagios e Empregos</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h3 class="mb-0">Painel Administrativo</h3></div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card card-primary card-outline">
                            <div class="card-header"><h3 class="card-title">Noticias</h3></div>
                            <div class="card-body">
                                <p class="display-6 mb-2"><?= e((string)$newsCount) ?></p>
                                <p class="text-secondary">Registros cadastrados.</p>
                                <a class="btn btn-primary" href="/admin/content.php?type=noticias">Gerenciar Noticias</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-secondary card-outline">
                            <div class="card-header"><h3 class="card-title">Editais</h3></div>
                            <div class="card-body">
                                <p class="display-6 mb-2"><?= e((string)$editaisCount) ?></p>
                                <p class="text-secondary">Registros cadastrados.</p>
                                <a class="btn btn-secondary" href="/admin/content.php?type=editais">Gerenciar Editais</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-primary card-outline">
                            <div class="card-header"><h3 class="card-title">Horarios de Aula</h3></div>
                            <div class="card-body">
                                <p class="mb-2">Edite e importe os horarios de alunos pela pagina oficial antiga.</p>
                                <a class="btn btn-primary" href="/admin/horarios.php">Gerenciar Horarios</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-info card-outline">
                            <div class="card-header"><h3 class="card-title">Defesas</h3></div>
                            <div class="card-body">
                                <p class="display-6 mb-2"><?= e((string)$defesasCount) ?></p>
                                <p class="text-secondary">Registros cadastrados.</p>
                                <a class="btn btn-info text-white" href="/admin/content.php?type=defesas">Gerenciar Defesas</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-dark card-outline">
                            <div class="card-header"><h3 class="card-title">Estagios e Empregos</h3></div>
                            <div class="card-body">
                                <p class="display-6 mb-2"><?= e((string)$jobsCount) ?></p>
                                <p class="text-secondary">Registros cadastrados.</p>
                                <a class="btn btn-dark" href="/admin/content.php?type=estagios">Gerenciar Estagios e Empregos</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-warning card-outline">
                            <div class="card-header"><h3 class="card-title">Pessoal</h3></div>
                            <div class="card-body">
                                <p class="display-6 mb-2"><?= e((string)$peopleCount) ?></p>
                                <p class="text-secondary">Docentes e funcionarios cadastrados.</p>
                                <a class="btn btn-warning" href="/admin/pessoal.php">Gerenciar Pessoal</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-success card-outline">
                            <div class="card-header"><h3 class="card-title">Menu Principal</h3></div>
                            <div class="card-body">
                                <p class="mb-2">Edite os itens de navegaÃ§Ã£o de GraduaÃ§Ã£o e PÃ³s-graduaÃ§Ã£o.</p>
                                <a class="btn btn-success" href="/admin/menu.php">Gerenciar Menu</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-info card-outline">
                            <div class="card-header"><h3 class="card-title">Pos-graduacao</h3></div>
                            <div class="card-body">
                                <p class="mb-2">Edite secoes da pagina de pos e gerencie egressos por ano.</p>
                                <a class="btn btn-info text-white" href="/admin/pos-graduacao.php">Gerenciar Pos-graduacao</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-danger card-outline">
                            <div class="card-header"><h3 class="card-title">Publicacoes da Pos</h3></div>
                            <div class="card-body">
                                <p class="mb-2">Postagem separada de noticias e editais da pos-graduacao.</p>
                                <a class="btn btn-danger" href="/admin/pos-publicacoes.php?tipo=noticias">Gerenciar Noticias/Editais da Pos</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-dark card-outline">
                            <div class="card-header"><h3 class="card-title">Subsite Pos</h3></div>
                            <div class="card-body">
                                <p class="mb-2">Importe e gerencie paginas institucionais da pos antiga em /pos.</p>
                                <a class="btn btn-dark" href="/admin/pos-subsite.php">Gerenciar Subsite Pos</a>
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
</body>
</html>

