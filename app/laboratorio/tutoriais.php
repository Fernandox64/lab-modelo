<?php
require __DIR__ . '/../includes/config.php';

page_header('Tutoriais');
?>
<main id="main-content" class="container py-4">
    <h1 class="section-title h3 mb-3">Tutoriais</h1>
    <p class="text-muted mb-4">Guias tecnicos para atividades de pesquisa, desenvolvimento e reproducao de resultados.</p>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card news-card h-100">
                <div class="card-body">
                    <h2 class="h5">Como preparar ambiente de pesquisa</h2>
                    <p class="mb-0">Passo a passo para configurar ferramentas e padrao de versionamento.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card news-card h-100">
                <div class="card-body">
                    <h2 class="h5">Boas praticas de documentacao</h2>
                    <p class="mb-0">Estrutura recomendada para relatorios, experimentos e reprodutibilidade.</p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php page_footer(); ?>
