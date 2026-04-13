<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_content');

function footer_links_to_text(array $links): string {
    $lines = [];
    foreach ($links as $link) {
        if (!is_array($link)) {
            continue;
        }
        $label = trim((string)($link['label'] ?? ''));
        $url = trim((string)($link['url'] ?? ''));
        if ($label === '' || $url === '') {
            continue;
        }
        $lines[] = $label . ' | ' . $url;
    }
    return implode("\n", $lines);
}

function footer_links_from_text(string $raw): array {
    $links = [];
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = explode('|', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $label = trim((string)$parts[0]);
        $url = trim((string)$parts[1]);
        if ($label === '' || $url === '') {
            continue;
        }
        $links[] = ['label' => $label, 'url' => $url];
    }
    return $links;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        try {
            $linksText = (string)($_POST['links_text'] ?? '');
            $payload = [
                'brand_name' => (string)($_POST['brand_name'] ?? ''),
                'brand_sigla' => (string)($_POST['brand_sigla'] ?? ''),
                'brand_university' => (string)($_POST['brand_university'] ?? ''),
                'contact_phone' => (string)($_POST['contact_phone'] ?? ''),
                'contact_email' => (string)($_POST['contact_email'] ?? ''),
                'show_calendar' => isset($_POST['show_calendar']) ? '1' : '0',
                'links' => footer_links_from_text($linksText),
            ];
            footer_save($payload);
            admin_audit_log('footer_update', ['links_count' => count((array)$payload['links'])], 'site_settings');
            $success = 'Rodape atualizado com sucesso.';
        } catch (Throwable $e) {
            $error = 'Nao foi possivel salvar o rodape.';
            error_log('Admin footer error: ' . $e->getMessage());
        }
    }
}

$footer = footer_get();
$linksText = footer_links_to_text((array)$footer['links']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Rodape do Site</title>
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
    <?php render_admin_sidebar('footer'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Editar Rodape do Site</h3>
                    <a class="btn btn-dark btn-sm" href="/" target="_blank" rel="noopener">Ver site publico</a>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Configuracoes do rodape</h3></div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                            <div class="col-md-4">
                                <label class="form-label">Nome exibido</label>
                                <input class="form-control" name="brand_name" value="<?= e((string)$footer['brand_name']) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Sigla</label>
                                <input class="form-control" name="brand_sigla" value="<?= e((string)$footer['brand_sigla']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Universidade</label>
                                <input class="form-control" name="brand_university" value="<?= e((string)$footer['brand_university']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telefone</label>
                                <input class="form-control" name="contact_phone" value="<?= e((string)$footer['contact_phone']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-mail</label>
                                <input class="form-control" name="contact_email" value="<?= e((string)$footer['contact_email']) ?>">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_calendar" id="show_calendar" value="1"<?= !empty($footer['show_calendar']) ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="show_calendar">Exibir calendario no rodape</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Links do rodape</label>
                                <textarea class="form-control" name="links_text" rows="10" placeholder="Label | /url"><?= e($linksText) ?></textarea>
                                <div class="form-text">Formato: um link por linha, no padrao <code>Label | /url</code>.</div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Salvar rodape</button>
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
