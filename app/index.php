<?php
require __DIR__ . '/includes/config.php';

$heroSlides = hero_carousel_get();
$news = array_slice(demo_news(), 0, 6);

page_header('Inicio');
?>
<section class="hero py-5">
    <div class="container">
        <div id="heroCarousel" class="carousel slide hero-carousel shadow-lg" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="false" data-bs-wrap="true">
            <div class="carousel-inner rounded-4 overflow-hidden">
                <?php foreach ($heroSlides as $idx => $slide): ?>
                    <div class="carousel-item<?= $idx === 0 ? ' active' : '' ?>">
                        <img src="<?= e((string)$slide['image']) ?>" class="d-block w-100 hero-slide-image" alt="<?= e((string)$slide['title']) ?>">
                        <div class="carousel-caption text-start">
                            <span class="badge hero-badge mb-2"><?= e((string)$slide['badge']) ?></span>
                            <?php if ($idx === 0): ?>
                                <h1 class="display-6 fw-bold mb-2"><?= e((string)$slide['title']) ?></h1>
                            <?php else: ?>
                                <h2 class="h2 fw-bold mb-2"><?= e((string)$slide['title']) ?></h2>
                            <?php endif; ?>
                            <p class="lead mb-<?= $idx === 0 ? '3' : '0' ?>"><?= e((string)$slide['text']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($heroSlides) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" aria-label="Slide anterior">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" aria-label="Proximo slide">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Proximo</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container py-4">
    <section class="mb-5">
        <div class="mb-3">
            <h2 class="section-title h4 mb-0">Noticias</h2>
        </div>
        <div class="row g-3">
            <?php foreach ($news as $item): ?>
                <div class="col-md-6 col-xl-4">
                    <a class="card card-link h-100 shadow-sm overflow-hidden" href="/noticias/ver.php?slug=<?= urlencode((string)$item['slug']) ?>">
                        <img class="news-card-cover" src="<?= e(content_image($item)) ?>" alt="<?= e((string)$item['title']) ?>">
                        <div class="card-body d-flex flex-column">
                            <span class="badge text-bg-primary"><?= e((string)$item['category']) ?></span>
                            <h3 class="h5 mt-2"><?= e((string)$item['title']) ?></h3>
                            <p class="text-muted mb-2"><?= e((string)$item['summary']) ?></p>
                            <span class="news-cta mt-auto">Ler mais</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php page_footer(); ?>
