<?php
require __DIR__ . '/../includes/config.php';

page_header('Cursos');
?>
<main id="main-content" class="container py-4">
    <h1 class="section-title h3 mb-3">Cursos</h1>
    <p class="text-muted mb-4">Formacoes e capacitacoes vinculadas ao laboratorio.</p>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Curso de Iniciacao Cientifica</h2>
                    <p class="mb-0">Trilha para estudantes ingressantes em projetos de pesquisa aplicada.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Oficina de Metodologia de Pesquisa</h2>
                    <p class="mb-0">Curso introdutorio sobre desenho experimental, coleta e analise de dados.</p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php page_footer(); ?>
