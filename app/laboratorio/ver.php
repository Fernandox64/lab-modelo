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

page_header((string)$item['title']);
?>
<main id="main-content" class="container py-4">
    <div class="card shadow-sm overflow-hidden">
        <?php if (!empty($item['image_url'])): ?>
            <img
                class="news-card-cover"
                style="height:280px;border-radius:0"
                src="<?= e((string)$item['image_url']) ?>"
                alt="<?= e((string)$item['title']) ?>"
            >
        <?php endif; ?>
        <div class="card-body p-4">
            <div class="d-flex gap-2 flex-wrap mb-3">
                <span class="badge text-bg-primary"><?= e((string)$item['category']) ?></span>
                <span class="badge text-bg-secondary"><?= e((string)$item['published_at']) ?></span>
            </div>
            <h1 class="h2"><?= e((string)$item['title']) ?></h1>
            <p class="lead text-secondary"><?= e((string)$item['summary']) ?></p>
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
