<?php
header('Content-Type: text/html; charset=UTF-8');
$themeInlineCss = current_site_palette_inline_css();
$topbarDepartmentName = trim(site_setting_get('topbar_department_name', 'Departamento Exemplo'));
$topbarPhone = trim(site_setting_get('topbar_phone', SITE_PHONE));
$topbarEmail = trim(site_setting_get('topbar_email', SITE_EMAIL));
if ($topbarDepartmentName === '') {
    $topbarDepartmentName = 'Departamento Exemplo';
}
if ($topbarPhone === '') {
    $topbarPhone = SITE_PHONE;
}
if ($topbarEmail === '') {
    $topbarEmail = SITE_EMAIL;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? SITE_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="/assets/css/theme.css" rel="stylesheet">
<style><?= $themeInlineCss ?></style>
<style>
.skip-link{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden}
.skip-link:focus{left:1rem;top:1rem;width:auto;height:auto;z-index:2000;padding:.5rem .75rem;background:#111;color:#fff;border-radius:.5rem}
</style>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="topbar py-2">
    <div class="container d-flex flex-wrap justify-content-between gap-2">
        <div class="topbar-brand-text">Universidade Federal de Ouro Preto | <?= e($topbarDepartmentName) ?></div>
        <div><?= e($topbarPhone) ?> | <?= e($topbarEmail) ?></div>
    </div>
</div>

<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-semibold" href="/" title="<?= e(SITE_NAME) ?>">
            <?= e(SITE_SIGLA) ?>
        </a>
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMain"
            aria-controls="navbarMain"
            aria-expanded="false"
            aria-label="Alternar navegacao">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/">HOME</a></li>
                <li class="nav-item"><a class="nav-link" href="/laboratorio/sobre.php">O LABORATORIO</a></li>
                <li class="nav-item"><a class="nav-link" href="/laboratorio/equipe.php">Equipe</a></li>
                <li class="nav-item"><a class="nav-link" href="/laboratorio/projetos.php">Projetos</a></li>
                <li class="nav-item"><a class="nav-link" href="/laboratorio/publicacoes.php">Publicacoes</a></li>
                <li class="nav-item"><a class="nav-link" href="/laboratorio/cursos.php">Cursos</a></li>
                <li class="nav-item"><a class="nav-link" href="/laboratorio/parceiros.php">Parceiros</a></li>
                <li class="nav-item"><a class="nav-link" href="/laboratorio/tutoriais.php">Tutoriais</a></li>
                <li class="nav-item"><a class="nav-link" href="/laboratorio/blog.php">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="/laboratorio/eventos.php">Eventos</a></li>
            </ul>

            <div class="d-flex gap-2">
                <a class="btn btn-primary btn-sm" href="/admin/login.php" aria-label="Area administrativa" title="Area administrativa">
                    <i class="bi bi-person-workspace"></i>
                </a>
            </div>
        </div>
    </div>
</nav>
