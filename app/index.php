<?php
require __DIR__ . '/includes/config.php';

$news = array_slice(demo_news(), 0, 6);
$editais = array_slice(demo_editais(), 0, 6);
$defesas = demo_defesas();
$jobs = demo_jobs();
$menuGraduacao = primary_menu_item('graduacao');
$menuPosGraduacao = primary_menu_item('pos_graduacao');
$heroSlides = hero_carousel_get();

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
                                    <span class="badge text-bg-light mb-2"><?= e((string)$slide['badge']) ?></span>
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
                                            <a class="btn btn-outline-light" href="/admin/dashboard.php">Area admin</a>
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
                        <h2 class="h5">Acesso rapido</h2>
                        <div class="list-group list-group-flush">
                            <a class="list-group-item list-group-item-action bg-transparent text-white" href="/pessoal/docentes.php">Docentes</a>
                            <a class="list-group-item list-group-item-action bg-transparent text-white" href="/ensino/ciencia-computacao.php">Curso de Ciencia da Computacao</a>
                            <a class="list-group-item list-group-item-action bg-transparent text-white" href="/ensino/inteligencia-artificial.php">Curso de Inteligencia Artificial</a>
                            <a class="list-group-item list-group-item-action bg-transparent text-white" href="<?= e((string)$menuGraduacao['url']) ?>"><?= e((string)$menuGraduacao['label']) ?></a>
                            <a class="list-group-item list-group-item-action bg-transparent text-white" href="<?= e((string)$menuPosGraduacao['url']) ?>"><?= e((string)$menuPosGraduacao['label']) ?></a>
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
            <div class="card shadow-sm mb-4 side-widget">
                <div class="card-body">
                    <h2 class="h5">Acesso do Aluno</h2>
                    <div class="d-grid gap-2">
                        <a class="btn btn-primary btn-sm" href="/pessoal/atendimento-docentes.php">Atendimento Docentes</a>
                        <a class="btn btn-outline-secondary btn-sm" href="/ensino/horarios-de-aula.php">Horarios de Aula</a>
                    </div>
                </div>
            </div>
            <?php foreach (['Editais' => $editais, 'Defesas' => $defesas, 'Estagios e Empregos' => $jobs] as $title => $items): ?>
                <div class="card shadow-sm mb-4 side-widget">
                    <div class="card-body">
                        <h2 class="h5"><?= e($title) ?></h2>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($items as $item): ?>
                                <li class="list-group-item px-0">
                                    <a class="side-widget-link" href="/noticias/ver.php?slug=<?= urlencode($item['slug']) ?>"><?= e($item['title']) ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card news-card mt-4">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h2 class="h4 mb-2">Quero ingressar em Ciencia da Computacao (UFOP)</h2>
                <p class="mb-0 text-muted">
                    Veja um apanhado geral do curso com descricao, eixos da grade curricular, avaliacao no MEC
                    e referencia de nota para ingresso via SISU/ENEM.
                </p>
            </div>
            <a class="btn btn-primary" href="/ensino/ciencia-computacao.php">Ver guia do ingressante</a>
        </div>
    </div>

    <div class="card news-card mt-4">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <h2 class="h4 mb-2">Pos-graduacao em Computacao (PPGCC)</h2>
                <p class="mb-0 text-muted">
                    As noticias e editais da pos agora ficam em paginas separadas, exclusivas da pos-graduacao.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-primary btn-sm" href="/ensino/pos-noticias.php">Noticias da Pos</a>
                <a class="btn btn-outline-danger btn-sm" href="/ensino/pos-editais.php">Editais da Pos</a>
                <a class="btn btn-primary btn-sm" href="/ensino/pos-graduacao.php">Pagina da Pos</a>
            </div>
        </div>
    </div>
</div>
<?php page_footer(); ?>
