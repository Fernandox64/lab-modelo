<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

require_admin();

function carousel_store_uploaded_image(array $file, int $slide): string {
    $errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Falha no upload da imagem do slide {$slide}.");
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);
    if ($tmp === '' || !is_uploaded_file($tmp) || $size <= 0 || $size > 8 * 1024 * 1024) {
        throw new RuntimeException("Arquivo invalido no slide {$slide}. Use imagens ate 8MB.");
    }

    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false) {
        throw new RuntimeException("O arquivo do slide {$slide} nao e uma imagem valida.");
    }

    $mime = strtolower((string)($imageInfo['mime'] ?? ''));
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($extMap[$mime])) {
        throw new RuntimeException("Formato nao suportado no slide {$slide}. Use JPG, PNG, GIF ou WEBP.");
    }

    $relativeDir = '/assets/images/carousel/uploads';
    $absoluteDir = __DIR__ . '/../assets/images/carousel/uploads';
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        throw new RuntimeException('Nao foi possivel criar a pasta de upload do carrossel.');
    }

    $filename = 'slide_' . $slide . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extMap[$mime];
    $destination = $absoluteDir . '/' . $filename;
    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException("Nao foi possivel salvar a imagem do slide {$slide}.");
    }

    return $relativeDir . '/' . $filename;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Token CSRF invalido.';
    } else {
        try {
            $slideIndex = (int)($_POST['slide_index'] ?? 0);
            if ($slideIndex < 1 || $slideIndex > 3) {
                throw new RuntimeException('Slide invalido para atualizacao.');
            }

            $slides = hero_carousel_get();
            $currentSlide = $slides[$slideIndex - 1] ?? ['image' => '', 'badge' => '', 'title' => '', 'text' => ''];
            $image = trim((string)($currentSlide['image'] ?? ''));
            $uploadField = "slide_{$slideIndex}_upload";
            if (isset($_FILES[$uploadField]) && is_array($_FILES[$uploadField])) {
                $uploadError = (int)($_FILES[$uploadField]['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                    $image = carousel_store_uploaded_image($_FILES[$uploadField], $slideIndex);
                }
            }
            $slides[$slideIndex - 1] = [
                'image' => $image,
                'badge' => (string)($_POST["slide_{$slideIndex}_badge"] ?? ''),
                'title' => (string)($_POST["slide_{$slideIndex}_title"] ?? ''),
                'text' => (string)($_POST["slide_{$slideIndex}_text"] ?? ''),
            ];

            hero_carousel_save($slides);
            $success = "Slide {$slideIndex} atualizado com sucesso.";
        } catch (Throwable $e) {
            $error = 'Falha ao salvar configuracao do carrossel.';
            error_log('Admin carousel error: ' . $e->getMessage());
        }
    }
}

$slides = hero_carousel_get();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Carrossel da Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">
    <style>
        .carousel-upload-preview {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: .5rem;
            border: 1px solid #ced4da;
            background: #f8f9fa;
        }
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
                    <li class="nav-item"><a href="/admin/carousel.php" class="nav-link active"><p>Carrossel Home</p></a></li>
                    <li class="nav-item"><a href="/admin/horarios.php" class="nav-link"><p>Horarios de Aula</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-graduacao.php" class="nav-link"><p>Pos-graduacao</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-publicacoes.php?tipo=noticias" class="nav-link"><p>Noticias/Editais Pos</p></a></li>
                    <li class="nav-item"><a href="/admin/pos-subsite.php" class="nav-link"><p>Subsite Pos</p></a></li>
                    <li class="nav-item"><a href="/health.php" class="nav-link" target="_blank" rel="noopener"><p>Health</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Editar Carrossel da Home</h3>
                    <a class="btn btn-dark btn-sm" href="/" target="_blank" rel="noopener">Ver Home</a>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Instrucoes</h3></div>
                    <div class="card-body">
                        <p class="mb-2"><i class="bi bi-upload me-1"></i>Agora voce pode fazer upload direto da imagem no painel.</p>
                        <p class="mb-2 text-muted">Formatos aceitos: JPG, PNG, GIF e WEBP. Tamanho maximo: 8MB por slide.</p>
                        <p class="mb-0 text-muted">A imagem e atualizada por upload e mantida automaticamente ate novo envio.</p>
                    </div>
                </div>

                <div class="accordion mb-4" id="ajudaCarrossel">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ajudaCarrosselConteudo" aria-expanded="false" aria-controls="ajudaCarrosselConteudo">
                                Ajuda rapida para imagens do carrossel
                            </button>
                        </h2>
                        <div id="ajudaCarrosselConteudo" class="accordion-collapse collapse" data-bs-parent="#ajudaCarrossel">
                            <div class="accordion-body">
                                <ol class="mb-0">
                                    <li>Escolha uma imagem no campo "Upload de imagem" do slide.</li>
                                    <li>Veja o preview para confirmar se esta correta.</li>
                                    <li>Clique em "Salvar este slide" para atualizar apenas um por vez.</li>
                                    <li>Use "Ver Home" para validar o resultado final.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <?php $s = $slides[$i - 1] ?? ['image' => '', 'badge' => '', 'title' => '', 'text' => '']; ?>
                    <form method="post" enctype="multipart/form-data" class="card mb-4">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="slide_index" value="<?= e((string)$i) ?>">
                        <div class="card-header"><h3 class="card-title">Slide <?= e((string)$i) ?></h3></div>
                        <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Preview</label>
                                        <img
                                            id="slide-preview-<?= e((string)$i) ?>"
                                            class="carousel-upload-preview"
                                            src="<?= e((string)$s['image'] !== '' ? (string)$s['image'] : '/assets/images/carousel/tech-circuit.jpg') ?>"
                                            alt="Preview do slide <?= e((string)$i) ?>"
                                        >
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Upload de imagem</label>
                                        <input
                                            class="form-control js-slide-upload"
                                            type="file"
                                            accept=".jpg,.jpeg,.png,.gif,.webp,image/*"
                                            name="slide_<?= e((string)$i) ?>_upload"
                                            data-preview-id="slide-preview-<?= e((string)$i) ?>"
                                        >
                                        <small class="text-muted">Opcional: se enviar arquivo, ele substitui o caminho informado abaixo.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Subtitulo</label>
                                        <input class="form-control" name="slide_<?= e((string)$i) ?>_badge" value="<?= e((string)$s['badge']) ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Titulo</label>
                                        <input class="form-control" name="slide_<?= e((string)$i) ?>_title" value="<?= e((string)$s['title']) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Texto</label>
                                        <textarea class="form-control" rows="3" name="slide_<?= e((string)$i) ?>_text"><?= e((string)$s['text']) ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary" type="submit">Salvar este slide</button>
                                    </div>
                                </div>
                        </div>
                    </form>
                <?php endfor; ?>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/js/adminlte.min.js"></script>
<script>
    document.querySelectorAll('.js-slide-upload').forEach(function (input) {
        input.addEventListener('change', function () {
            var previewId = input.getAttribute('data-preview-id');
            var preview = document.getElementById(previewId);
            if (!preview || !input.files || !input.files[0]) {
                return;
            }
            var file = input.files[0];
            var url = URL.createObjectURL(file);
            preview.src = url;
            preview.onload = function () { URL.revokeObjectURL(url); };
        });
    });
</script>
</body>
</html>
