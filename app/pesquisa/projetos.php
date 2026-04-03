<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';

function curated_projects_catalog(): array {
    return [
        [
            'slug' => 'csilab-ia-aplicada',
            'title' => 'CSILab: IA aplicada a problemas reais',
            'project_type' => 'pesquisa',
            'summary' => 'Projeto de pesquisa em inteligencia artificial, visao computacional e ciencia de dados com integracao entre graduacao e pos.',
            'description' => 'O CSILab atua no desenvolvimento de modelos e sistemas inteligentes com foco em aplicacoes praticas, formacao de recursos humanos e cooperacao com outras instituicoes. A iniciativa integra pesquisa academica com desenvolvimento tecnologico, envolvendo docentes, estudantes de pos-graduacao e graduacao.',
            'coordinator' => 'Equipe CSILab / DECOM-UFOP',
            'site_url' => 'https://csilab.ufop.br/',
            'source_url' => 'https://ufop.br/noticias/pesquisa-e-inovacao/laboratorio-de-computacao-de-sistemas-inteligentes-expande-pesquisas-em',
            'image_url' => 'https://csilab.ufop.br/sites/default/files/styles/os_slideshow_16%3A9_660/public/csilab2/files/csilab_marca_4.png',
        ],
        [
            'slug' => 'goal-otimizacao-algoritmos',
            'title' => 'GOAL: Otimizacao e algoritmos',
            'project_type' => 'pesquisa',
            'summary' => 'Linha de pesquisa em otimização combinatoria, metaheuristicas e algoritmos para problemas de alta complexidade.',
            'description' => 'O GOAL concentra trabalhos em modelagem matematica e computacional para problemas de otimizacao, com aplicacoes em logistica, redes e planejamento. O grupo contribui para formacao cientifica e producao tecnica em computacao aplicada.',
            'coordinator' => 'Grupo GOAL / DECOM-UFOP',
            'site_url' => 'http://www.goal.ufop.br',
            'source_url' => 'https://www3.decom.ufop.br/decom/pesquisa/labs/',
            'image_url' => '/assets/images/carousel/tech-circuit.jpg',
        ],
        [
            'slug' => 'gaid-analise-inteligente-dados',
            'title' => 'GAID: Gerencia e analise inteligente de dados',
            'project_type' => 'pesquisa',
            'summary' => 'Projeto voltado a ciencia de dados, mineracao e inteligencia analitica para apoio a decisao.',
            'description' => 'O GAID desenvolve pesquisas em gerenciamento e analise de dados, combinando tecnicas de banco de dados, mineracao e aprendizado de maquina para gerar conhecimento aplicavel em diferentes dominios.',
            'coordinator' => 'GAID / DECOM-UFOP',
            'site_url' => 'http://www.decom.ufop.br/gaid/',
            'source_url' => 'https://www3.decom.ufop.br/decom/pesquisa/labs/',
            'image_url' => '/assets/images/carousel/decom-campus.png',
        ],
        [
            'slug' => 'treinamento-algoritmos-programacao-avancada',
            'title' => 'Treinamento em Algoritmos e Programacao Avancada',
            'project_type' => 'extensao',
            'summary' => 'Projeto de extensao para fortalecer logica, algoritmos e programacao avancada com alunos da regiao.',
            'description' => 'Acao de extensao com foco na formacao em programacao e resolucao de problemas. O projeto promove aproximacao entre ensino medio/tecnico e universidade, estimulando interesse em computacao e desenvolvimento de competencias em algoritmos.',
            'coordinator' => 'Prof. Marco Antonio Moreira de Carvalho',
            'site_url' => 'https://www3.decom.ufop.br/decom/extensao/projetos/',
            'source_url' => 'https://www3.decom.ufop.br/decom/extensao/projetos/',
            'image_url' => '/assets/images/carousel/ufop-campus-map.png',
        ],
        [
            'slug' => 'telecentro-comunitario',
            'title' => 'Telecentro Comunitario',
            'project_type' => 'extensao',
            'summary' => 'Projeto social de inclusao digital com atividades de tecnologia para criancas e jovens.',
            'description' => 'O Telecentro Comunitario e uma acao de extensao apoiada pelo DECOM e pela Pro-Reitoria de Extensao, oferecendo acesso supervisionado a computadores e internet, alem de atividades educativas para aproximar comunidade e tecnologia.',
            'coordinator' => 'Prof. Jose Maria R. Neves',
            'site_url' => 'https://www3.decom.ufop.br/decom/extensao/projetos/',
            'source_url' => 'https://www3.decom.ufop.br/decom/extensao/projetos/',
            'image_url' => '/assets/images/carousel/decom-campus.png',
        ],
        [
            'slug' => 'codigo-x-equidade-stem',
            'title' => 'Codigo X: equidade em STEM com programacao e IA',
            'project_type' => 'extensao',
            'summary' => 'Projeto de extensao para promover equidade em STEM por meio do ensino de programacao e inteligencia artificial.',
            'description' => 'Iniciativa extensionista voltada a formacao tecnologica e inclusao, com atividades educacionais para ampliar participacao em STEM. O projeto aparece em editais recentes de selecao de bolsistas vinculados ao DECOM.',
            'coordinator' => 'Equipe de extensao DECOM-UFOP',
            'site_url' => 'https://www3.decom.ufop.br/decom/noticias/acervo/edital-selecao-bolsista-projeto-de-extensao',
            'source_url' => 'https://www3.decom.ufop.br/decom/noticias/acervo/edital-selecao-bolsista-projeto-de-extensao',
            'image_url' => '/assets/images/carousel/tech-circuit.jpg',
        ],
    ];
}

function merge_projects_with_database(array $curated): array {
    $dbItems = research_projects_data();
    $merged = [];
    $indexBySlug = [];

    foreach ($curated as $item) {
        $slug = (string)($item['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        $item['description'] = (string)($item['description'] ?? $item['summary'] ?? '');
        $item['image_url'] = (string)($item['image_url'] ?? '/assets/images/carousel/tech-circuit.jpg');
        $item['source_url'] = (string)($item['source_url'] ?? '');
        $merged[] = $item;
        $indexBySlug[$slug] = count($merged) - 1;
    }

    foreach ($dbItems as $db) {
        $slug = trim((string)($db['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        if (isset($indexBySlug[$slug])) {
            $idx = $indexBySlug[$slug];
            $merged[$idx]['title'] = (string)($db['title'] ?? $merged[$idx]['title']);
            $merged[$idx]['summary'] = (string)($db['summary'] ?? $merged[$idx]['summary']);
            if (trim((string)($db['site_url'] ?? '')) !== '') {
                $merged[$idx]['site_url'] = (string)$db['site_url'];
            }
            if (trim((string)($db['coordinator'] ?? '')) !== '') {
                $merged[$idx]['coordinator'] = (string)$db['coordinator'];
            }
            if (trim((string)($db['project_type'] ?? '')) !== '') {
                $merged[$idx]['project_type'] = (string)$db['project_type'];
            }
            continue;
        }

        $merged[] = [
            'slug' => $slug,
            'title' => (string)($db['title'] ?? 'Projeto'),
            'project_type' => (string)($db['project_type'] ?? 'pesquisa'),
            'summary' => (string)($db['summary'] ?? ''),
            'description' => (string)($db['summary'] ?? ''),
            'coordinator' => (string)($db['coordinator'] ?? 'DECOM/UFOP'),
            'site_url' => (string)($db['site_url'] ?? ''),
            'source_url' => '',
            'image_url' => '/assets/images/carousel/tech-circuit.jpg',
        ];
    }

    return $merged;
}

$projects = merge_projects_with_database(curated_projects_catalog());
$slug = trim((string)($_GET['slug'] ?? ''));
$selected = null;
if ($slug !== '') {
    foreach ($projects as $item) {
        if ((string)$item['slug'] === $slug) {
            $selected = $item;
            break;
        }
    }
}

page_header($selected ? (string)$selected['title'] : 'Projetos de Pesquisa e Extensao');
?>
<div class="container py-4">
    <?php if ($selected): ?>
        <?php $isExt = (($selected['project_type'] ?? '') === 'extensao'); ?>
        <a class="btn btn-outline-secondary btn-sm mb-3" href="/pesquisa/projetos.php">Voltar para projetos</a>
        <div class="card shadow-sm">
            <img
                src="<?= e((string)$selected['image_url']) ?>"
                alt="<?= e((string)$selected['title']) ?>"
                class="card-img-top"
                style="max-height:380px;object-fit:cover;"
            >
            <div class="card-body">
                <span class="badge <?= $isExt ? 'text-bg-secondary' : 'text-bg-primary' ?> mb-2">
                    <?= $isExt ? 'Extensao' : 'Pesquisa' ?>
                </span>
                <h1 class="h3 mb-3"><?= e((string)$selected['title']) ?></h1>
                <p class="lead"><?= e((string)$selected['summary']) ?></p>
                <p><?= e((string)$selected['description']) ?></p>
                <?php if (!empty($selected['coordinator'])): ?>
                    <p><strong>Coordenacao/Responsavel:</strong> <?= e((string)$selected['coordinator']) ?></p>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (!empty($selected['site_url'])): ?>
                        <a class="btn btn-primary btn-sm" href="<?= e((string)$selected['site_url']) ?>" target="_blank" rel="noopener">Site do projeto</a>
                    <?php endif; ?>
                    <?php if (!empty($selected['source_url'])): ?>
                        <a class="btn btn-outline-dark btn-sm" href="<?= e((string)$selected['source_url']) ?>" target="_blank" rel="noopener">Fonte oficial</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <h1 class="section-title h3 mb-2">Projetos de Pesquisa e Extensao</h1>
        <p class="text-muted mb-4">Resumo de iniciativas da UFOP/DECOM com pagina propria para cada projeto.</p>

        <div class="row g-4">
            <?php foreach ($projects as $project): ?>
                <?php $isExt = (($project['project_type'] ?? '') === 'extensao'); ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card news-card h-100 shadow-sm">
                        <img
                            src="<?= e((string)$project['image_url']) ?>"
                            alt="<?= e((string)$project['title']) ?>"
                            class="card-img-top"
                            style="height:180px;object-fit:cover;"
                        >
                        <div class="card-body d-flex flex-column">
                            <span class="badge <?= $isExt ? 'text-bg-secondary' : 'text-bg-primary' ?> mb-2">
                                <?= $isExt ? 'Extensao' : 'Pesquisa' ?>
                            </span>
                            <h2 class="h5 mb-2"><?= e((string)$project['title']) ?></h2>
                            <p class="news-summary mb-3"><?= e((string)$project['summary']) ?></p>
                            <?php if (!empty($project['coordinator'])): ?>
                                <p class="mb-3"><strong>Coordenacao:</strong> <?= e((string)$project['coordinator']) ?></p>
                            <?php endif; ?>
                            <div class="mt-auto d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary btn-sm" href="/pesquisa/projetos.php?slug=<?= e((string)$project['slug']) ?>">Ver detalhes</a>
                                <?php if (!empty($project['site_url'])): ?>
                                    <a class="btn btn-outline-dark btn-sm" href="<?= e((string)$project['site_url']) ?>" target="_blank" rel="noopener">Site</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php page_footer(); ?>
