<?php
require __DIR__ . '/../includes/config.php';

$slug = isset($_GET['slug']) ? (string)$_GET['slug'] : '';
$context = content_find_news_or_edital_by_slug($slug);
$item = $context['item'] ?? null;
$contentType = (string)($context['content_type'] ?? '');
$contentId = (int)($context['content_id'] ?? 0);

if (!$item && $slug !== '') {
    $item = find_demo_item($slug);
}

if (!$item) {
    http_response_code(404);
    page_header('Conteudo nao encontrado');
    echo '<div class="container py-4"><div class="alert alert-danger">Conteudo nao encontrado.</div></div>';
    page_footer();
    exit;
}

page_header((string)$item['title']);
?>
<div class="container py-4">
    <div class="card shadow-sm overflow-hidden">
        <?php
            $slides = [];
            if (in_array($contentType, ['noticias', 'editais'], true) && $contentId > 0) {
                $slides = content_carousel_images_get($contentType, $contentId);
            }
            if (empty($slides)) {
                $slides[] = ['image_url' => content_image($item), 'caption' => (string)$item['summary']];
            }
            $carouselId = 'newsCarousel-' . preg_replace('/[^a-z0-9_-]/i', '-', (string)($item['slug'] ?? 'item'));
        ?>
        <?php if (!empty($slides)): ?>
            <div id="<?= e($carouselId) ?>" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php foreach ($slides as $i => $slide): ?>
                        <button type="button" data-bs-target="#<?= e($carouselId) ?>" data-bs-slide-to="<?= e((string)$i) ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="Slide <?= e((string)($i + 1)) ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner">
                    <?php foreach ($slides as $i => $slide): ?>
                        <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                            <img
                                class="news-card-cover"
                                style="height:320px;border-radius:0;object-fit:cover"
                                src="<?= e((string)$slide['image_url']) ?>"
                                alt="<?= e((string)$item['title']) ?>"
                            >
                            <?php if (trim((string)($slide['caption'] ?? '')) !== ''): ?>
                                <div class="carousel-caption d-none d-md-block text-start" style="background: rgba(0,0,0,.45); border-radius: 8px; padding: .75rem 1rem;">
                                    <p class="mb-0"><?= e((string)$slide['caption']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($slides) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#<?= e($carouselId) ?>" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#<?= e($carouselId) ?>" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Proximo</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="card-body p-4">
            <div class="d-flex gap-2 flex-wrap mb-3">
                <span class="badge text-bg-primary"><?= e($item['category']) ?></span>
            </div>
            <h1 class="h2"><?= e($item['title']) ?></h1>
            <p class="lead text-secondary"><?= e($item['summary']) ?></p>
            <div><?= render_rich_text((string)$item['content']) ?></div>
        </div>
    </div>
</div>
<?php page_footer(); ?>
