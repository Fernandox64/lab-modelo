<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';

$data = atendimento_docentes_get('pos');
page_header((string)$data['title']);
?>
<style>
.atendimento-docentes table thead th{background:#0d6efd;color:#fff}
.atendimento-docentes table tbody td.filled{background:#dbeafe;color:#0b3a8a;font-weight:600}
.atendimento-docentes table tbody td:first-child{background:#f8fafc;font-weight:700}
</style>
<div class="container py-4">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-outline-secondary btn-sm" href="/pos/inicio.php">Voltar ao subsite da Pos</a>
        <a class="btn btn-outline-primary btn-sm" href="/pos/docentes.php">Docentes da Pos</a>
    </div>

    <h1 class="section-title h3 mb-3"><?= e((string)$data['title']) ?></h1>
    <p class="lead mb-4"><?= e((string)$data['summary']) ?></p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <?= render_rich_text((string)$data['intro_html']) ?>
            <?php if (!empty($data['source_url'])): ?>
                <a class="btn btn-outline-primary btn-sm mt-2" target="_blank" rel="noopener" href="<?= e((string)$data['source_url']) ?>">
                    Fonte institucional
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4 atendimento-docentes">
        <div class="card-body">
            <?= render_rich_text((string)$data['table_html']) ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?= render_rich_text((string)$data['notes_html']) ?>
            <?php if (!empty($data['last_sync'])): ?>
                <p class="small text-muted mb-0 mt-2">Ultima atualizacao automatica: <?= e((string)$data['last_sync']) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.atendimento-docentes table tbody tr').forEach(function (row) {
    row.querySelectorAll('td').forEach(function (cell, idx) {
      if (idx === 0) return;
      if ((cell.textContent || '').trim() !== '') cell.classList.add('filled');
    });
  });
});
</script>
<?php page_footer(); ?>
