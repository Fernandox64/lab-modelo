SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS news_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    category VARCHAR(80) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS edital_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    category VARCHAR(80) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS defesa_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    category VARCHAR(80) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    category VARCHAR(80) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS people_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    role_type ENUM('docente','funcionario','estudante_graduacao','estudante_pos') NOT NULL DEFAULT 'docente',
    scope ENUM('principal','pos') NOT NULL DEFAULT 'principal',
    name VARCHAR(180) NOT NULL,
    position VARCHAR(255) NOT NULL,
    degree TEXT DEFAULT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    lattes_url VARCHAR(255) DEFAULT NULL,
    email VARCHAR(180) DEFAULT NULL,
    phone VARCHAR(80) DEFAULT NULL,
    room VARCHAR(255) DEFAULT NULL,
    photo_url VARCHAR(255) DEFAULT NULL,
    interests TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS research_labs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    summary TEXT NOT NULL,
    site_url VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS research_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    project_type ENUM('pesquisa','extensao') NOT NULL DEFAULT 'pesquisa',
    summary TEXT NOT NULL,
    site_url VARCHAR(255) DEFAULT NULL,
    coordinator VARCHAR(180) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS laboratory_page_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_slug VARCHAR(60) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'Laboratorio',
    content_html MEDIUMTEXT NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    external_url VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_lab_page_slug (page_slug, slug),
    INDEX idx_lab_page_pub (page_slug, is_active, published_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ppgcc_page_content (
    id INT NOT NULL PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    intro_html MEDIUMTEXT NOT NULL,
    ingresso_html MEDIUMTEXT NOT NULL,
    editais_html MEDIUMTEXT NOT NULL,
    grade_html MEDIUMTEXT NOT NULL,
    docencia_html MEDIUMTEXT NOT NULL,
    bolsas_html MEDIUMTEXT NOT NULL,
    graduacao_html MEDIUMTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ppgcc_graduates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    graduate_year INT NOT NULL,
    student_name VARCHAR(220) NOT NULL,
    source_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ppgcc_graduate (graduate_year, student_name)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ppgcc_notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    title VARCHAR(220) NOT NULL,
    summary TEXT NOT NULL,
    notice_type ENUM('edital','informacao') NOT NULL DEFAULT 'edital',
    notice_url VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ppgcc_selection_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_title VARCHAR(255) NOT NULL,
    item_title VARCHAR(255) NOT NULL,
    item_url VARCHAR(600) NOT NULL,
    item_hash CHAR(64) NOT NULL UNIQUE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ppgcc_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    title VARCHAR(220) NOT NULL,
    summary TEXT NOT NULL,
    content_html MEDIUMTEXT NOT NULL,
    source_url VARCHAR(600) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

DELETE FROM news_items;
DELETE FROM edital_items;

DELETE FROM defesa_items
WHERE slug IN (
    'defesa-monografia-sistemas-2026-1',
    'defesa-tcc-ia-aplicada-2026-1'
);

DELETE FROM job_items
WHERE slug IN (
    'vaga-estagio-web-php',
    'vaga-dev-junior-backend'
);

DELETE FROM people_items;
DELETE FROM laboratory_page_items;

DELETE FROM research_labs;
DELETE FROM research_projects;

INSERT INTO laboratory_page_items
    (page_slug, slug, title, summary, category, content_html, image_url, external_url, is_active, sort_order, published_at)
VALUES
    ('projetos', 'proj-visao-computacional-saude', 'Projeto Visao Computacional em Saude', 'Pesquisa aplicada com processamento de imagens para apoio a diagnostico.', 'Pesquisa', '<p>Projeto de pesquisa com foco em visao computacional aplicada a saude, envolvendo alunos de graduacao e pos.</p>', '/assets/cards/noticia-pesquisa.svg', '', 1, 1, '2026-03-20 10:00:00'),
    ('projetos', 'proj-iot-cidades-inteligentes', 'Projeto IoT para Cidades Inteligentes', 'Desenvolvimento de sensores e analise de dados urbanos em tempo real.', 'Extensao', '<p>Projeto interdisciplinar para monitoramento urbano e prototipagem de solucoes com Internet das Coisas.</p>', '/assets/cards/noticia-portal.svg', '', 1, 2, '2026-02-15 10:00:00'),

    ('publicacoes', 'pub-artigo-redes-neurais-2026', 'Artigo: Redes neurais para classificacao de imagens medicas', 'Publicacao em periodico internacional com resultados do laboratorio.', 'Artigo', '<p>Resumo da publicacao com objetivos, metodologia e principais resultados.</p>', '/assets/cards/noticia-pesquisa.svg', 'https://example.org/artigo-redes-neurais', 1, 1, '2026-03-10 09:00:00'),
    ('publicacoes', 'pub-trabalho-evento-educacao-2025', 'Trabalho em evento: IA aplicada a educacao', 'Apresentacao em conferencia nacional de computacao aplicada.', 'Evento', '<p>Trabalho apresentado em evento cientifico com participacao de estudantes do laboratorio.</p>', '/assets/cards/noticia-default.svg', 'https://example.org/evento-ia-educacao', 1, 2, '2025-11-21 09:00:00'),

    ('cursos', 'curso-oficina-python-dados', 'Oficina de Python para Analise de Dados', 'Capacitacao introdutoria para estudantes e bolsistas do laboratorio.', 'Capacitacao', '<p>Oficina com fundamentos de Python, limpeza de dados e visualizacao.</p>', '/assets/cards/noticia-portal.svg', '', 1, 1, '2026-03-05 14:00:00'),
    ('cursos', 'curso-metodologia-pesquisa-aplicada', 'Curso de Metodologia de Pesquisa Aplicada', 'Formacao em desenho experimental e reproducibilidade cientifica.', 'Formacao', '<p>Curso com foco em metodologia cientifica para projetos de graduacao e pos.</p>', '/assets/cards/noticia-pesquisa.svg', '', 1, 2, '2026-02-01 14:00:00'),

    ('parceiros', 'parceria-universidade-federal-a', 'Parceria com Universidade Federal A', 'Cooperacao academica para projetos e coorientacoes.', 'Academico', '<p>Parceria institucional para desenvolvimento de pesquisas colaborativas e intercambio de estudantes.</p>', '/assets/cards/noticia-default.svg', 'https://example.org/parceria-ufa', 1, 1, '2026-03-01 08:30:00'),
    ('parceiros', 'parceria-empresa-laboratorio-tech', 'Parceria com Laboratorio Tech', 'Transferencia de tecnologia e apoio a infraestrutura de pesquisa.', 'Setor Produtivo', '<p>Parceria para validacao de prototipos, mentorias e cooperacao tecnico-cientifica.</p>', '/assets/cards/noticia-portal.svg', 'https://example.org/parceria-tech', 1, 2, '2026-01-25 08:30:00'),

    ('tutoriais', 'tutorial-git-fluxo-pesquisa', 'Tutorial: Fluxo Git para projetos de pesquisa', 'Guia rapido para versionamento e organizacao de codigo em equipe.', 'Tutorial', '<p>Passo a passo para padronizar branchs, revisoes e versionamento no laboratorio.</p>', '/assets/cards/noticia-default.svg', '', 1, 1, '2026-03-18 16:00:00'),
    ('tutoriais', 'tutorial-reprodutibilidade-experimentos', 'Tutorial: Reprodutibilidade de experimentos', 'Boas praticas para registro, execucao e replicacao de resultados.', 'Boas Praticas', '<p>Checklist de reproducibilidade para experimentos computacionais do laboratorio.</p>', '/assets/cards/noticia-pesquisa.svg', '', 1, 2, '2026-02-28 16:00:00'),

    ('blog', 'blog-boas-vindas-novos-bolsistas', 'Boas-vindas aos novos bolsistas', 'Laboratorio recebe nova turma de estudantes para o semestre 2026/1.', 'Comunicado', '<p>Mensagem de boas-vindas e apresentacao das frentes de trabalho do laboratorio.</p>', '/assets/cards/noticia-portal.svg', '', 1, 1, '2026-03-22 11:00:00'),
    ('blog', 'blog-relato-seminario-interno', 'Relato do seminario interno de pesquisa', 'Resumo dos temas apresentados pelos grupos do laboratorio.', 'Evento', '<p>Publicacao com os destaques do seminario interno e proximas acoes planejadas.</p>', '/assets/cards/noticia-pesquisa.svg', '', 1, 2, '2026-03-08 11:00:00'),

    ('eventos', 'evento-workshop-ia-aplicada', 'Workshop de IA Aplicada em Dados Cientificos', 'Workshop presencial para equipe e convidados com foco em pipelines reprodutiveis.', 'Workshop', '<p>Evento tecnico com mini-curso e estudos de caso em IA aplicada a dados cientificos.</p>', '/assets/cards/noticia-pesquisa.svg', '', 1, 1, '2026-04-03 09:00:00'),
    ('eventos', 'evento-seminario-parcerias-lab', 'Seminario de Parcerias e Cooperacao Academica', 'Encontro com grupos parceiros para apresentar resultados e planejar novas colaboracoes.', 'Seminario', '<p>Seminario aberto do laboratorio para integracao entre universidade e parceiros externos.</p>', '/assets/cards/noticia-portal.svg', '', 1, 2, '2026-03-27 14:00:00')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    category = VALUES(category),
    content_html = VALUES(content_html),
    image_url = VALUES(image_url),
    external_url = VALUES(external_url),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order),
    published_at = VALUES(published_at);

INSERT INTO news_items (slug, title, summary, category, content, image, published_at) VALUES
('noticia-laboratorio-abre-vagas-ic-2026-1','Laboratorio abre selecao de bolsistas de iniciacao cientifica (2026/1)','Selecao para estudantes de graduacao atuarem em projetos de pesquisa aplicada.','Pesquisa','O laboratorio convida estudantes interessados em pesquisa para participar do processo seletivo de iniciacao cientifica do semestre 2026/1.','/assets/cards/noticia-pesquisa.svg','2026-04-08 09:00:00'),
('noticia-seminario-metodologia-cientifica','Laboratorio promove seminario de metodologia cientifica','Evento interno para apresentacao de boas praticas de escrita e reproducibilidade de experimentos.','Eventos','Seminario aberto a estudantes e pesquisadores do departamento para alinhamento de metodos de pesquisa e divulgacao cientifica.','/assets/cards/noticia-portal.svg','2026-04-07 14:30:00'),
('noticia-parceria-novo-projeto-iot','Nova parceria impulsiona projeto de IoT do laboratorio','Acordo de cooperacao amplia infraestrutura para experimentos e coleta de dados.','Parcerias','O laboratorio firmou cooperacao com instituicao parceira para fortalecer o desenvolvimento de prototipos IoT e analise de dados em tempo real.','/assets/cards/noticia-default.svg','2026-04-06 10:15:00'),
('noticia-curso-python-dados-inscricoes','Inscricoes abertas para oficina de Python e dados','Capacitacao introdutoria voltada para estudantes e bolsistas do laboratorio.','Capacitacao','A oficina abordara fundamentos de Python, tratamento de dados e visualizacao, com atividades praticas conduzidas pela equipe do laboratorio.','/assets/cards/noticia-portal.svg','2026-04-05 11:20:00'),
('noticia-publicacao-rede-neural-medica','Equipe publica artigo sobre classificacao de imagens medicas','Trabalho apresenta resultados de redes neurais em base de dados clinica.','Publicacoes','A publicacao descreve metodologia, avaliacao e resultados obtidos em experimento de classificacao de imagens medicas desenvolvido no laboratorio.','/assets/cards/noticia-pesquisa.svg','2026-04-04 08:40:00'),
('noticia-recepcao-novos-integrantes','Laboratorio recebe novos integrantes para o semestre','Encontro de boas-vindas apresentou linhas de pesquisa e cronograma de atividades.','Comunicados','A equipe do laboratorio realizou recepcao institucional com apresentacao de projetos em andamento e orientacoes para os novos membros.','/assets/cards/noticia-default.svg','2026-04-03 16:00:00')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    category = VALUES(category),
    content = VALUES(content),
    image = VALUES(image),
    published_at = VALUES(published_at);

INSERT INTO edital_items (slug, title, summary, category, content, image, published_at) VALUES
('edital-bolsa-iniciacao-cientifica-lab-2026-1','Edital de bolsa de iniciacao cientifica do laboratorio (2026/1)','Chamada para selecao de bolsistas em projetos vinculados ao laboratorio e ao departamento.','Editais','Edital com cronograma, criterios de selecao e documentacao exigida para candidatura a bolsa de iniciacao cientifica.','/assets/cards/edital-monitoria.svg','2026-03-20 10:00:00'),
('edital-monitoria-projetos-laboratorio-2026','Edital de monitoria para apoio a projetos do laboratorio','Selecao de monitor(a) para suporte tecnico e organizacao de atividades de pesquisa.','Editais','O laboratorio publica edital para monitoria de apoio a projetos de pesquisa e extensao em parceria com a universidade.','/assets/cards/edital-extensao.svg','2026-03-12 08:00:00')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    category = VALUES(category),
    content = VALUES(content),
    image = VALUES(image),
    published_at = VALUES(published_at);

INSERT INTO defesa_items (slug, title, summary, category, content, image, published_at) VALUES
('defesa-monografia-sistemas-2026-1','Defesa de Monografia - Sistemas de Informacao','Apresentacao final da disciplina de monografia com banca avaliadora.','Defesas','Divulgacao da banca de defesa de monografia com data, horario e local da apresentacao.','/assets/cards/noticia-pesquisa.svg','2026-03-29 14:00:00'),
('defesa-tcc-ia-aplicada-2026-1','Defesa de TCC - IA Aplicada a Educacao','Sessao publica de defesa de trabalho de conclusao de curso.','Defesas','Comunicado oficial de defesa com orientador, banca e tema do trabalho.','/assets/cards/noticia-portal.svg','2026-03-18 15:00:00')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    category = VALUES(category),
    content = VALUES(content),
    image = VALUES(image),
    published_at = VALUES(published_at);

INSERT INTO job_items (slug, title, summary, category, content, image, published_at) VALUES
('vaga-estagio-web-php','Vaga de Estagio em Desenvolvimento Web (PHP)','Empresa parceira busca estudante para atuar com PHP e MySQL.','Carreiras','Oportunidade de estagio com bolsa, atividades de desenvolvimento e suporte a sistemas web.','/assets/cards/noticia-portal.svg','2026-03-27 09:00:00'),
('vaga-dev-junior-backend','Vaga de Desenvolvedor(a) Junior Backend','Processo seletivo para vaga junior com foco em APIs e banco de dados.','Carreiras','Divulgacao de vaga para recem-formados e alunos em fase final com conhecimento em backend.','/assets/cards/noticia-default.svg','2026-03-21 10:00:00')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    category = VALUES(category),
    content = VALUES(content),
    image = VALUES(image),
    published_at = VALUES(published_at);

INSERT INTO people_items (slug, role_type, scope, name, position, degree, website_url, lattes_url, email, phone, room, interests, bio, sort_order) VALUES
('ana-luiza-costa','docente','principal','Ana Luiza Costa','Docente Pesquisadora','Doutora em Ciencia da Computacao','','http://lattes.cnpq.br/','ana.costa@universidade.br','(31) 3559-1101','Laboratorio - Sala 201','Inteligencia artificial e ciencia de dados.','Atua em projetos de IA aplicada no laboratorio.',1),
('marcos-vinicius-lima','funcionario','principal','Marcos Vinicius Lima','Tecnico de Laboratorio','','','','marcos.lima@universidade.br','(31) 3559-1102','Laboratorio - Sala 203','Suporte de infraestrutura e equipamentos.','Responsavel pelo suporte tecnico do laboratorio.',2),
('beatriz-oliveira-santos','estudante_graduacao','principal','Beatriz Oliveira Santos','Estudante de Graduacao (IC)','Graduacao em Ciencia da Computacao','','','beatriz.santos@aluno.universidade.br','','','Visao computacional e aprendizado profundo.','Bolsista de iniciacao cientifica do laboratorio.',3),
('caio-henrique-pereira','estudante_graduacao','principal','Caio Henrique Pereira','Estudante de Graduacao (IC)','Graduacao em Sistemas de Informacao','','','caio.pereira@aluno.universidade.br','','','Engenharia de software e dados.','Participa de projeto de pesquisa aplicada.',4),
('juliana-mendes-araujo','estudante_pos','principal','Juliana Mendes Araujo','Mestranda','Mestrado em Computacao','','','juliana.araujo@pos.universidade.br','','Laboratorio - Sala 205','Mineracao de dados e NLP.','Discente de pos vinculada ao laboratorio.',5),
('rodrigo-almeida-ferraz','estudante_pos','principal','Rodrigo Almeida Ferraz','Doutorando','Doutorado em Computacao','','','rodrigo.ferraz@pos.universidade.br','','Laboratorio - Sala 206','Otimizacao e aprendizado de maquina.','Discente de doutorado em projeto colaborativo.',6)
ON DUPLICATE KEY UPDATE
    role_type = VALUES(role_type),
    scope = VALUES(scope),
    name = VALUES(name),
    position = VALUES(position),
    degree = VALUES(degree),
    website_url = VALUES(website_url),
    lattes_url = VALUES(lattes_url),
    email = VALUES(email),
    phone = VALUES(phone),
    room = VALUES(room),
    interests = VALUES(interests),
    bio = VALUES(bio),
    sort_order = VALUES(sort_order);

INSERT INTO research_labs (slug, name, summary, site_url, is_active, sort_order) VALUES
('csilab','CSILab','Laboratorio de Computacao de Sistemas Inteligentes.','https://csilab.ufop.br/',1,1),
('gaid','GAID','Laboratorio Tematico em Gerencia e Analise Inteligente de Dados.','http://www.decom.ufop.br/gaid/',1,2),
('goal','GOAL','Laboratorio Tematico em Otimizacao e Algoritmos.','http://www.goal.ufop.br',1,3),
('imobilis','iMobilis','Laboratorio Tematico em Computacao Movel.','http://www2.decom.ufop.br/imobilis/',1,4),
('kryptolab','KryptoLab','Laboratorio de Criptografia e Seguranca de Redes.','https://kryptolab.decom.ufop.br',1,5),
('lcad','LCAD','Laboratorio de Computacao Aplicada e Desenvolvimento.','https://lcad.ufop.br/',1,6),
('lapdi','LaPDI','Laboratorio Tematico em Processamento de Imagens.','http://www.decom.ufop.br/lapdi/',1,7),
('terralab','TerraLab','Laboratorio Tematico em Simulacao e Geoprocessamento.','http://www.decom.ufop.br/terralab/',1,8),
('xr4good','XR4Good','Laboratorio Tematico de Realidade Estendida.','http://xr4goodlab.decom.ufop.br/',1,9)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    summary = VALUES(summary),
    site_url = VALUES(site_url),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

INSERT INTO research_projects (slug, title, project_type, summary, site_url, coordinator, is_active, sort_order) VALUES
('ia-apoio-ao-ensino','IA aplicada ao apoio ao ensino','pesquisa','Projeto focado em modelos de aprendizado de maquina para suporte a atividades educacionais.','','DECOM/UFOP',1,1),
('visao-computacional-saude','Visao computacional para aplicacoes em saude','pesquisa','Pesquisa em analise de imagens e reconhecimento de padroes aplicada a contextos de saude.','','DECOM/UFOP',1,2),
('cultura-digital-e-formacao','Cultura digital e formacao em tecnologia','extensao','Projeto de extensao com oficinas e atividades para aproximar comunidade e computacao.','','DECOM/UFOP',1,3),
('programacao-para-escolas','Programacao para escolas publicas','extensao','Acoes extensionistas de ensino de programacao e pensamento computacional para estudantes da rede publica.','','DECOM/UFOP',1,4)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    project_type = VALUES(project_type),
    summary = VALUES(summary),
    site_url = VALUES(site_url),
    coordinator = VALUES(coordinator),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order);

INSERT INTO site_settings (setting_key, setting_value) VALUES
('menu_graduacao_label','Graduacao'),
('menu_graduacao_url','/ensino/ciencia-computacao.php'),
('menu_pos_graduacao_label','Pos-graduacao'),
('menu_pos_graduacao_url','/ensino/pos-graduacao.php')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value);

INSERT INTO ppgcc_page_content
    (id, title, intro_html, ingresso_html, editais_html, grade_html, docencia_html, bolsas_html, graduacao_html)
VALUES
    (
        1,
        'Pos-graduacao em Computacao',
        '<p>O PPGCC/UFOP oferece Mestrado e Doutorado em Ciencia da Computacao, com foco em pesquisa, inovacao tecnologica e formacao docente.</p>',
        '<p>O ingresso ocorre por edital de processo seletivo para cada nivel, com criterios e cronograma publicados oficialmente.</p>',
        '<p>O programa publica editais de ingresso, bolsas e chamadas academicas ao longo do ano.</p>',
        '<p>A grade contempla disciplinas basicas e eletivas, com creditos minimos para mestrado e doutorado.</p>',
        '<p>O estagio em docencia segue regras institucionais e do programa.</p>',
        '<p>Bolsas e auxilios sao regidos por editais e disponibilidade institucional.</p>',
        '<p>Alunos da graduacao podem cursar disciplinas isoladas conforme regras e calendario semestral.</p>'
    )
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    intro_html = VALUES(intro_html),
    ingresso_html = VALUES(ingresso_html),
    editais_html = VALUES(editais_html),
    grade_html = VALUES(grade_html),
    docencia_html = VALUES(docencia_html),
    bolsas_html = VALUES(bolsas_html),
    graduacao_html = VALUES(graduacao_html);

INSERT INTO ppgcc_notices (slug, title, summary, notice_type, notice_url, is_active, published_at) VALUES
('ppgcc-04-2025-ingresso-2026','Edital PPGCC 04/2025 - Ingresso 2026 (Mestrado e Doutorado)','Processo seletivo para ingresso no PPGCC com vagas para Mestrado e Doutorado.','edital','https://www3.decom.ufop.br/pos/processoseletivo/',1,'2025-10-01 09:00:00'),
('ppgcc-02-2026-bolsas-doutorado','Edital PPGCC 02/2026 - Classificacao para bolsas de Doutorado','Chamada para classificacao de discentes de doutorado para manutencao de bolsas (dedicacao parcial).','edital','https://www3.decom.ufop.br/pos/processoseletivo/',1,'2026-03-01 09:00:00'),
('ppgcc-01-2026-pdse','Edital PPGCC 01/2026 - PDSE Doutorado Sanduiche','Selecao interna para o Programa Institucional de Doutorado Sanduiche no Exterior.','edital','https://www3.decom.ufop.br/pos/processoseletivo/',1,'2026-02-10 09:00:00'),
('calendario-isoladas-ppgcc','Calendario e orientacoes de matricula em disciplinas isoladas','Informes para matricula, incluindo orientacao para alunos de graduacao interessados em disciplinas isoladas.','informacao','https://www3.decom.ufop.br/pos/noticias/',1,'2026-01-25 09:00:00')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    notice_type = VALUES(notice_type),
    notice_url = VALUES(notice_url),
    is_active = VALUES(is_active),
    published_at = VALUES(published_at);
