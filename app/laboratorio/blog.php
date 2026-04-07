<?php
require __DIR__ . '/../includes/config.php';

$posts = array_slice(demo_news(), 0, 9);

page_header('Blog');
?>
<main id="main-content" class="container py-4">
    <h1 class="section-title h3 mb-3">Blog</h1>
    <p class="text-muted mb-4">Noticias, atualizacoes de pesquisa e textos tecnicos do laboratorio.</p>

    <div class="row g-3">
        <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-xl-4">
                <a class="card card-link news-card h-100" href="/noticias/ver.php?slug=<?= urlencode((string)$post['slug']) ?>">
                    <img class="news-card-cover" src="<?= e(content_image($post)) ?>" alt="<?= e((string)$post['title']) ?>">
                    <div class="card-body">
                        <span class="badge text-bg-primary mb-2"><?= e((string)$post['category']) ?></span>
                        <h2 class="h5 mb-2"><?= e((string)$post['title']) ?></h2>
                        <p class="mb-0 text-muted"><?= e((string)$post['summary']) ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php page_footer(); ?>
