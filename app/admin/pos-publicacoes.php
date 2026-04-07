<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_pos');
ensure_ppgcc_tables();

$typeInput = (string)($_GET['tipo'] ?? 'noticias');
$type = $typeInput === 'editais' ? 'edital' : 'informacao';
$typeLabel = $type === 'edital' ? 'Editais da Pos' : 'Noticias da Pos';
$sidebarActive = 'pos_publicacoes';

$error = null;
$success = null;
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $action = (string)($_POST['action'] ?? 'save');
        try {
            if ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $title = trim((string)($_POST['title'] ?? ''));
                $summary = trim((string)($_POST['summary'] ?? ''));
                $url = trim((string)($_POST['notice_url'] ?? ''));
                $publishedAt = trim((string)($_POST['published_at'] ?? ''));
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                if ($title === '' || $summary === '') {
                    $error = 'Titulo e resumo sao obrigatorios.';
                } else {
                    $slug = ppgcc_notice_unique_slug($title, $id > 0 ? $id : null);
                    $published = $publishedAt !== '' ? str_replace('T', ' ', $publishedAt) . ':00' : date('Y-m-d H:i:s');
                    if ($id > 0) {
                        $stmt = db()->prepare(
                            'UPDATE ppgcc_notices
                             SET slug = :slug, title = :title, summary = :summary, notice_type = :type,
                                 notice_url = :url, is_active = :active, published_at = :published_at
                             WHERE id = :id'
                        );
                        $stmt->execute([
                            ':slug' => $slug,
                            ':title' => $title,
                            ':summary' => $summary,
                            ':type' => $type,
                            ':url' => $url !== '' ? $url : null,
                            ':active' => $isActive,
                            ':published_at' => $published,
                            ':id' => $id,
                        ]);
                        $success = 'Publicacao atualizada.';
                    } else {
                        $stmt = db()->prepare(
                            'INSERT INTO ppgcc_notices (slug, title, summary, notice_type, notice_url, is_active, published_at)
                             VALUES (:slug, :title, :summary, :type, :url, :active, :published_at)'
                        );
                        $stmt->execute([
                            ':slug' => $slug,
                            ':title' => $title,
                            ':summary' => $summary,
                            ':type' => $type,
                            ':url' => $url !== '' ? $url : null,
                            ':active' => $isActive,
                            ':published_at' => $published,
                        ]);
                        $success = 'Publicacao criada.';
                    }
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = db()->prepare('DELETE FROM ppgcc_notices WHERE id = :id AND notice_type = :type');
                    $stmt->execute([':id' => $id, ':type' => $type]);
                    $success = 'Publicacao removida.';
                }
            }
        } catch (Throwable $e) {
            $error = 'Falha ao processar publicacao da pos.';
            error_log('Admin pos-publicacoes error: ' . $e->getMessage());
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM ppgcc_notices WHERE id = :id AND notice_type = :type');
    $stmt->execute([':id' => $editId, ':type' => $type]);
    $editing = $stmt->fetch() ?: null;
}

$stmt = db()->prepare('SELECT id, title, summary, notice_url, is_active, published_at FROM ppgcc_notices WHERE notice_type = :type ORDER BY published_at DESC, id DESC');
$stmt->execute([':type' => $type]);
$items = $stmt->fetchAll() ?: [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - <?= e($typeLabel) ?></title>
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

    <?php render_admin_sidebar($sidebarActive); ?>

    <main class="app-main">
        <div class="app-content-header"><div class="container-fluid"><h3 class="mb-0">Gerenciar <?= e($typeLabel) ?></h3></div></div>
        <div class="app-content"><div class="container-fluid">
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title"><?= $editing ? 'Editar publicacao' : 'Nova publicacao' ?></h3></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? 0)) ?>">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Titulo</label>
                                <input class="form-control" name="title" required value="<?= e((string)($editing['title'] ?? '')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data de publicacao</label>
                                <input class="form-control" type="datetime-local" name="published_at" value="<?= e(isset($editing['published_at']) ? str_replace(' ', 'T', substr((string)$editing['published_at'], 0, 16)) : '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Resumo</label>
                                <textarea class="form-control" name="summary" rows="2" required><?= e((string)($editing['summary'] ?? '')) ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">URL (opcional)</label>
                                <input class="form-control" name="notice_url" value="<?= e((string)($editing['notice_url'] ?? '')) ?>">
                            </div>
                            <div class="col-12">
                                <?php $active = !isset($editing['is_active']) || (int)$editing['is_active'] === 1; ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive"<?= $active ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="isActive">Publicacao ativa</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary" type="submit"><?= $editing ? 'Salvar' : 'Criar' ?></button>
                            <?php if ($editing): ?><a class="btn btn-outline-secondary" href="/admin/pos-publicacoes.php?tipo=<?= e($type === 'edital' ? 'editais' : 'noticias') ?>">Cancelar</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Publicacoes cadastradas</h3></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Titulo</th><th>Publicacao</th><th>Status</th><th class="text-end">Acoes</th></tr></thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td><?= e((string)$it['title']) ?></td>
                                    <td><?= e((string)$it['published_at']) ?></td>
                                    <td><?= (int)$it['is_active'] === 1 ? 'Ativo' : 'Oculto' ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-outline-primary btn-sm" href="/admin/pos-publicacoes.php?tipo=<?= e($type === 'edital' ? 'editais' : 'noticias') ?>&edit=<?= e((string)$it['id']) ?>">Editar</a>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= e((string)$it['id']) ?>">
                                            <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('Excluir esta publicacao?');">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div></div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>
</body>
</html>



