<?php
require __DIR__ . '/../includes/config.php';

ensure_ppgcc_tables();
$pages = ppgcc_pages_list(true);
$pesquisa = ppgcc_section_get('pesquisa');
$extensao = ppgcc_section_get('extensao');

page_header('Subsite da Pos-graduacao');
?>
<div class="container py-4">
    <h1 class="section-title h3 mb-3">Subsite da Pos-graduacao</h1>
    <p class="text-muted mb-4">Area dedicada a paginas institucionais, noticias e editais da pos-graduacao do departamento.</p>

    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2">
            <a class="btn btn-primary btn-sm" href="/pos/noticias.php">Noticias da Pos</a>
            <a class="btn btn-danger btn-sm" href="/pos/editais.php">Editais da Pos</a>
            <a class="btn btn-dark btn-sm" href="/pos/processo-seletivo.php">Processo Seletivo</a>
            <a class="btn btn-outline-primary btn-sm" href="/pos/docentes.php">Docentes da Pos</a>
            <a class="btn btn-outline-secondary btn-sm" href="/pos/atendimento-docentes.php">Atendimento da Pos</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header border-bottom-0">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPesquisaPos" type="button" role="tab">Pesquisa</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabExtensaoPos" type="button" role="tab">Extensao</button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabPesquisaPos" role="tabpanel">
                    <h2 class="h5 mb-2"><?= e((string)$pesquisa['title']) ?></h2>
                    <?php if ((string)$pesquisa['summary'] !== ''): ?><p class="text-muted"><?= e((string)$pesquisa['summary']) ?></p><?php endif; ?>
                    <div class="mb-2"><?= render_rich_text((string)$pesquisa['content_html']) ?></div>
                    <a class="btn btn-outline-primary btn-sm" href="/pos/pesquisa.php">Abrir pagina Pesquisa</a>
                </div>
                <div class="tab-pane fade" id="tabExtensaoPos" role="tabpanel">
                    <h2 class="h5 mb-2"><?= e((string)$extensao['title']) ?></h2>
                    <?php if ((string)$extensao['summary'] !== ''): ?><p class="text-muted"><?= e((string)$extensao['summary']) ?></p><?php endif; ?>
                    <div class="mb-2"><?= render_rich_text((string)$extensao['content_html']) ?></div>
                    <a class="btn btn-outline-primary btn-sm" href="/pos/extensao.php">Abrir pagina Extensao</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($pages as $p): ?>
            <div class="col-md-6 col-xl-4">
                <a class="card card-link news-card h-100" href="/pos/pagina.php?slug=<?= urlencode((string)$p['slug']) ?>">
                    <div class="card-body">
                        <span class="badge text-bg-secondary mb-2">Pagina institucional</span>
                        <h2 class="h5"><?= e((string)$p['title']) ?></h2>
                        <p class="news-summary"><?= e((string)$p['summary']) ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
        <?php if (empty($pages)): ?>
            <div class="col-12"><div class="alert alert-warning mb-0">Nenhuma pagina institucional da pos foi cadastrada ainda.</div></div>
        <?php endif; ?>
    </div>
</div>
<?php page_footer(); ?>
