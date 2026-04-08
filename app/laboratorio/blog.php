<?php
require __DIR__ . '/../includes/config.php';

$pageSlug = 'blog';
$content = laboratory_page_get($pageSlug);

$perPage = 9;
$currentPage = max(1, (int)($_GET['pagina'] ?? 1));
$selectedYear = isset($_GET['ano']) ? (int)$_GET['ano'] : 0;

$list = laboratory_page_items_paginated($pageSlug, $selectedYear, $currentPage, $perPage);
$years = (array)($list['years'] ?? []);
$selectedYear = (int)($list['selected_year'] ?? 0);
$currentPage = (int)($list['current_page'] ?? 1);
$totalPages = (int)($list['total_pages'] ?? 1);
$items = (array)($list['items'] ?? []);

function build_laboratorio_page_url(string $slug, int $year, int $page): string {
    return laboratory_page_build_url($slug, $year, $page);
}

page_header((string)$content['title']);
?>
<main id="main-content" class="container py-4">
    <h1 class="section-title h3 mb-3"><?= e((string)$content['title']) ?></h1>
    <p class="text-muted mb-4"><?= e((string)$content['summary']) ?></p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <?= render_rich_text((string)$content['content_html']) ?>
        </div>
    </div>

    <?php if (!empty($years)): ?>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <?php foreach ($years as $year): ?>
                <a class="btn btn-sm <?= $year === $selectedYear ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= e(build_laboratorio_page_url($pageSlug, (int)$year, 1)) ?>">
                    <?= e((string)$year) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach ($items as $item): ?>
            <?php $itemUrl = '/laboratorio/ver.php?pagina=' . urlencode($pageSlug) . '&slug=' . urlencode((string)$item['slug']); ?>
            <div class="col-md-6 col-xl-4">
                <a class="card card-link news-card h-100" href="<?= e((string)$itemUrl) ?>">
                    <?php if (!empty($item['image_url'])): ?>
                        <img class="news-card-cover" src="<?= e((string)$item['image_url']) ?>" alt="<?= e((string)$item['title']) ?>">
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge text-bg-primary"><?= e((string)$item['category']) ?></span>
                        </div>
                        <h2 class="h5 mb-2"><?= e((string)$item['title']) ?></h2>
                        <p class="news-summary mb-3"><?= e((string)$item['summary']) ?></p>
                        <span class="news-cta mt-auto">Ler mais</span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($items)): ?>
        <div class="alert alert-warning mt-4 mb-0">Nenhum item encontrado para o ano selecionado.</div>
    <?php endif; ?>

    <?php if ($totalPages > 1 && $selectedYear > 0): ?>
        <?php
            $maxVisiblePages = 10;
            $windowStart = max(1, $currentPage - intdiv($maxVisiblePages, 2));
            $windowEnd = min($totalPages, $windowStart + $maxVisiblePages - 1);
            if (($windowEnd - $windowStart + 1) < $maxVisiblePages) {
                $windowStart = max(1, $windowEnd - $maxVisiblePages + 1);
            }
        ?>
        <nav class="mt-4" aria-label="Paginacao de itens">
            <ul class="pagination">
                <li class="page-item<?= $currentPage <= 1 ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= e(build_laboratorio_page_url($pageSlug, $selectedYear, max(1, $currentPage - 1))) ?>">Anterior</a>
                </li>
                <?php if ($windowStart > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= e(build_laboratorio_page_url($pageSlug, $selectedYear, 1)) ?>">1</a></li>
                <?php endif; ?>
                <?php if ($windowStart > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <?php for ($pg = $windowStart; $pg <= $windowEnd; $pg++): ?>
                    <li class="page-item<?= $pg === $currentPage ? ' active' : '' ?>">
                        <a class="page-link" href="<?= e(build_laboratorio_page_url($pageSlug, $selectedYear, $pg)) ?>"><?= e((string)$pg) ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($windowEnd < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <?php if ($windowEnd < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="<?= e(build_laboratorio_page_url($pageSlug, $selectedYear, $totalPages)) ?>"><?= e((string)$totalPages) ?></a></li>
                <?php endif; ?>
                <li class="page-item<?= $currentPage >= $totalPages ? ' disabled' : '' ?>">
                    <a class="page-link" href="<?= e(build_laboratorio_page_url($pageSlug, $selectedYear, min($totalPages, $currentPage + 1))) ?>">Proxima</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</main>
<?php page_footer(); ?>
