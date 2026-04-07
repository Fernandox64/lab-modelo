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
    role_type ENUM('docente','funcionario') NOT NULL DEFAULT 'docente',
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

DELETE FROM news_items
WHERE slug IN (
    'portal-em-teste',
    'horarios-de-aula-disponiveis',
    'grupo-de-pesquisa-abre-chamada'
);

DELETE FROM edital_items
WHERE slug IN (
    'edital-monitoria-2026-1',
    'edital-bolsas-extensao'
);

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

DELETE FROM people_items
WHERE role_type = 'docente';

DELETE FROM research_labs;
DELETE FROM research_projects;

INSERT INTO news_items (slug, title, summary, category, content, image, published_at) VALUES
('qualificacao-mestrado-eduardo-henke-2026-03-26','Qualificacao de mestrado do discente Eduardo Henke, dia 26/03 as 14:00.','Aviso publicado no acervo de noticias do DECOM-UFOP.','Pesquisa','Comunicado academico referente a qualificacao de mestrado divulgada no portal oficial do departamento.','/assets/cards/noticia-pesquisa.svg','2026-03-26 14:00:00'),
('horario-aulas-decom-2026-1','Horario das aulas do DECOM 2026-1','Atualizacao institucional com informacoes sobre horarios letivos.','Ensino','Publicacao de referencia para consulta de horarios do semestre no DECOM-UFOP.','/assets/cards/noticia-horarios.svg','2026-03-24 09:00:00'),
('defesa-doutorado-guilherme-augusto-2026-03-20','Defesa de doutorado do discente Guilherme Augusto, dia 20/03/2026 as 13:00.','Evento academico de pos-graduacao divulgado no site oficial.','Pesquisa','Comunicado sobre defesa de doutorado conforme agenda publicada no portal do DECOM-UFOP.','/assets/cards/noticia-portal.svg','2026-03-20 13:00:00')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    summary = VALUES(summary),
    category = VALUES(category),
    content = VALUES(content),
    image = VALUES(image),
    published_at = VALUES(published_at);

INSERT INTO edital_items (slug, title, summary, category, content, image, published_at) VALUES
('inicio-matriculas-isoladas-ppgcc-2026-1','Inicio de matriculas em disciplinas isoladas PPGCC - 2026/1','Comunicado academico sobre periodo de matricula em disciplinas isoladas.','Editais','Informacao institucional baseada no acervo de noticias do DECOM-UFOP.','/assets/cards/edital-extensao.svg','2026-03-10 08:00:00'),
('grade-disciplinas-matricula-2026-1','Grade de disciplinas e datas de matricula 2026/1','Publicacao com datas e organizacao academica do periodo 2026/1.','Editais','Aviso institucional para estudantes sobre oferta e calendario de matriculas.','/assets/cards/edital-monitoria.svg','2026-02-12 10:00:00'),
('horarios-monitorias-decom','Horarios Monitorias DECOM','Divulgacao dos horarios de monitorias para disciplinas do departamento.','Editais','Comunicado institucional com lista de monitorias disponiveis para consulta.','/assets/cards/edital-monitoria.svg','2025-08-15 09:00:00')
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

INSERT INTO people_items (slug, role_type, name, position, degree, website_url, lattes_url, email, phone, room, interests, bio, sort_order) VALUES
('aline-norberta-de-brito','docente','Aline Norberta de Brito','Docente','Doutora em Ciencia da Computacao - Universidade Federal de Minas Gerais (UFMG)','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','aline.brito@ufop.edu.br','+55 31 3559-1692','Instituto de Ciencias Exatas e Biologicas - Sala 346','Engenharia de Software, incluindo topicos como Qualidade de Software, Manutencao e Evolucao de Software, e Mineracao de Repositorios de Software.','Perfil de docente do DECOM.',1),
('anderson-almeida-ferreira','docente','Anderson Almeida Ferreira','Docente','Doutor em Ciencia da Computacao - Universidade Federal de Minas Gerais','','http://lattes.cnpq.br/','','','Instituto de Ciencias Exatas e Biologicas - Sala 340','Teoria da Computacao. Bibliotecas Digitais. Bancos de Dados. Gerencia de Dados. Recuperacao de Informacao. Aprendizado de Maquina.','Perfil de docente do DECOM.',2),
('andre-luiz-carvalho-ottoni','docente','Andre Luiz Carvalho Ottoni','Docente','Doutor em Engenharia Eletrica - Universidade Federal da Bahia','','http://lattes.cnpq.br/','','+55 31 35591330','Instituto de Ciencias Exatas e Biologicas - Sala 326','Inteligencia Artificial, Aprendizado Profundo, Aprendizado por Reforco, AutoML e Robotica Inteligente.','Perfil de docente do DECOM.',3),
('andrea-gomes-campos','docente','Andrea Gomes Campos','Docente','Doutora em Fisica Computacional - Universidade de Sao Paulo/Sao Carlos','','http://lattes.cnpq.br/','','+55 (31) 3559 1640','Instituto de Ciencias Exatas e Biologicas - Sala 64','Visao Computacional. Analise e processamento de imagens. Simulacao computacional. Reconhecimento de padroes.','Perfil de docente do DECOM.',4),
('carlos-frederico-m-c-cavalcanti','docente','Carlos Frederico M. C. Cavalcanti','Docente','Doutor em Ciencia da Computacao; Mestre em Ciencia da Computacao; Pos-Graduacao em Analise de Sistemas; Graduacao em Engenharia Eletrica/Eletronica','','http://lattes.cnpq.br/','','+55 (31) 3559-1213','Instituto de Ciencias Exatas e Biologicas - Sala COM09','Estruturas de Ledgers, blockchain, DAGs, arquiteturas descentralizadas, criptografia e seguranca de redes, sistemas distribuidos.','Perfil de docente do DECOM.',5),
('carlos-henrique-gomes-ferreira','docente','Carlos Henrique Gomes Ferreira','Docente','Doutorado em Ciencias da Computacao (UFMG) e em Electrical, Electronics and Communications Engineering (Politecnico di Torino, Italia - co-tutela)','','http://lattes.cnpq.br/','','','','Redes Complexas, Aprendizado de Maquina, Comportamento do Usuario, Processamento de Linguagem Natural.','Perfil de docente do DECOM.',6),
('daniel-ludovico-guidoni','docente','Daniel Ludovico Guidoni','Docente','Doutor em Ciencia da Computacao pela Universidade Federal de Minas Gerais','','http://lattes.cnpq.br/','','','','Redes de Computadores, Comunicacao Sem Fio, Cidades Inteligentes, Redes Veiculares e Ciencia de Dados.','Perfil de docente do DECOM.',7),
('dayanne-gouveia-coelho','docente','Dayanne Gouveia Coelho','Docente','Doutora em Engenharia Eletrica - Universidade Federal de Minas Gerais (UFMG)','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','+55 (31) 3559-1333','Instituto de Ciencias Exatas e Biologicas - Sala COM 46','Otimizacao Multi-objetivo. Meta-heuristicas. Tecnologias de apoio a aprendizagem.','Perfil de docente do DECOM.',8),
('eduardo-jose-da-silva-luz','docente','Eduardo Jose da Silva Luz','Docente','Doutor em Ciencia da Computacao - Universidade Federal de Ouro Preto','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','+55 31 35591330','Instituto de Ciencias Exatas e Biologicas - Sala 342 ICEB III','Redes neurais artificiais, aprendizado de maquina, visao computacional, reconhecimento de padroes e sistemas embarcados.','Perfil de docente do DECOM.',9),
('fernanda-sumika-hojo-de-souza','docente','Fernanda Sumika Hojo de Souza','Docente','Doutora em Ciencia da Computacao pela Universidade Federal de Minas Gerais (UFMG)','','http://lattes.cnpq.br/','','','','Otimizacao Combinatoria, Programacao Inteira, Heuristicas, Analise de Dados, Cidades Inteligentes, Virtualizacao de Redes, Aplicacoes em saude.','Perfil de docente do DECOM.',10),
('fernando-cortez-sica','docente','Fernando Cortez Sica','Docente','Doutor em Engenharia Eletrica (UFMG); Mestre em Engenharia Eletrica (UNICAMP)','','http://lattes.cnpq.br/','','','Instituto de Ciencias Exatas e Biologicas - Sala COM13 (ICEB 3)','Sistemas de Computacao.','Perfil de docente do DECOM.',11),
('gladston-juliano-prates-moreira','docente','Gladston Juliano Prates Moreira','Docente','Doutor em Engenharia Eletrica, Mestre e Bacharel em Matematica - UFMG','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','+55 (31) 3559-1330','Instituto de Ciencias Exatas e Biologicas - Sala 342 - ICEB III','Otimizacao Multi-objetivo, Modelos Analiticos e de Simulacao, Reconhecimento de Padroes, Computacao Evolutiva, Estatistica Espacial.','Perfil de docente do DECOM.',12),
('guilherme-tavares-de-assis','docente','Guilherme Tavares de Assis','Docente','Doutor em Ciencia da Computacao - DCC/UFMG, 2008','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','+55 (31) 3559-1692','Instituto de Ciencias Exatas e Biologicas - Sala COM17','Banco de Dados, Gerencia de Dados, Biblioteca Digital, Recuperacao de Informacao, Coleta de Paginas Web, Algoritmos e Estruturas de Dados, Tecnologia Educacional.','Perfil de docente do DECOM.',13),
('guillermo-camara-chavez','docente','Guillermo Camara Chavez','Docente','Doutor em Ciencia da Computacao - Universidade Federal de Minas Gerais','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','','Instituto de Ciencias Exatas e Biologicas - Sala COM09','Processamento digital em video. Reconhecimento de padroes invariantes. Redes neurais.','Perfil de docente do DECOM.',14),
('gustavo-peixoto-silva','docente','Gustavo Peixoto Silva','Docente','Doutor em Engenharia de Transportes - Universidade de Sao Paulo','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','','Instituto de Ciencias Exatas e Biologicas - Sala COM05','Algoritmos de Fluxo em Rede. Otimizacao Combinatoria. Otimizacao de Transporte.','Perfil de docente do DECOM.',15),
('ivair-ramos-silva','docente','Ivair Ramos Silva','Docente','Doutorado em Estatistica - Universidade Federal de Minas Gerais','','http://lattes.cnpq.br/','','','','Metodos Monte Carlo, Otimizacao em Analise Sequencial, Analise em Alta Dimensao, Estatistica Espacial e Inferencia em Processos Estocasticos.','Perfil de docente do DECOM.',16),
('jadson-castro-gertrudes','docente','Jadson Castro Gertrudes','Docente','Doutor em Ciencias da Computacao e Matematica Computacional pela Universidade de Sao Paulo (2019)','','http://lattes.cnpq.br/','','+55 (31)3559-1319','Instituto de Ciencias Exatas e Biologicas - Sala 18 (ICEB III)','Aprendizado supervisionado, nao supervisionado e semissupervisionado; analise quantitativa/qualitativa entre estrutura quimica e atividade biologica.','Perfil de docente do DECOM.',17),
('jose-romildo-malaquias','docente','Jose Romildo Malaquias','Docente','Doutor em Engenharia Eletrica - Universidade Federal de Uberlandia; Mestre em Ciencias - ITA','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','+55 (31) 3559-1645','Instituto de Ciencias Exatas e Biologicas - Sala ICEB 3 Sala 21','Linguagens de programacao.','Perfil de docente do DECOM.',18),
('joubert-de-castro-lima','docente','Joubert de Castro Lima','Docente','Doutor em Engenharia Eletronica e Computacao - ITA','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','','Instituto de Ciencias Exatas e Biologicas - Sala COM15','Sistemas Distribuidos e Banco de Dados.','Perfil de docente do DECOM.',19),
('marcelo-luiz-silva','docente','Marcelo Luiz Silva','Docente','Mestre em Engenharia Eletrica - Universidade Federal de Uberlandia','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','','Instituto de Ciencias Exatas e Biologicas - Sala COM17','Realidade virtual. Informatica na educacao.','Perfil de docente do DECOM.',20),
('marco-antonio-moreira-de-carvalho','docente','Marco Antonio Moreira de Carvalho','Docente','Doutor em Engenharia Eletronica e Computacao - ITA','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','+55 (31) 3559-1663','Instituto de Ciencias Exatas e Biologicas - Sala COM45','Otimizacao Combinatoria. Pesquisa Operacional. Heuristicas ad hoc. Metaheuristicas.','Perfil de docente do DECOM.',21),
('pablo-luiz-araujo-munhoz','docente','Pablo Luiz Araujo Munhoz','Docente','Doutor em Computacao pela UFF e pela Universite d''Avignon et de Pays de Vaucluse (Franca) (2017)','','http://lattes.cnpq.br/','','+55 (31) 3889-1666','Instituto de Ciencias Exatas e Biologicas - Sala 374','Pesquisa Operacional; Otimizacao Combinatoria; Meta-heuristicas.','Perfil de docente do DECOM.',22),
('pedro-henrique-lopes-silva','docente','Pedro Henrique Lopes Silva','Docente','Doutor em Ciencia da Computacao pela UFOP - 2022','','http://lattes.cnpq.br/','','+55 31 3559-1303','Instituto de Ciencias Exatas e Biologicas - Sala 354 ICEB III','Deep Learning, Otimizacao Multiobjetivo e Metric Learning.','Perfil de docente do DECOM.',23),
('puca-huachi-vaz-penna','docente','Puca Huachi Vaz Penna','Docente','Doutor em Computacao pela Universidade Federal Fluminense (2013)','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','','','Pesquisa Operacional; Otimizacao Combinatoria; Meta-heuristicas; Problema de Roteamento de Veiculos.','Perfil de docente do DECOM.',24),
('rafael-alves-bonfim-de-queiroz','docente','Rafael Alves Bonfim de Queiroz','Docente','Doutor em Modelagem Computacional - LNCC','','http://lattes.cnpq.br/','','','','Matematica Computacional, Modelagem de Sistemas Fisiologicos, Dinamica dos Fluidos Computacional, Scientific Machine Learning e Explainable AI.','Perfil de docente do DECOM.',25),
('reinaldo-silva-fortes','docente','Reinaldo Silva Fortes','Docente','Doutor em Ciencia da Computacao - UFMG','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','+55 (31) 3559-1327','Instituto de Ciencias Exatas e Biologicas - Sala 358','Mineracao de Dados. Recuperacao da Informacao. Sistemas de Recomendacao. Televisao Interativa. Ensino de Programacao e Pensamento Computacional.','Perfil de docente do DECOM.',26),
('ricardo-augusto-rabelo-oliveira','docente','Ricardo Augusto Rabelo Oliveira','Docente','Doutor em Ciencia da Computacao - Universidade Federal de Minas Gerais','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','','Instituto de Ciencias Exatas e Biologicas - Sala COM15','Computacao Movel. Redes sem fio. Sistemas Embarcados. Sistemas operacionais. Arquitetura de computadores.','Perfil de docente do DECOM.',27),
('rodrigo-cesar-pedrosa-silva','docente','Rodrigo Cesar Pedrosa Silva','Docente','Ph.D. em Engenharia Eletrica - McGill University (Canada), 2018','','http://lattes.cnpq.br/','','','','Aprendizado de maquina, soft-computing, otimizacao nao-linear e multi-objetivo, inteligencia computacional.','Perfil de docente do DECOM.',28),
('rodrigo-geraldo-ribeiro','docente','Rodrigo Geraldo Ribeiro','Docente','Doutor em Ciencia da Computacao - Universidade Federal de Minas Gerais','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','','','Teoria de tipos, projeto e implementacao de linguagens de programacao e verificacao formal.','Perfil de docente do DECOM.',29),
('saul-emanuel-delabrida-silva','docente','Saul Emanuel Delabrida Silva','Docente','Doutor em Ciencia da Computacao - DECOM/UFOP, 2018','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','','Instituto de Ciencias Exatas e Biologicas - Sala 358','Computacao Vestivel, Realidade Aumentada, Realidade Virtual, Computacao Ubiqua, Industria 4.0, IHC.','Perfil de docente do DECOM.',30),
('tiago-garcia-de-senna-carneiro','docente','Tiago Garcia de Senna Carneiro','Docente','Doutor em Computacao Aplicada - INPE','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','','Instituto de Ciencias Exatas e Biologicas - Sala COM13','Computacao cientifica. Modelagem analitica e de simulacao. Geoinformatica. Desenvolvimento de Software. Inovacao tecnologica.','Perfil de docente do DECOM.',31),
('valeria-de-carvalho-santos','docente','Valeria de Carvalho Santos','Docente','','','http://lattes.cnpq.br/','','+55 3359-1640','Instituto de Ciencias Exatas e Biologicas - Sala 362','Aprendizado de Maquina, Algoritmos Evolutivos, Robotica.','Perfil de docente do DECOM.',32),
('vander-luis-de-souza-freitas','docente','Vander Luis de Souza Freitas','Docente','Doutorado em Computacao Aplicada - INPE','https://www3.decom.ufop.br/decom/inicio/','http://lattes.cnpq.br/','','+55 (31) 3559-1320','Instituto de Ciencias Exatas e Biologicas - Sala 348','Redes Complexas, Aprendizado de Maquina e Sistemas Dinamicos.','Perfil de docente do DECOM.',33),
('vinicius-antonio-de-oliveira-martins','docente','Vinicius Antonio de Oliveira Martins','Docente','Mestre em Engenharia Eletrica - Universidade de Sao Paulo (USP)','','http://lattes.cnpq.br/','','+55 (31) 3559-1301','Instituto de Ciencias Exatas e Biologicas - Sala 13','Sistemas, Sistemas Embarcados e Tempo Real, Projeto de Circuitos Integrados, Verificacao de Circuitos Integrados.','Perfil de docente do DECOM.',34),
('mariana-souza-almeida','funcionario','Mariana Souza Almeida','Secretaria Administrativa','','','','mariana.almeida@ufop.edu.br','+55 31 3559-1692','Instituto de Ciencias Exatas e Biologicas','Atendimento academico e administrativo.','Atendimento academico e administrativo do departamento.',100)
ON DUPLICATE KEY UPDATE
    role_type = VALUES(role_type),
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
