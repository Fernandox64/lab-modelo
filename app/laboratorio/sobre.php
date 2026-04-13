<?php
require __DIR__ . '/../includes/config.php';

$content = laboratory_about_get();
$slides = laboratory_about_carousel_get();
page_header($content['title']);
?>
<main id="main-content" class="container py-4">
    <h1 class="section-title h3 mb-3"><?= e((string)$content['title']) ?></h1>
    <p class="lead mb-3"><?= e((string)$content['summary']) ?></p>
    <?php if (!empty($slides)): ?>
        <div id="labAboutCarousel" class="carousel slide mb-4 shadow-sm" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <?php foreach ($slides as $i => $slide): ?>
                    <button type="button" data-bs-target="#labAboutCarousel" data-bs-slide-to="<?= e((string)$i) ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="Slide <?= e((string)($i + 1)) ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-inner rounded">
                <?php foreach ($slides as $i => $slide): ?>
                    <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                        <img src="<?= e((string)$slide['image']) ?>" class="d-block w-100" alt="<?= e((string)($slide['title'] !== '' ? $slide['title'] : 'Slide do laboratorio')) ?>" style="height: 420px; object-fit: cover;">
                        <?php if (trim((string)($slide['title'] ?? '')) !== '' || trim((string)($slide['caption'] ?? '')) !== ''): ?>
                            <div class="carousel-caption d-none d-md-block text-start" style="background: rgba(0,0,0,0.45); border-radius: 8px; padding: .85rem 1rem;">
                                <?php if (trim((string)($slide['title'] ?? '')) !== ''): ?><h5 class="mb-1"><?= e((string)$slide['title']) ?></h5><?php endif; ?>
                                <?php if (trim((string)($slide['caption'] ?? '')) !== ''): ?><p class="mb-0"><?= e((string)$slide['caption']) ?></p><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#labAboutCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#labAboutCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Proximo</span>
            </button>
        </div>
    <?php endif; ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <?= render_rich_text((string)$content['content_html']) ?>
        </div>
    </div>
</main>
<?php page_footer(); ?>
