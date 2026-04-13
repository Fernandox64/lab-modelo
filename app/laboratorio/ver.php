<?php
require __DIR__ . '/../includes/config.php';

$pageSlug = laboratory_page_slug_normalize((string)($_GET['pagina'] ?? 'projetos'));
$itemSlug = trim((string)($_GET['slug'] ?? ''));
$item = $itemSlug !== '' ? laboratory_page_item_find($pageSlug, $itemSlug) : null;

if (!$item) {
    http_response_code(404);
    page_header('Conteudo nao encontrado');
    echo '<div class="container py-4"><div class="alert alert-danger">Conteudo nao encontrado.</div></div>';
    page_footer();
    exit;
}

$carouselPages = ['projetos', 'publicacoes', 'cursos', 'parceiros', 'tutoriais', 'blog', 'eventos'];
$useCarousel = in_array($pageSlug, $carouselPages, true);
$carouselSlides = [];

$mainImage = trim((string)($item['image_url'] ?? ''));
if ($mainImage !== '') {
    $carouselSlides[] = $mainImage;
}

$contentHtml = (string)($item['content_html'] ?? '');
if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $contentHtml, $matches)) {
    foreach (($matches[1] ?? []) as $src) {
        $src = trim((string)$src);
        if ($src === '' || in_array($src, $carouselSlides, true)) {
            continue;
        }
        $carouselSlides[] = $src;
    }
}

$carouselId = 'postCarousel-' . preg_replace('/[^a-z0-9_-]/i', '-', (string)($item['slug'] ?? 'item'));

page_header((string)$item['title']);
?>
<main id="main-content" class="container py-4">
    <div class="card shadow-sm overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex gap-2 flex-wrap mb-3">
                <span class="badge text-bg-primary"><?= e((string)$item['category']) ?></span>
                <span class="badge text-bg-secondary"><?= e((string)$item['published_at']) ?></span>
            </div>
            <h1 class="h2"><?= e((string)$item['title']) ?></h1>
            <p class="lead text-secondary"><?= e((string)$item['summary']) ?></p>

            <?php if ($useCarousel && !empty($carouselSlides)): ?>
                <div id="<?= e($carouselId) ?>" class="carousel slide mb-4 shadow-sm" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <?php foreach ($carouselSlides as $i => $slideImage): ?>
                            <button type="button" data-bs-target="#<?= e($carouselId) ?>" data-bs-slide-to="<?= e((string)$i) ?>"<?= $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="Slide <?= e((string)($i + 1)) ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-inner rounded">
                        <?php foreach ($carouselSlides as $i => $slideImage): ?>
                            <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                                <img src="<?= e((string)$slideImage) ?>" class="d-block w-100" alt="<?= e((string)$item['title']) ?>" style="height: 420px; object-fit: cover;">
                                <div class="carousel-caption d-none d-md-block text-start" style="background: rgba(0,0,0,0.45); border-radius: 8px; padding: .85rem 1rem;">
                                    <h5 class="mb-1"><?= e((string)$item['title']) ?></h5>
                                    <?php if ($i === 0): ?>
                                        <p class="mb-0"><?= e((string)$item['summary']) ?></p>
                                    <?php else: ?>
                                        <p class="mb-0">Imagem <?= e((string)($i + 1)) ?> do post</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($carouselSlides) > 1): ?>
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
            <?php elseif (!empty($item['image_url'])): ?>
                <img
                    class="news-card-cover mb-4"
                    style="height:280px;border-radius:.5rem"
                    src="<?= e((string)$item['image_url']) ?>"
                    alt="<?= e((string)$item['title']) ?>"
                >
            <?php endif; ?>

            <div><?= render_rich_text((string)$item['content_html']) ?></div>
            <?php if (!empty($item['external_url'])): ?>
                <div class="mt-3">
                    <a class="btn btn-outline-primary" href="<?= e((string)$item['external_url']) ?>" target="_blank" rel="noopener">Acessar link externo</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php page_footer(); ?>
