<?php
require __DIR__ . '/../includes/config.php';

$content = laboratory_contact_get();

page_header((string)$content['title']);
?>
<main id="main-content" class="container py-4">
    <h1 class="section-title h3 mb-3"><?= e((string)$content['title']) ?></h1>
    <p class="text-muted mb-4"><?= e((string)$content['summary']) ?></p>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Informacoes de contato</h2>
                    <p class="mb-2"><strong>E-mail:</strong> <?= e((string)$content['email']) ?></p>
                    <p class="mb-2"><strong>Telefone:</strong> <?= e((string)$content['phone']) ?></p>
                    <p class="mb-0"><strong>Endereco:</strong> <?= e((string)$content['address']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?= render_rich_text((string)$content['content_html']) ?>
                </div>
            </div>
        </div>
    </div>
</main>
<?php page_footer(); ?>

