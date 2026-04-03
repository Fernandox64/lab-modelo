<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';

function normalize_absolute_url(string $href, string $baseUrl): string {
    $href = trim($href);
    if ($href === '') {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $href) === 1) {
        return $href;
    }
    if (strpos($href, '//') === 0) {
        return 'https:' . $href;
    }
    $baseParts = parse_url($baseUrl);
    if (!is_array($baseParts) || empty($baseParts['scheme']) || empty($baseParts['host'])) {
        return $href;
    }
    $origin = $baseParts['scheme'] . '://' . $baseParts['host'];
    if (strpos($href, '/') === 0) {
        return $origin . $href;
    }
    $path = $baseParts['path'] ?? '/';
    $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');
    return $origin . ($dir !== '' ? $dir : '') . '/' . $href;
}

function parse_semester_meta(string $text): array {
    $year = 0;
    $semester = 0;
    if (preg_match('/(20\d{2})\s*[-\/\.]?\s*([12])/', $text, $m) === 1) {
        $year = (int)$m[1];
        $semester = (int)$m[2];
    } elseif (preg_match('/(20\d{2})/', $text, $m) === 1) {
        $year = (int)$m[1];
    }
    return ['year' => $year, 'semester' => $semester];
}

function ufop_calendarios_presenciais(): array {
    $cacheDataKey = 'ufop_presencial_calendarios_json';
    $cacheTimeKey = 'ufop_presencial_calendarios_cached_at';
    $cacheTtlSeconds = 60 * 60 * 12;
    $baseUrl = 'https://www.prograd.ufop.br/calendario-academico';
    $fallback = [
        [
            'title' => 'Calendario academico da PROGRAD',
            'url' => $baseUrl,
            'year' => 0,
            'semester' => 0,
            'is_presencial' => true,
        ],
    ];

    $cachedAt = (int)site_setting_get($cacheTimeKey, '0');
    $cachedJson = site_setting_get($cacheDataKey, '');
    if ($cachedAt > 0 && (time() - $cachedAt) < $cacheTtlSeconds && $cachedJson !== '') {
        $cached = json_decode($cachedJson, true);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 12,
            'ignore_errors' => true,
            'user_agent' => 'DECOM-UFOP/1.0 (+https://localhost:8080)',
        ],
    ]);
    $html = @file_get_contents($baseUrl, false, $context);
    if (!is_string($html) || trim($html) === '') {
        if ($cachedJson !== '') {
            $cached = json_decode($cachedJson, true);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        }
        return $fallback;
    }

    $items = [];
    $seen = [];
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if (@$dom->loadHTML($html)) {
        $xpath = new DOMXPath($dom);
        $links = $xpath->query('//a[@href]');
        if ($links !== false) {
            foreach ($links as $link) {
                $title = trim(preg_replace('/\s+/', ' ', (string)$link->textContent) ?? '');
                $href = trim((string)$link->getAttribute('href'));
                if ($href === '') {
                    continue;
                }
                $url = normalize_absolute_url($href, $baseUrl);
                $haystack = mb_strtolower($title . ' ' . $url, 'UTF-8');
                if (strpos($haystack, 'calend') === false) {
                    continue;
                }
                $isPresencial = (strpos($haystack, 'presencial') !== false);
                $meta = parse_semester_meta($title . ' ' . $url);
                if (isset($seen[$url])) {
                    continue;
                }
                $seen[$url] = true;
                $items[] = [
                    'title' => $title !== '' ? $title : 'Calendario academico',
                    'url' => $url,
                    'year' => $meta['year'],
                    'semester' => $meta['semester'],
                    'is_presencial' => $isPresencial,
                ];
            }
        }
    }
    libxml_clear_errors();

    if (empty($items)) {
        if ($cachedJson !== '') {
            $cached = json_decode($cachedJson, true);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        }
        return $fallback;
    }

    usort($items, static function (array $a, array $b): int {
        $scoreA = ($a['is_presencial'] ? 1000000 : 0) + ((int)$a['year'] * 10) + (int)$a['semester'];
        $scoreB = ($b['is_presencial'] ? 1000000 : 0) + ((int)$b['year'] * 10) + (int)$b['semester'];
        return $scoreB <=> $scoreA;
    });

    $selected = array_slice($items, 0, 8);
    site_setting_set($cacheDataKey, json_encode($selected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    site_setting_set($cacheTimeKey, (string)time());
    return $selected;
}

$page = page_data('informacoes-uteis');
$calendarios = ufop_calendarios_presenciais();
$anoVigente = (int)date('Y');
$calendariosAnoVigente = array_values(array_filter(
    $calendarios,
    static fn(array $item): bool => (int)($item['year'] ?? 0) === $anoVigente
));
if (empty($calendariosAnoVigente)) {
    $calendariosAnoVigente = array_slice($calendarios, 0, 1);
}
$lastSync = (int)site_setting_get('ufop_presencial_calendarios_cached_at', '0');
page_header('Informacoes Uteis');
?>
<div class="container py-4">
    <h1 class="section-title h3 mb-2"><?= e($page['title']) ?></h1>
    <p class="text-secondary mb-4"><?= e($page['summary']) ?></p>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Calendario academico oficial (UFOP/PROGRAD)</h2>
                    <p class="mb-2">Atualizacao automatica semestral a partir da pagina oficial da PROGRAD, com prioridade para versoes presenciais.</p>
                    <p class="small text-secondary mb-3">Fonte: <a href="https://www.prograd.ufop.br/calendario-academico" target="_blank" rel="noopener">prograd.ufop.br/calendario-academico</a></p>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($calendariosAnoVigente as $item): ?>
                            <li class="list-group-item px-0">
                                <a href="<?= e((string)$item['url']) ?>" target="_blank" rel="noopener"><?= e((string)$item['title']) ?></a>
                                <?php if (!empty($item['is_presencial'])): ?>
                                    <span class="badge text-bg-primary ms-2">Presencial</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($lastSync > 0): ?>
                        <p class="small text-secondary mt-3 mb-0">Ultima sincronizacao: <?= e(date('d/m/Y H:i', $lastSync)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Atendimento burocratico (ICEB e DECOM)</h2>
                    <ul class="mb-3">
                        <li>Secretaria DECOM: apoio para demandas de disciplinas, documentos e fluxos internos do curso.</li>
                        <li>Colegiado (COCIC): deliberacoes sobre casos academicos, trancamentos, ajustes e equivalencias.</li>
                        <li>PROGRAD/CARA: registro academico, processamento de requerimentos e lancamentos no historico.</li>
                    </ul>
                    <p class="mb-1"><strong>Contatos institucionais:</strong></p>
                    <p class="mb-1">DECOM: decom@ufop.edu.br | +55 (31) 3559-1692</p>
                    <p class="mb-0">COCIC: cocic@ufop.edu.br | +55 (31) 3559-1312</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Orientacoes de matricula</h2>
                    <ol class="mb-3">
                        <li>Consultar calendario e janela de matricula do semestre vigente.</li>
                        <li>Conferir pre-requisitos, choques de horario e situacao curricular no sistema academico.</li>
                        <li>Solicitar ajustes dentro do prazo oficial e acompanhar deferimentos pelo curso.</li>
                    </ol>
                    <p class="mb-0">Recomendacao: priorize disciplinas obrigatorias do periodo e mantenha comprovantes/protocolos das solicitacoes.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Aproveitamento de estudos e equivalencias</h2>
                    <ul class="mb-3">
                        <li>Pedido formal com historico e ementas/programas da instituicao de origem.</li>
                        <li>Analise tecnica pelo colegiado do curso, com parecer de equivalencia total ou parcial.</li>
                        <li>Registro no historico academico apos deferimento oficial.</li>
                    </ul>
                    <p class="mb-0">Oriente-se sempre por edital/portaria vigente e prazo semestral definido pela UFOP.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Monitorias (ensino presencial)</h2>
            <p class="mb-2">Acompanhe os editais e cronogramas de monitoria/tutoria do DECOM e da PROGRAD para bolsas, inscricoes e selecoes.</p>
            <a class="btn btn-outline-primary btn-sm me-2" href="/noticias/editais.php">Ver editais internos</a>
            <a class="btn btn-outline-secondary btn-sm" href="https://www.prograd.ufop.br/%3Cnolink%3E/monitoria" target="_blank" rel="noopener">Pagina oficial de monitoria (PROGRAD)</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Observacoes importantes para o aluno</h2>
            <ul class="mb-0">
                <li>Sempre confirme a versao mais recente do calendario academico antes de protocolar requerimentos.</li>
                <li>Processos de aproveitamento/equivalencia exigem documentacao completa e legivel.</li>
                <li>Para casos especiais, procure primeiro a secretaria/coordenação do curso e depois a PROGRAD quando necessario.</li>
            </ul>
        </div>
    </div>
</div>
<?php page_footer(); ?>
