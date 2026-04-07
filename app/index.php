<?php
require __DIR__ . '/includes/config.php';

$news = array_slice(demo_news(), 0, 6);
$heroSlides = hero_carousel_get();
$topbarDepartmentName = trim(site_setting_get('topbar_department_name', 'Departamento Exemplo'));
if ($topbarDepartmentName === '') {
    $topbarDepartmentName = 'Departamento Exemplo';
}

page_header('Inicio');
?>
<section class="hero py-5">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <div id="heroCarousel" class="carousel slide hero-carousel shadow-lg" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <?php foreach ($heroSlides as $idx => $slide): ?>
                            <button
                                type="button"
                                data-bs-target="#heroCarousel"
                                data-bs-slide-to="<?= e((string)$idx) ?>"
                                class="<?= $idx === 0 ? 'active' : '' ?>"
                                <?= $idx === 0 ? 'aria-current="true"' : '' ?>
                                aria-label="Slide <?= e((string)($idx + 1)) ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
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
                                    <?php if ($idx === 0): ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-light" href="/noticias/index.php">Ultimas noticias</a>
                                            <a class="btn btn-outline-light" href="/noticias/editais.php">Editais</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Proximo</span>
                    </button>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Laboratorio vinculado ao <?= e($topbarDepartmentName) ?></h2>
                        <p class="mb-3 text-white-50">Modelo de site para laboratorio de pesquisa em universidade federal.</p>
                        <div class="list-group list-group-flush">
                            <a class="list-group-item list-group-item-action bg-transparent text-white" href="/laboratorio/equipe.php">Equipe do laboratorio</a>
                            <a class="list-group-item list-group-item-action bg-transparent text-white" href="/laboratorio/publicacoes.php">Publicacoes recentes</a>
                            <a class="list-group-item list-group-item-action bg-transparent text-white" href="/noticias/index.php">Noticias e comunicados</a>
                            <a class="list-group-item list-group-item-action bg-transparent text-white" href="/contato/index.php">Contato institucional</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <h2 class="section-title h4 mb-3">Noticias</h2>
            <div class="row g-3">
                <?php foreach ($news as $item): ?>
                    <div class="col-md-6">
                        <a class="card card-link h-100 shadow-sm overflow-hidden" href="/noticias/ver.php?slug=<?= urlencode($item['slug']) ?>">
                            <img class="news-card-cover" src="<?= e(content_image($item)) ?>" alt="<?= e($item['title']) ?>">
                            <div class="card-body">
                                <span class="badge text-bg-primary"><?= e($item['category']) ?></span>
                                <h3 class="h5 mt-2"><?= e($item['title']) ?></h3>
                                <p class="text-muted mb-0"><?= e($item['summary']) ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Sobre o laboratorio</h2>
                    <p class="mb-2">Este modelo foi preparado para apresentar o laboratorio, equipe, producao cientifica e comunicados.</p>
                    <p class="mb-0 text-muted">A estrutura pode ser usada por qualquer laboratorio vinculado a departamento de universidade federal.</p>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Acesso rapido</h2>
                    <div class="d-grid gap-2">
                        <a class="btn btn-primary btn-sm" href="/laboratorio/equipe.php">Equipe</a>
                        <a class="btn btn-outline-primary btn-sm" href="/laboratorio/publicacoes.php">Publicacoes</a>
                        <a class="btn btn-outline-secondary btn-sm" href="/contato/index.php">Contato</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php page_footer(); ?>
