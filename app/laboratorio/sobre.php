<?php
require __DIR__ . '/../includes/config.php';

$content = laboratory_about_get();
page_header($content['title']);
?>
<main id="main-content" class="container py-4">
    <h1 class="section-title h3 mb-3"><?= e((string)$content['title']) ?></h1>
    <p class="lead mb-3"><?= e((string)$content['summary']) ?></p>
    <div class="card shadow-sm">
        <div class="card-body">
            <?= render_rich_text((string)$content['content_html']) ?>
        </div>
    </div>
</main>
<?php page_footer(); ?>
