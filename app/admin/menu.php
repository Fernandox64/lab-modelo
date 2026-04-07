<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_menu');

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        try {
            $graduacaoLabel = trim((string)($_POST['graduacao_label'] ?? ''));
            $graduacaoUrl = normalize_menu_url((string)($_POST['graduacao_url'] ?? ''), '/ensino/ciencia-computacao.php');
            $posLabel = trim((string)($_POST['pos_graduacao_label'] ?? ''));
            $posUrl = normalize_menu_url((string)($_POST['pos_graduacao_url'] ?? ''), '/ensino/pos-graduacao.php');
            $departmentName = trim((string)($_POST['topbar_department_name'] ?? ''));
            $topbarPhone = trim((string)($_POST['topbar_phone'] ?? ''));
            $topbarEmail = trim((string)($_POST['topbar_email'] ?? ''));
            $showStudentCalendar = isset($_POST['show_student_calendar']) ? '1' : '0';
            $studentCalendarSourceUrl = trim((string)($_POST['student_calendar_source_url'] ?? ''));
            $studentCalendarShowHolidays = isset($_POST['student_calendar_show_holidays']) ? '1' : '0';
            $studentCalendarManualEvents = trim((string)($_POST['student_calendar_manual_events'] ?? ''));

            if ($graduacaoLabel === '' || $posLabel === '' || $departmentName === '' || $topbarPhone === '' || $topbarEmail === '') {
                $error = 'Preencha todos os campos obrigatorios.';
            } else {
                site_setting_set('menu_graduacao_label', $graduacaoLabel);
                site_setting_set('menu_graduacao_url', $graduacaoUrl);
                site_setting_set('menu_pos_graduacao_label', $posLabel);
                site_setting_set('menu_pos_graduacao_url', $posUrl);
                site_setting_set('topbar_department_name', $departmentName);
                site_setting_set('topbar_phone', $topbarPhone);
                site_setting_set('topbar_email', $topbarEmail);
                site_setting_set('show_student_calendar', $showStudentCalendar);
                site_setting_set('student_calendar_source_url', $studentCalendarSourceUrl !== '' ? $studentCalendarSourceUrl : 'https://www.prograd.ufop.br/calendario-academico');
                site_setting_set('student_calendar_show_holidays', $studentCalendarShowHolidays);
                site_setting_set('student_calendar_manual_events', $studentCalendarManualEvents);
                admin_audit_log('menu_update', [
                    'graduacao_label' => $graduacaoLabel,
                    'graduacao_url' => $graduacaoUrl,
                    'pos_label' => $posLabel,
                    'pos_url' => $posUrl,
                    'topbar_department_name' => $departmentName,
                    'topbar_phone' => $topbarPhone,
                    'topbar_email' => $topbarEmail,
                    'show_student_calendar' => $showStudentCalendar,
                    'student_calendar_source_url' => $studentCalendarSourceUrl,
                    'student_calendar_show_holidays' => $studentCalendarShowHolidays,
                    'student_calendar_manual_events_count' => $studentCalendarManualEvents === '' ? 0 : count(preg_split("/(\r\n|\n|\r)/", $studentCalendarManualEvents)),
                ], 'site_settings');
                $success = 'Menu principal atualizado com sucesso.';
            }
        } catch (Throwable $e) {
            $error = 'Nao foi possivel salvar as configuracoes do menu.';
            error_log('Failed saving menu settings: ' . $e->getMessage());
        }
    }
}

$graduacao = primary_menu_item('graduacao');
$posGraduacao = primary_menu_item('pos_graduacao');
$topbarDepartmentName = site_setting_get('topbar_department_name', 'Departamento Exemplo');
$topbarPhone = site_setting_get('topbar_phone', SITE_PHONE);
$topbarEmail = site_setting_get('topbar_email', SITE_EMAIL);
$showStudentCalendar = site_setting_get('show_student_calendar', '1') !== '0';
$studentCalendarSourceUrl = site_setting_get('student_calendar_source_url', 'https://www.prograd.ufop.br/calendario-academico');
$studentCalendarShowHolidays = site_setting_get('student_calendar_show_holidays', '1') !== '0';
$studentCalendarManualEvents = site_setting_get('student_calendar_manual_events', '');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Menu Principal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
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
    <?php render_admin_sidebar('menu'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Editar Menu Principal</h3>
                    <a class="btn btn-dark btn-sm" href="/" target="_blank" rel="noopener">Ver site</a>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Itens editaveis</h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                            <div class="row g-3">
                                <div class="col-12"><h4 class="h6 text-uppercase text-muted">Item 1 - Graduacao</h4></div>
                                <div class="col-md-4">
                                    <label class="form-label">Titulo</label>
                                    <input class="form-control" name="graduacao_label" required value="<?= e((string)$graduacao['label']) ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">URL</label>
                                    <input class="form-control" name="graduacao_url" required value="<?= e((string)$graduacao['url']) ?>">
                                </div>

                                <div class="col-12 pt-2"><h4 class="h6 text-uppercase text-muted">Item 2 - P&oacute;s-gradua&ccedil;&atilde;o</h4></div>
                                <div class="col-md-4">
                                    <label class="form-label">Titulo</label>
                                    <input class="form-control" name="pos_graduacao_label" required value="<?= e((string)$posGraduacao['label']) ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">URL</label>
                                    <input class="form-control" name="pos_graduacao_url" required value="<?= e((string)$posGraduacao['url']) ?>">
                                </div>

                                <div class="col-12 pt-2"><h4 class="h6 text-uppercase text-muted">Topo do site</h4></div>
                                <div class="col-md-6">
                                    <label class="form-label">Nome do departamento (ao lado de UFOP)</label>
                                    <input class="form-control" name="topbar_department_name" required value="<?= e((string)$topbarDepartmentName) ?>" placeholder="Departamento Exemplo">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Telefone do topo</label>
                                    <input class="form-control" name="topbar_phone" required value="<?= e((string)$topbarPhone) ?>" placeholder="+55 00 0000-0000">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">E-mail do topo</label>
                                    <input class="form-control" name="topbar_email" required value="<?= e((string)$topbarEmail) ?>" placeholder="departamento@instituicao.br">
                                </div>
                                <div class="col-12">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="show_student_calendar" name="show_student_calendar" value="1" <?= $showStudentCalendar ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_student_calendar">Exibir calendario na area do aluno</label>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Link do calendario oficial</label>
                                    <input class="form-control" name="student_calendar_source_url" value="<?= e((string)$studentCalendarSourceUrl) ?>" placeholder="https://www.prograd.ufop.br/calendario-academico">
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4 pt-2">
                                        <input class="form-check-input" type="checkbox" id="student_calendar_show_holidays" name="student_calendar_show_holidays" value="1" <?= $studentCalendarShowHolidays ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="student_calendar_show_holidays">Exibir feriados do mes</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Eventos manuais do departamento (1 por linha)</label>
                                    <textarea class="form-control" rows="4" name="student_calendar_manual_events" placeholder="2026-04-10 | Semana de Integracao&#10;2026-04-22 | Oficina de Extensao"><?= e((string)$studentCalendarManualEvents) ?></textarea>
                                    <div class="form-text">Formato: <code>AAAA-MM-DD | Titulo do evento</code></div>
                                </div>

                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Salvar menu</button>
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
</body>
</html>

