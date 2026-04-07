<?php
require __DIR__ . '/../includes/config.php';

$docentes = docentes('principal');
$tecnicos = funcionarios('principal');

page_header('Equipe');
?>
<div class="container py-4">
    <h1 class="section-title h3 mb-3">Equipe do Laboratorio</h1>
    <p class="text-muted mb-4">Docentes, pesquisadores e equipe tecnica vinculados ao laboratorio.</p>

    <div class="row g-3">
        <?php foreach ($docentes as $item): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card news-card h-100">
                    <img
                        class="news-card-cover"
                        src="<?= e(person_photo_url($item)) ?>"
                        alt="<?= e((string)($item['name'] ?? 'Membro da equipe')) ?>"
                    >
                    <div class="card-body">
                        <span class="badge text-bg-primary mb-2">Docente</span>
                        <h2 class="h5 mb-1"><?= e((string)($item['name'] ?? '')) ?></h2>
                        <p class="mb-2 text-muted"><?= e((string)($item['position'] ?? '')) ?></p>
                        <?php if (!empty($item['interests'])): ?>
                            <p class="mb-2"><strong>Interesses:</strong> <?= e((string)$item['interests']) ?></p>
                        <?php endif; ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if (!empty($item['email'])): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="mailto:<?= e((string)$item['email']) ?>">E-mail</a>
                            <?php endif; ?>
                            <?php if (!empty($item['lattes_url'])): ?>
                                <a class="btn btn-sm btn-primary" href="<?= e((string)$item['lattes_url']) ?>" target="_blank" rel="noopener">Lattes</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($tecnicos)): ?>
        <h2 class="h4 mt-5 mb-3">Equipe Tecnica</h2>
        <div class="row g-3">
            <?php foreach ($tecnicos as $item): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h5 mb-1"><?= e((string)($item['name'] ?? '')) ?></h3>
                            <p class="text-muted mb-2"><?= e((string)($item['position'] ?? '')) ?></p>
                            <?php if (!empty($item['email'])): ?>
                                <p class="mb-1"><strong>E-mail:</strong> <a href="mailto:<?= e((string)$item['email']) ?>"><?= e((string)$item['email']) ?></a></p>
                            <?php endif; ?>
                            <?php if (!empty($item['phone'])): ?>
                                <p class="mb-0"><strong>Telefone:</strong> <?= e((string)$item['phone']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php page_footer(); ?>
