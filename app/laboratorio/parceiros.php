<?php
require __DIR__ . '/../includes/config.php';

page_header('Parceiros');
?>
<main id="main-content" class="container py-4">
    <h1 class="section-title h3 mb-3">Parceiros</h1>
    <p class="text-muted mb-4">Instituicoes e grupos que colaboram com o laboratorio.</p>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Departamento Vinculado</h2>
                    <p class="mb-0">Cooperacao academica e administrativa com o departamento de origem.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Outros Laboratorios</h2>
                    <p class="mb-0">Projetos interdisciplinares com laboratorios da universidade e rede externa.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Setor Produtivo</h2>
                    <p class="mb-0">Parcerias para transferencia de tecnologia e inovacao.</p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php page_footer(); ?>
