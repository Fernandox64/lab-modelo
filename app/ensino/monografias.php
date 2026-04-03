<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';

page_header('Monografias');
?>
<div class="container py-4">
    <h1 class="section-title h3 mb-3">Monografias (TCC) - Ciencia da Computacao</h1>
    <p class="lead mb-4">
        Orientacoes para planejamento, desenvolvimento e defesa de Monografia I e Monografia II.
    </p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Visao geral</h2>
            <p class="mb-2">
                A monografia consolida a formacao do aluno em um projeto tecnico-cientifico orientado,
                com foco em aplicacao pratica, metodo e comunicacao academica.
            </p>
            <p class="mb-0">
                Esta pagina organiza etapas, documentos e links uteis para alunos, orientadores e bancas.
            </p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Fluxo academico recomendado</h2>
                    <ol class="mb-0">
                        <li>Definicao de tema, orientador e escopo inicial.</li>
                        <li>Cadastro/validacao do plano de trabalho e cronograma.</li>
                        <li>Execucao da Monografia I (proposta, estudo e resultados parciais).</li>
                        <li>Execucao da Monografia II (implementacao, avaliacao e redacao final).</li>
                        <li>Solicitacao de banca, defesa publica e entrega da versao final.</li>
                    </ol>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Checklist do aluno</h2>
                    <ul class="mb-0">
                        <li>Confirmar pre-requisitos e matricula na disciplina correta.</li>
                        <li>Definir tema viavel com objetivos, metodologia e entregas.</li>
                        <li>Manter reunioes regulares com orientador e registrar evolucao.</li>
                        <li>Seguir padrao de escrita academica e normas de citacao.</li>
                        <li>Preparar apresentacao e versao final dentro dos prazos oficiais.</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Bancas e defesas</h2>
                    <p class="mb-3">
                        As agendas e comunicados de defesa sao publicadas no modulo de Defesas do portal.
                    </p>
                    <a class="btn btn-outline-primary btn-sm" href="/noticias/defesas.php">Ver Defesas publicadas</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Documentos e links</h2>
                    <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="https://www3.decom.ufop.br/decom/ensino/monografias/">Pagina base (DECOM antigo)</a>
                        <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="https://monografias.ufop.br/handle/35400000/10">Repositorio de Monografias UFOP</a>
                        <a class="btn btn-outline-primary btn-sm" href="/ensino/ciencia-computacao.php?curso=cc">Pagina da Graduacao</a>
                        <a class="btn btn-outline-primary btn-sm" href="/pessoal/atendimento-docentes.php">Atendimento Docentes</a>
                        <a class="btn btn-outline-primary btn-sm" href="/ensino/horarios-de-aula.php">Horarios de Aula</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Prazos e acompanhamento</h2>
                    <p class="mb-2">
                        Consulte o calendario academico vigente para datas de matricula, submissao e defesa.
                    </p>
                    <p class="mb-0 text-muted">
                        Recomendado iniciar o planejamento da monografia com antecedencia minima de um semestre.
                    </p>
                </div>
            </div>

            <div class="card news-card">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Suporte</h2>
                    <p class="mb-2"><strong>Secretaria DECOM:</strong> decom@ufop.edu.br</p>
                    <p class="mb-0"><strong>Telefone:</strong> +55 31 3559-1692</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php page_footer(); ?>
