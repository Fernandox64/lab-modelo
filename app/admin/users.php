<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin_permission('manage_users');
ensure_admin_users_table();
ensure_default_admin_user();

$error = null;
$success = null;
$current = admin_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'create') {
                $name = trim((string)($_POST['name'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));
                $password = (string)($_POST['password'] ?? '');
                $role = admin_normalize_role((string)($_POST['role'] ?? 'editor'));

                if ($name === '' || $email === '' || $password === '') {
                    throw new RuntimeException('Nome, e-mail e senha sÃƒÆ’Ã‚Â£o obrigatÃƒÆ’Ã‚Â³rios.');
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Informe um e-mail vÃƒÆ’Ã‚Â¡lido.');
                }
                $passwordError = admin_validate_password_strength($password);
                if ($passwordError !== null) {
                    throw new RuntimeException($passwordError);
                }

                $stmt = db()->prepare(
                    'INSERT INTO admin_users (name, email, password_hash, role, is_active)
                     VALUES (:name, :email, :password_hash, :role, 1)'
                );
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':role' => $role,
                ]);
                admin_audit_log('admin_user_create', ['email' => $email, 'role' => $role], 'admin_users');
                $success = 'Conta criada com sucesso.';
            }

            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));
                $role = admin_normalize_role((string)($_POST['role'] ?? 'editor'));
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if ($id <= 0 || $name === '' || $email === '') {
                    throw new RuntimeException('Dados invÃƒÆ’Ã‚Â¡lidos para atualizaÃƒÆ’Ã‚Â§ÃƒÆ’Ã‚Â£o.');
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Informe um e-mail vÃƒÆ’Ã‚Â¡lido.');
                }
                if ((int)$current['id'] === $id && $isActive !== 1) {
                    throw new RuntimeException('VocÃƒÆ’Ã‚Âª nÃƒÆ’Ã‚Â£o pode desativar sua prÃƒÆ’Ã‚Â³pria conta.');
                }

                $stmt = db()->prepare(
                    'UPDATE admin_users
                     SET name = :name, email = :email, role = :role, is_active = :is_active
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':role' => $role,
                    ':is_active' => $isActive,
                    ':id' => $id,
                ]);
                admin_audit_log('admin_user_update', ['id' => $id, 'email' => $email, 'role' => $role, 'is_active' => $isActive], 'admin_users');
                $success = 'Conta atualizada com sucesso.';
            }

            if ($action === 'reset_password') {
                $id = (int)($_POST['id'] ?? 0);
                $newPassword = (string)($_POST['new_password'] ?? '');
                if ($id <= 0) {
                    throw new RuntimeException('Conta invÃƒÆ’Ã‚Â¡lida para redefiniÃƒÆ’Ã‚Â§ÃƒÆ’Ã‚Â£o de senha.');
                }
                $passwordError = admin_validate_password_strength($newPassword);
                if ($passwordError !== null) {
                    throw new RuntimeException($passwordError);
                }
                $stmt = db()->prepare(
                    'UPDATE admin_users SET password_hash = :password_hash WHERE id = :id'
                );
                $stmt->execute([
                    ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    ':id' => $id,
                ]);
                admin_audit_log('admin_user_reset_password', ['id' => $id], 'admin_users');
                $success = 'Senha redefinida com sucesso.';
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$users = db()->query(
    'SELECT id, name, email, role, is_active, last_login_at, created_at
     FROM admin_users
     ORDER BY id ASC'
)->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - UsuÃƒÆ’Ã‚Â¡rios</title>
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
    <?php render_admin_sidebar('users'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <h3 class="mb-0">Gerenciar UsuÃƒÆ’Ã‚Â¡rios e PermissÃƒÆ’Ã‚Âµes</h3>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Nova conta</h3></div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="create">
                            <div class="col-md-4">
                                <label class="form-label">Nome</label>
                                <input class="form-control" name="name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">E-mail</label>
                                <input class="form-control" type="email" name="email" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Perfil</label>
                                <select class="form-select" name="role">
                                    <option value="editor">editor</option>
                                    <option value="secretaria">secretaria</option>
                                    <option value="superadmin">superadmin</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Senha inicial</label>
                                <input class="form-control" type="password" name="password" required minlength="10">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Criar conta</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Contas cadastradas</h3></div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>ÃƒÆ’Ã…Â¡ltimo login</th>
                                <th>Criado em</th>
                                <th class="text-end">AÃƒÆ’Ã‚Â§ÃƒÆ’Ã‚Âµes</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= e((string)$u['id']) ?></td>
                                    <td><?= e((string)$u['name']) ?></td>
                                    <td><?= e((string)$u['email']) ?></td>
                                    <td><span class="badge text-bg-info"><?= e((string)$u['role']) ?></span></td>
                                    <td>
                                        <?php if ((int)$u['is_active'] === 1): ?>
                                            <span class="badge text-bg-success">ativo</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e((string)($u['last_login_at'] ?? '-')) ?></td>
                                    <td><?= e((string)$u['created_at']) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editUser<?= e((string)$u['id']) ?>">Editar</button>
                                        <button class="btn btn-outline-warning btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#resetPwd<?= e((string)$u['id']) ?>">Senha</button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="editUser<?= e((string)$u['id']) ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?= e((string)$u['id']) ?>">
                                                <div class="modal-header"><h5 class="modal-title">Editar usuÃƒÆ’Ã‚Â¡rio</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body">
                                                    <div class="mb-2">
                                                        <label class="form-label">Nome</label>
                                                        <input class="form-control" name="name" value="<?= e((string)$u['name']) ?>" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">E-mail</label>
                                                        <input class="form-control" type="email" name="email" value="<?= e((string)$u['email']) ?>" required>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">Perfil</label>
                                                        <select class="form-select" name="role">
                                                            <?php foreach (admin_roles() as $role): ?>
                                                                <option value="<?= e($role) ?>"<?= (string)$u['role'] === $role ? ' selected' : '' ?>><?= e($role) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_active" id="active<?= e((string)$u['id']) ?>"<?= (int)$u['is_active'] === 1 ? ' checked' : '' ?>>
                                                        <label class="form-check-label" for="active<?= e((string)$u['id']) ?>">Conta ativa</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="resetPwd<?= e((string)$u['id']) ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="id" value="<?= e((string)$u['id']) ?>">
                                                <div class="modal-header"><h5 class="modal-title">Redefinir senha</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body">
                                                    <label class="form-label">Nova senha (min. 10, maiuscula, minuscula, numero e especial)</label>
                                                    <input class="form-control" type="password" name="new_password" minlength="10" required>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-warning">Atualizar senha</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
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


