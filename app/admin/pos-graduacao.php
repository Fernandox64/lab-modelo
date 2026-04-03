<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin();
ensure_ppgcc_tables();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $action = (string)($_POST['action'] ?? 'save_content');
        try {
            if ($action === 'save_content') {
                ppgcc_content_get();
                ppgcc_content_save($_POST);
                $success = 'Conteudo da pagina de pos-graduacao atualizado.';
            } elseif ($action === 'import_selection') {
                $result = ppgcc_import_selection_page();
                if (($result['ok'] ?? false) === true) {
                    $success = 'Processo seletivo importado. Itens inseridos: ' . (string)($result['inserted'] ?? 0) . '.';
                } else {
                    $error = (string)($result['message'] ?? 'Falha na importacao do processo seletivo.');
                }
            } elseif ($action === 'add_graduate') {
                $year = (int)($_POST['graduate_year'] ?? 0);
                $name = trim((string)($_POST['student_name'] ?? ''));
                $source = trim((string)($_POST['source_url'] ?? ''));
                if ($year < 2000 || $year > 2100 || $name === '') {
                    $error = 'Informe ano e nome validos para cadastrar egresso.';
                } else {
                    $stmt = db()->prepare(
                        'INSERT INTO ppgcc_graduates (graduate_year, student_name, source_url)
                         VALUES (:y, :n, :s)
                         ON DUPLICATE KEY UPDATE source_url = VALUES(source_url)'
                    );
                    $stmt->execute([':y' => $year, ':n' => $name, ':s' => $source !== '' ? $source : null]);
                    $success = 'Egresso adicionado/atualizado.';
                }
            } elseif ($action === 'delete_graduate') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = db()->prepare('DELETE FROM ppgcc_graduates WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    $success = 'Egresso removido.';
                }
            }
        } catch (Throwable $e) {
            $error = 'Falha ao processar requisicao no modulo de pos-graduacao.';
            error_log('Admin pos-graduacao error: ' . $e->getMessage());
        }
    }
}

$content = ppgcc_content_get();
$yearStats = ppgcc_graduate_years();
$selectedYear = isset($_GET['year']) ? (int)($_GET['year']) : 0;
if ($selectedYear === 0 && !empty($yearStats)) {
    $selectedYear = (int)$yearStats[0]['graduate_year'];
}
$graduates = $selectedYear > 0 ? ppgcc_graduates_by_year($selectedYear) : [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Pos-graduacao</title>
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
                    <li class="nav-item"><a href="/admin/atendimento-docentes.php" class="nav-link"><p>Atendimento Docentes</p></a></li>
                    <li class="nav-item"><a href="/admin/menu.php" class="nav-link"><p>Menu Principal</p></a></li>
                    <li class="nav-item"><a href="/admin/carousel.php" class="nav-link"><p>Carrossel Home</p></a></li>
                    <li class="nav-item"><a href="/admin/horarios.php" class="nav-link"><p>Horarios de Aula</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-graduacao.php" class="nav-link active"><p>Pos-graduacao</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-publicacoes.php?tipo=noticias" class="nav-link"><p>Noticias/Editais Pos</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-subsite.php" class="nav-link"><p>Subsite Pos</p></a></li>
                    <li class="nav-item"><a href="/health.php" class="nav-link" target="_blank" rel="noopener"><p>Health</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header"><div class="container-fluid"><h3 class="mb-0">Gerenciar Pos-graduacao</h3></div></div>
        <div class="app-content"><div class="container-fluid">
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">Conteudo da pagina publica</h3></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="save_content">
                        <div class="mb-3"><label class="form-label">Titulo da pagina</label><input class="form-control" name="title" value="<?= e((string)$content['title']) ?>"></div>
                        <div class="mb-3"><label class="form-label">Introducao</label><textarea class="form-control editor" name="intro_html" rows="5"><?= e((string)$content['intro_html']) ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Criterios de ingresso</label><textarea class="form-control editor" name="ingresso_html" rows="5"><?= e((string)$content['ingresso_html']) ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Editais e selecoes</label><textarea class="form-control editor" name="editais_html" rows="5"><?= e((string)$content['editais_html']) ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Grade e carga horaria</label><textarea class="form-control editor" name="grade_html" rows="5"><?= e((string)$content['grade_html']) ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Estagio em docencia</label><textarea class="form-control editor" name="docencia_html" rows="5"><?= e((string)$content['docencia_html']) ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Bolsas e auxilios</label><textarea class="form-control editor" name="bolsas_html" rows="5"><?= e((string)$content['bolsas_html']) ?></textarea></div>
                        <div class="mb-3"><label class="form-label">Facilidades para graduacao</label><textarea class="form-control editor" name="graduacao_html" rows="5"><?= e((string)$content['graduacao_html']) ?></textarea></div>
                        <button class="btn btn-primary" type="submit">Salvar conteudo</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">Publicacoes da Pos (separadas)</h3></div>
                <div class="card-body">
                    <p class="mb-2">Noticias e editais da pos sao gerenciados em modulo separado.</p>
                    <a class="btn btn-primary btn-sm" href="/admin/pos-publicacoes.php?tipo=noticias">Gerenciar Noticias da Pos</a>
                    <a class="btn btn-danger btn-sm ms-2" href="/admin/pos-publicacoes.php?tipo=editais">Gerenciar Editais da Pos</a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">Processo seletivo (importacao da pagina antiga)</h3></div>
                <div class="card-body">
                    <p class="mb-2">
                        Esta acao atualiza a pagina nova <code>/ensino/pos-processo-seletivo.php</code> com os dados da fonte oficial:
                        <a target="_blank" rel="noopener" href="https://www3.decom.ufop.br/pos/processoseletivo/">www3.decom.ufop.br/pos/processoseletivo</a>.
                    </p>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="import_selection">
                        <button class="btn btn-dark" type="submit">Importar dados agora</button>
                    </form>
                    <a class="btn btn-outline-primary ms-2" target="_blank" rel="noopener" href="/ensino/pos-processo-seletivo.php">Ver pagina publica</a>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Adicionar egresso</h3></div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="add_graduate">
                                <div class="mb-2"><label class="form-label">Ano</label><input class="form-control" type="number" name="graduate_year" min="2000" max="2100" value="<?= e((string)($selectedYear ?: date('Y'))) ?>"></div>
                                <div class="mb-2"><label class="form-label">Nome completo</label><input class="form-control" name="student_name"></div>
                                <div class="mb-3"><label class="form-label">Fonte (URL opcional)</label><input class="form-control" name="source_url" placeholder="https://www3.decom.ufop.br/pos/discentes/egressos/..."></div>
                                <button class="btn btn-success" type="submit">Adicionar</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Egressos por ano</h3></div>
                        <div class="card-body">
                            <div class="mb-3 d-flex flex-wrap gap-2">
                                <?php foreach ($yearStats as $ys): ?>
                                    <?php $y = (int)$ys['graduate_year']; ?>
                                    <a class="btn btn-sm <?= $y === $selectedYear ? 'btn-dark' : 'btn-outline-dark' ?>" href="/admin/pos-graduacao.php?year=<?= e((string)$y) ?>">
                                        <?= e((string)$y) ?> (<?= e((string)$ys['total']) ?>)
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead><tr><th>Nome</th><th>Ano</th><th class="text-end">Acoes</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($graduates as $g): ?>
                                            <tr>
                                                <td><?= e((string)$g['student_name']) ?></td>
                                                <td><?= e((string)$g['graduate_year']) ?></td>
                                                <td class="text-end">
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                        <input type="hidden" name="action" value="delete_graduate">
                                                        <input type="hidden" name="id" value="<?= e((string)$g['id']) ?>">
                                                        <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('Excluir egresso?');">Excluir</button>
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
            </div>
        </div></div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>
<script>
tinymce.init({
  selector: '.editor',
  height: 220,
  menubar: false,
  plugins: 'lists link table code',
  toolbar: 'undo redo | bold italic | bullist numlist | link | code',
  branding: false
});
</script>
</body>
</html>

