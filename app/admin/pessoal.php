<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_people');
ensure_people_items_scope_column();

function people_slugify(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? substr($text, 0, 140) : 'pessoa-' . bin2hex(random_bytes(4));
}

function people_unique_slug(PDO $pdo, string $baseSlug, string $scope, ?int $ignoreId = null): string {
    $slug = $baseSlug;
    $i = 1;
    while (true) {
        $sql = 'SELECT id FROM people_items WHERE slug = :slug AND scope = :scope';
        $params = [':slug' => $slug, ':scope' => $scope];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $i++;
        $slug = $baseSlug . '-' . $i;
    }
}

$pdo = db();
$error = null;
$success = null;
$editing = null;
$scope = people_scope_normalize((string)($_GET['scope'] ?? ($_POST['scope'] ?? 'principal')));
$scopeLabel = people_scope_label($scope);
$isPosScope = $scope === 'pos';
$sidebarActive = $isPosScope ? 'pos_docentes' : 'pessoal';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare('DELETE FROM people_items WHERE id = :id AND scope = :scope');
            $stmt->execute([':id' => $id, ':scope' => $scope]);
            $success = 'Pessoa removida com sucesso.';
        }

        if ($action === 'save') {
            $name = trim((string)($_POST['name'] ?? ''));
            $roleType = (string)($_POST['role_type'] ?? 'docente');
            $position = trim((string)($_POST['position'] ?? ''));
            $degree = trim((string)($_POST['degree'] ?? ''));
            $website = trim((string)($_POST['website_url'] ?? ''));
            $lattes = trim((string)($_POST['lattes_url'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $room = trim((string)($_POST['room'] ?? ''));
            $photoUrl = trim((string)($_POST['photo_url'] ?? ''));
            $interests = trim((string)($_POST['interests'] ?? ''));
            $bio = trim((string)($_POST['bio'] ?? ''));
            $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
            $slugInput = trim((string)($_POST['slug'] ?? ''));

            if (!in_array($roleType, ['docente', 'funcionario'], true)) {
                $roleType = 'docente';
            }

            if ($name === '' || $position === '') {
                $error = 'Nome e cargo/filiacao sao obrigatorios.';
            } else {
                $baseSlug = $slugInput !== '' ? people_slugify($slugInput) : people_slugify($name);
                $slug = people_unique_slug($pdo, $baseSlug, $scope, $id > 0 ? $id : null);

                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE people_items
                         SET slug = :slug, scope = :scope, role_type = :role_type, name = :name, position = :position, degree = :degree,
                             website_url = :website_url, lattes_url = :lattes_url, email = :email, phone = :phone, room = :room,
                             photo_url = :photo_url, interests = :interests, bio = :bio, sort_order = :sort_order
                         WHERE id = :id AND scope = :scope_where'
                    );
                    $stmt->execute([
                        ':slug' => $slug,
                        ':scope' => $scope,
                        ':role_type' => $roleType,
                        ':name' => $name,
                        ':position' => $position,
                        ':degree' => $degree,
                        ':website_url' => $website,
                        ':lattes_url' => $lattes,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':room' => $room,
                        ':photo_url' => $photoUrl,
                        ':interests' => $interests,
                        ':bio' => $bio,
                        ':sort_order' => $sortOrder,
                        ':id' => $id,
                        ':scope_where' => $scope,
                    ]);
                    $success = 'Cadastro atualizado com sucesso.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO people_items (slug, scope, role_type, name, position, degree, website_url, lattes_url, email, phone, room, photo_url, interests, bio, sort_order)
                         VALUES (:slug, :scope, :role_type, :name, :position, :degree, :website_url, :lattes_url, :email, :phone, :room, :photo_url, :interests, :bio, :sort_order)'
                    );
                    $stmt->execute([
                        ':slug' => $slug,
                        ':scope' => $scope,
                        ':role_type' => $roleType,
                        ':name' => $name,
                        ':position' => $position,
                        ':degree' => $degree,
                        ':website_url' => $website,
                        ':lattes_url' => $lattes,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':room' => $room,
                        ':photo_url' => $photoUrl,
                        ':interests' => $interests,
                        ':bio' => $bio,
                        ':sort_order' => $sortOrder,
                    ]);
                    $success = 'Pessoa adicionada com sucesso.';
                }
            }
        }
    }
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM people_items WHERE id = :id AND scope = :scope');
    $stmt->execute([':id' => $editId, ':scope' => $scope]);
    $editing = $stmt->fetch();
}

$stmt = $pdo->prepare('SELECT id, slug, scope, role_type, name, position, email, phone, photo_url, sort_order FROM people_items WHERE scope = :scope ORDER BY role_type ASC, sort_order ASC, name ASC');
$stmt->execute([':scope' => $scope]);
$items = $stmt->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Pessoal <?= e($scopeLabel) ?></title>
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
        <div class="app-content-header">
            <div class="container-fluid">
                <h3 class="mb-0">Gerenciar Pessoal - <?= e($scopeLabel) ?></h3>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title"><?= $editing ? 'Editar pessoa' : 'Adicionar pessoa' ?></h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="scope" value="<?= e($scope) ?>">
                            <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? '0')) ?>">

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Nome</label>
                                    <input class="form-control" name="name" required value="<?= e((string)($editing['name'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tipo</label>
                                    <?php $selectedRole = (string)($editing['role_type'] ?? 'docente'); ?>
                                    <select class="form-select" name="role_type">
                                        <option value="docente"<?= $selectedRole === 'docente' ? ' selected' : '' ?>>Docente</option>
                                        <option value="funcionario"<?= $selectedRole === 'funcionario' ? ' selected' : '' ?>>Funcionario</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Cargo / Filia&ccedil;&atilde;o</label>
                                    <input class="form-control" name="position" required value="<?= e((string)($editing['position'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ordem</label>
                                    <input class="form-control" type="number" name="sort_order" value="<?= e((string)($editing['sort_order'] ?? '0')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Titulacao</label>
                                    <input class="form-control" name="degree" value="<?= e((string)($editing['degree'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Slug (opcional)</label>
                                    <input class="form-control" name="slug" value="<?= e((string)($editing['slug'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Site</label>
                                    <input class="form-control" name="website_url" placeholder="https://..." value="<?= e((string)($editing['website_url'] ?? '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Curriculo Lattes</label>
                                    <input class="form-control" name="lattes_url" placeholder="https://lattes.cnpq.br/..." value="<?= e((string)($editing['lattes_url'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">E-mail</label>
                                    <input class="form-control" type="email" name="email" value="<?= e((string)($editing['email'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Telefone</label>
                                    <input class="form-control" name="phone" value="<?= e((string)($editing['phone'] ?? '')) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sala / Local</label>
                                    <input class="form-control" name="room" value="<?= e((string)($editing['room'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Foto (URL)</label>
                                    <input class="form-control" name="photo_url" placeholder="https://... ou /assets/pessoal/foto.jpg" value="<?= e((string)($editing['photo_url'] ?? '')) ?>">
                                    <small class="text-muted">A foto do docente fica clicavel e pode abrir o Curriculo Lattes.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Areas de interesse</label>
                                    <textarea class="form-control" rows="3" name="interests"><?= e((string)($editing['interests'] ?? '')) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Descricao curta</label>
                                    <textarea class="form-control" rows="2" name="bio"><?= e((string)($editing['bio'] ?? '')) ?></textarea>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-primary" type="submit"><?= $editing ? 'Salvar alteracoes' : 'Adicionar pessoa' ?></button>
                                <?php if ($editing): ?><a class="btn btn-outline-secondary" href="/admin/pessoal.php?scope=<?= e($scope) ?>">Cancelar</a><?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Cadastros de <?= e($scopeLabel) ?> (<?= e((string)count($items)) ?>)</h3></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Escopo</th>
                                    <th>Tipo</th>
                                    <th>Nome</th>
                                    <th>Cargo</th>
                                    <th>Contato</th>
                                    <th>Ordem</th>
                                    <th class="text-end">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $row): ?>
                                    <tr>
                                        <td><?= e((string)$row['id']) ?></td>
                                        <td><?= e(people_scope_label((string)$row['scope'])) ?></td>
                                        <td><?= e((string)$row['role_type']) ?></td>
                                        <td><?= e((string)$row['name']) ?></td>
                                        <td><?= e((string)$row['position']) ?></td>
                                        <td><?= e((string)($row['email'] ?: $row['phone'])) ?></td>
                                        <td><?= e((string)$row['sort_order']) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-outline-primary btn-sm" href="/admin/pessoal.php?scope=<?= e($scope) ?>&edit=<?= e((string)$row['id']) ?>">Editar</a>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="scope" value="<?= e($scope) ?>">
                                                <input type="hidden" name="id" value="<?= e((string)$row['id']) ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Excluir este cadastro?');">Excluir</button>
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
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>
</body>
</html>



