<?php
require __DIR__ . '/../includes/config.php';

$projetos = research_projects_data();

page_header('Projetos');
?>
<main id="main-content" class="container py-4">
    <h1 class="section-title h3 mb-3">Projetos</h1>
    <p class="text-muted mb-4">Projetos de pesquisa e extensao conduzidos pelo laboratorio.</p>

    <div class="row g-3">
        <?php foreach ($projetos as $projeto): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card news-card h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge text-bg-primary mb-2"><?= e((string)($projeto['project_type'] ?? 'projeto')) ?></span>
                        <h2 class="h5 mb-2"><?= e((string)($projeto['title'] ?? 'Projeto')) ?></h2>
                        <p class="text-muted mb-2"><?= e((string)($projeto['summary'] ?? '')) ?></p>
                        <?php if (!empty($projeto['coordinator'])): ?>
                            <p class="mb-3"><strong>Coordenacao:</strong> <?= e((string)$projeto['coordinator']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($projeto['site_url'])): ?>
                            <a class="btn btn-outline-primary btn-sm mt-auto" href="<?= e((string)$projeto['site_url']) ?>" target="_blank" rel="noopener">Acessar projeto</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php page_footer(); ?>
