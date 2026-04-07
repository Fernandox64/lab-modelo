<?php
require __DIR__ . '/../includes/config.php';

$publicacoes = [
    [
        'tipo' => 'Artigo em Periodico',
        'titulo' => 'Arquitetura de dados para laboratorios universitarios: um estudo aplicado',
        'autores' => 'S. Oliveira, M. Santos, A. Ribeiro',
        'ano' => '2026',
        'link' => '#',
    ],
    [
        'tipo' => 'Trabalho em Evento',
        'titulo' => 'Modelo de laboratorio integrado ao departamento para gestao academica e pesquisa',
        'autores' => 'B. Mendes, C. Lopes',
        'ano' => '2025',
        'link' => '#',
    ],
    [
        'tipo' => 'Capitulo de Livro',
        'titulo' => 'Boas praticas para producao cientifica em laboratorios de universidades federais',
        'autores' => 'Equipe do Laboratorio',
        'ano' => '2024',
        'link' => '#',
    ],
];

page_header('Publicacoes');
?>
<div class="container py-4">
    <h1 class="section-title h3 mb-3">Publicacoes</h1>
    <p class="text-muted mb-4">Producao cientifica do laboratorio, em parceria com o departamento.</p>

    <div class="row g-3">
        <?php foreach ($publicacoes as $pub): ?>
            <div class="col-lg-4">
                <div class="card news-card h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge text-bg-primary mb-2"><?= e((string)$pub['tipo']) ?></span>
                        <h2 class="h5 mb-2"><?= e((string)$pub['titulo']) ?></h2>
                        <p class="mb-2 text-muted"><?= e((string)$pub['autores']) ?></p>
                        <p class="mb-3"><strong>Ano:</strong> <?= e((string)$pub['ano']) ?></p>
                        <a class="btn btn-outline-primary btn-sm mt-auto" href="<?= e((string)$pub['link']) ?>">Ver referencia</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php page_footer(); ?>
