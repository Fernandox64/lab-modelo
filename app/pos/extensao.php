<?php
require __DIR__ . '/../includes/config.php';

$section = ppgcc_section_get('extensao');
page_header((string)$section['title']);
?>
<div class="container py-4">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="/pos/inicio.php">Voltar ao subsite da Pos</a>
        <a class="btn btn-outline-primary btn-sm" href="/pos/pesquisa.php">Pesquisa</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-3"><?= e((string)$section['title']) ?></h1>
            <?php if ((string)$section['summary'] !== ''): ?><p class="lead text-muted"><?= e((string)$section['summary']) ?></p><?php endif; ?>
            <div><?= render_rich_text((string)$section['content_html']) ?></div>
        </div>
    </div>
</div>
<?php page_footer(); ?>
