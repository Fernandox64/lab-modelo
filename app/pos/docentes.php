<?php
require __DIR__ . '/../includes/config.php';

$items = docentes('pos');
page_header('Docentes da Pos-graduacao');
?>
<style>
.doc-card{border:0;border-radius:16px;overflow:hidden;box-shadow:0 14px 30px rgba(16,42,67,.12);transition:transform .2s ease,box-shadow .2s ease;background:#fff}
.doc-card:hover{transform:translateY(-4px);box-shadow:0 18px 36px rgba(16,42,67,.18)}
.doc-photo-wrap{position:relative;display:block}
.doc-photo-wrap::after{content:"";position:absolute;inset:auto 0 0 0;height:84px;background:linear-gradient(to top,rgba(7,22,40,.65),transparent)}
.doc-photo{width:100%;height:230px;object-fit:cover;background:#dbe7f3}
.doc-chip{font-size:.78rem;font-weight:600;color:#0b4f89;background:#e7f1fb;border-radius:999px;padding:.25rem .6rem;display:inline-block}
.doc-actions .btn{border-radius:999px}
.doc-actions .btn-primary{background:#0d5ea8;border-color:#0d5ea8}
.doc-actions .btn-outline-secondary{border-color:#c5d2df;color:#334e68}
</style>
<div class="container py-4">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="/pos/inicio.php">Voltar ao subsite da Pos</a>
        <a class="btn btn-outline-primary btn-sm" href="/pos/atendimento-docentes.php">Atendimento da Pos</a>
    </div>

    <h1 class="section-title h3 mb-4">Docentes da Pos-graduacao</h1>
    <div class="row g-3">
        <?php foreach ($items as $item): ?>
            <div class="col-md-6">
                <div class="card doc-card h-100">
                    <?php $photoHref = !empty($item['lattes_url']) ? (string)$item['lattes_url'] : '#'; ?>
                    <a class="doc-photo-wrap<?= $photoHref === '#' ? ' pe-none' : '' ?>" href="<?= e($photoHref) ?>" target="_blank" rel="noopener" title="Abrir curriculo lattes">
                        <img class="doc-photo" src="<?= e(person_photo_url($item)) ?>" alt="<?= e($item['name'] ?? 'Docente') ?>">
                    </a>
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <h2 class="h5 mb-0"><?= e($item['name'] ?? '') ?></h2>
                            <span class="doc-chip">Docente da Pos</span>
                        </div>
                        <p class="mb-2 text-muted"><?= e($item['position'] ?? '') ?></p>

                        <?php if (!empty($item['degree'])): ?>
                            <p class="mb-2"><strong>Titulacao:</strong> <?= e($item['degree']) ?></p>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap gap-2 mb-3 doc-actions">
                            <?php if (!empty($item['lattes_url'])): ?>
                                <a class="btn btn-sm btn-primary" href="<?= e($item['lattes_url']) ?>" target="_blank" rel="noopener">Ver Curriculo</a>
                            <?php endif; ?>
                            <?php if (!empty($item['website_url'])): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e($item['website_url']) ?>" target="_blank" rel="noopener">Site</a>
                            <?php endif; ?>
                            <?php if (!empty($item['email'])): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="mailto:<?= e($item['email']) ?>">E-mail</a>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($item['phone'])): ?>
                            <p class="mb-1"><strong>Telefone:</strong> <?= e($item['phone']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['room'])): ?>
                            <p class="mb-1"><?= e($item['room']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['interests'])): ?>
                            <p class="mb-0"><strong>Areas de interesse:</strong> <?= e($item['interests']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
            <div class="col-12"><div class="alert alert-warning mb-0">Nenhum docente da pos-graduacao cadastrado.</div></div>
        <?php endif; ?>
    </div>
</div>
<?php page_footer(); ?>
