<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

if (is_admin_logged_in()) {
    redirect('/admin/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Sessao expirada. Tente novamente.';
    } elseif (admin_is_login_locked()) {
        $error = 'Muitas tentativas. Aguarde alguns minutos.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $remember = isset($_POST['remember_me']);

        if (admin_is_rate_limited($email)) {
            admin_register_login_failure($email, 'rate_limited');
            $error = 'Muitas tentativas em curto periodo. Aguarde 15 minutos e tente novamente.';
        } elseif (admin_login($email, $password)) {
            if ($remember) {
                admin_enable_remember_me();
            }
            redirect('/admin/dashboard.php');
        } else {
            admin_register_login_failure($email, 'invalid_credentials');
            $error = 'Credenciais invalidas.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
    <style>
        body.login-page {
            background-image:
                linear-gradient(rgba(17, 31, 162, 0.62), rgba(17, 31, 162, 0.62)),
                url('/assets/images/login-bg-ufop-2.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .login-box .card {
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.28);
            border-radius: 14px;
            overflow: hidden;
        }
        .admin-login-brand {
            font-family: "Trebuchet MS", "Segoe UI", Arial, sans-serif;
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: .02em;
            color: #1b2a4a;
            line-height: 1.1;
        }
    </style>
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="/" class="admin-login-brand text-decoration-none">Painel Administrativo</a>
        </div>
        <div class="card-body login-card-body">
            <p class="login-box-msg">Entre para gerenciar noticias e editais</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="input-group mb-3">
                    <input type="email" class="form-control" name="email" placeholder="E-mail">
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Senha">
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember_me">
                    <label class="form-check-label" for="remember_me">Lembrar senha por 30 dias</label>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <a href="/" class="btn btn-outline-secondary w-100">Voltar ao site principal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>
</body>
</html>

