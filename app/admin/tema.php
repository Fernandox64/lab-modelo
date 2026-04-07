<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_menu');

$error = null;
$success = null;
$palettes = site_color_palettes();
$currentPaletteKey = current_site_palette_key();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $selected = trim((string)($_POST['palette_key'] ?? ''));
        if (!set_current_site_palette($selected)) {
            $error = 'Paleta invalida.';
        } else {
            admin_audit_log('theme_palette_update', ['palette_key' => $selected], 'site_settings');
            $success = 'Paleta aplicada com sucesso.';
            $currentPaletteKey = current_site_palette_key();
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Tema do Site</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
    <style>
        .palette-card { border: 1px solid #d8dee5; border-radius: 12px; padding: 12px; background: #fff; }
        .palette-card.active { border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13, 110, 253, .15); }
        .palette-swatches { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; margin-top: 8px; }
        .palette-swatch { height: 20px; border-radius: 6px; border: 1px solid rgba(0,0,0,.08); }
    </style>
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
    <?php render_admin_sidebar('tema'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Tema e Cores do Site</h3>
                    <a class="btn btn-dark btn-sm" href="/" target="_blank" rel="noopener">Ver site</a>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Escolha uma paleta</h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <div class="row g-3">
                                <?php foreach ($palettes as $key => $palette): ?>
                                    <?php $vars = $palette['vars']; $isActive = $currentPaletteKey === $key; ?>
                                    <div class="col-md-6 col-xl-4">
                                        <label class="palette-card d-block <?= $isActive ? 'active' : '' ?>">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div>
                                                    <div class="fw-semibold"><?= e((string)$palette['name']) ?></div>
                                                    <div class="text-muted small"><?= e((string)$palette['description']) ?></div>
                                                </div>
                                                <input type="radio" name="palette_key" value="<?= e((string)$key) ?>" <?= $isActive ? 'checked' : '' ?>>
                                            </div>
                                            <div class="palette-swatches">
                                                <span class="palette-swatch" style="background: <?= e((string)$vars['yellow']) ?>"></span>
                                                <span class="palette-swatch" style="background: <?= e((string)$vars['cyan']) ?>"></span>
                                                <span class="palette-swatch" style="background: <?= e((string)$vars['blue']) ?>"></span>
                                                <span class="palette-swatch" style="background: <?= e((string)$vars['navy']) ?>"></span>
                                                <span class="palette-swatch" style="background: <?= e((string)$vars['bg']) ?>"></span>
                                                <span class="palette-swatch" style="background: <?= e((string)$vars['text']) ?>"></span>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Aplicar paleta</button>
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
