<?php
declare(strict_types=1);

function apply_security_headers(): void {
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

apply_security_headers();

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $rememberSeconds = 60 * 60 * 24 * 30;
    @ini_set('session.gc_maxlifetime', (string)$rememberSeconds);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

const SITE_NAME = 'Departamento de Computação';
const SITE_SIGLA = 'DECOM';
const SITE_UNIVERSITY = 'Universidade Federal de Ouro Preto';
const SITE_EMAIL = 'decom@ufop.edu.br';
const SITE_PHONE = '+55 31 3559-1692';
const SITE_ADDRESS = 'Campus Morro do Cruzeiro, Ouro Preto - MG';

const ADMIN_MAX_LOGIN_ATTEMPTS = 5;
const ADMIN_LOCKOUT_SECONDS = 900;

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = getenv('DB_HOST') ?: 'db';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'newsdb';
    $username = getenv('DB_USERNAME') ?: 'newsuser';
    $password = getenv('DB_PASSWORD') ?: 'newspass';
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    return $pdo;
}
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function sanitize_rich_text(string $html): string {
    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><h4><blockquote><img><table><thead><tbody><tr><td><th><hr>';
    $clean = strip_tags($html, $allowed);
    $clean = preg_replace('/\sstyle\s*=\s*("|\').*?\1/iu', '', $clean) ?? $clean;
    $clean = preg_replace('/\son\w+\s*=\s*("|\').*?\1/iu', '', $clean) ?? $clean;
    $clean = preg_replace('/href\s*=\s*("|\')\s*javascript:[^"\']*\1/iu', 'href="#"', $clean) ?? $clean;
    return trim($clean);
}
function render_rich_text(string $content): string {
    $safe = sanitize_rich_text($content);
    if ($safe === '') {
        return '';
    }
    $hasTag = preg_match('/<\s*[a-z][^>]*>/i', $safe) === 1;
    if (!$hasTag) {
        return nl2br(e($safe));
    }
    return $safe;
}
function page_header(string $title): void { $pageTitle = $title; require __DIR__ . '/header.php'; }
function page_footer(): void { require __DIR__ . '/footer.php'; }
function is_admin_logged_in(): bool { return !empty($_SESSION['admin_ok']); }
function redirect(string $path): void { header("Location: {$path}"); exit; }
function require_admin(): void { if (!is_admin_logged_in()) { redirect('/admin/login.php'); } }
function ensure_site_settings_table(): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS site_settings (
                setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('Failed ensuring site_settings table: ' . $e->getMessage());
    }
}
function site_setting_get(string $key, string $default = ''): string {
    ensure_site_settings_table();
    try {
        $stmt = db()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :k');
        $stmt->execute([':k' => $key]);
        $value = $stmt->fetchColumn();
        if ($value === false || $value === null) {
            return $default;
        }
        return (string)$value;
    } catch (Throwable $e) {
        error_log('Failed loading site setting: ' . $e->getMessage());
        return $default;
    }
}
function site_setting_set(string $key, string $value): void {
    ensure_site_settings_table();
    $stmt = db()->prepare(
        'INSERT INTO site_settings (setting_key, setting_value)
         VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([':k' => $key, ':v' => $value]);
}
function hero_carousel_defaults(): array {
    return [
        [
            'image' => '/assets/images/carousel/decom-campus.png',
            'badge' => 'Departamento de Computacao',
            'title' => 'Portal Institucional do DECOM/UFOP',
            'text' => 'Comunicacao academica e administrativa com noticias, editais, defesas e servicos para alunos.',
        ],
        [
            'image' => '/assets/images/carousel/ufop-campus-map.png',
            'badge' => 'Ensino e Estrutura',
            'title' => 'Informacoes de cursos, horarios e atendimento',
            'text' => 'Acesso rapido para graduacao, pos, monografias e servicos ao estudante.',
        ],
        [
            'image' => '/assets/images/carousel/tech-circuit.jpg',
            'badge' => 'Pesquisa e Inovacao',
            'title' => 'Tecnologia, ciencia de dados e inteligencia artificial',
            'text' => 'Projetos, laboratorios e iniciativas do DECOM para formacao e impacto social.',
        ],
    ];
}
function hero_carousel_get(): array {
    $defaults = hero_carousel_defaults();
    $slides = [];
    for ($i = 1; $i <= 3; $i++) {
        $d = $defaults[$i - 1];
        $slides[] = [
            'image' => trim(site_setting_get("hero_slide_{$i}_image", $d['image'])),
            'badge' => trim(site_setting_get("hero_slide_{$i}_badge", $d['badge'])),
            'title' => trim(site_setting_get("hero_slide_{$i}_title", $d['title'])),
            'text' => trim(site_setting_get("hero_slide_{$i}_text", $d['text'])),
        ];
    }
    return $slides;
}
function hero_carousel_save(array $slides): void {
    $defaults = hero_carousel_defaults();
    for ($i = 1; $i <= 3; $i++) {
        $input = $slides[$i - 1] ?? [];
        $d = $defaults[$i - 1];
        $image = trim((string)($input['image'] ?? $d['image']));
        $badge = trim((string)($input['badge'] ?? $d['badge']));
        $title = trim((string)($input['title'] ?? $d['title']));
        $text = trim((string)($input['text'] ?? $d['text']));
        site_setting_set("hero_slide_{$i}_image", $image !== '' ? $image : $d['image']);
        site_setting_set("hero_slide_{$i}_badge", $badge !== '' ? $badge : $d['badge']);
        site_setting_set("hero_slide_{$i}_title", $title !== '' ? $title : $d['title']);
        site_setting_set("hero_slide_{$i}_text", $text !== '' ? $text : $d['text']);
    }
}
function horarios_cc_2026_template_html(): string {
    return <<<'HTML'
<h2>Horarios de Aula - Bacharelado em Ciencia da Computacao (2026-1)</h2>
<p><strong>Acesso Rapido:</strong> 1o, 2o, 3o, 4o, 5o, 6o, 7o, 8o periodo e Eletivas.</p>

<h3>1o Periodo</h3>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
<thead><tr><th>Horario</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sabado</th></tr></thead>
<tbody>
<tr><td>07:30 - 09:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>08:20 - 10:00</td><td></td><td>BCC201</td><td></td><td>BCC201</td><td></td><td></td></tr>
<tr><td>10:10 - 11:50</td><td></td><td>BCC201</td><td></td><td>BCC201</td><td></td><td></td></tr>
<tr><td>13:30 - 15:10</td><td>BCC109 (P) / BCC265 (P)</td><td>BCC109 / BCC265</td><td>BCC201 (P)</td><td>BCC109 / BCC265</td><td></td><td></td></tr>
<tr><td>15:20 - 17:00</td><td></td><td></td><td>BCC201 (P)</td><td></td><td>BCC501</td><td></td></tr>
<tr><td>17:10 - 18:50</td><td>BCC109 (P) / BCC201 / BCC265 (P)</td><td>BCC201 (P)</td><td>BCC201</td><td></td><td></td><td></td></tr>
<tr><td>19:00 - 20:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>21:00 - 22:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
</tbody>
</table>
</div>
<p><small>BCC109 - Eletronica para Computacao | BCC201 - Introducao a Programacao | BCC265 - Eletronica para Computacao | BCC501 - Introducao a Ciencia da Computacao | MTM122 - Calculo Diferencial e Integral I | MTM131 - Geometria Analitica e Calculo Vetorial</small></p>

<h3>2o Periodo</h3>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
<thead><tr><th>Horario</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sabado</th></tr></thead>
<tbody>
<tr><td>07:30 - 09:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>08:20 - 10:00</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>10:10 - 11:50</td><td>BCC324</td><td>BCC101 / BCC202</td><td>BCC324</td><td>BCC101 / BCC202</td><td></td><td></td></tr>
<tr><td>13:30 - 15:10</td><td>BCC101</td><td>BCC266</td><td>BCC101</td><td>BCC266</td><td></td><td></td></tr>
<tr><td>15:20 - 17:00</td><td></td><td></td><td></td><td></td><td>BCC202 (P)</td><td></td></tr>
<tr><td>17:10 - 18:50</td><td></td><td></td><td></td><td></td><td>BCC202 (P)</td><td></td></tr>
<tr><td>19:00 - 20:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>21:00 - 22:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
</tbody>
</table>
</div>
<p><small>BCC101 - Matematica Discreta I | BCC202 - Estruturas de Dados I | BCC266 - Organizacao de Computadores | BCC324 - Interacao Humano-Computador | MTM123 - Calculo Diferencial e Integral II</small></p>

<h3>3o Periodo</h3>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
<thead><tr><th>Horario</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sabado</th></tr></thead>
<tbody>
<tr><td>07:30 - 09:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>08:20 - 10:00</td><td>BCC222</td><td></td><td>BCC222 (P)</td><td></td><td></td><td></td></tr>
<tr><td>10:10 - 11:50</td><td></td><td>BCC203</td><td>BCC222 (P)</td><td>BCC203</td><td></td><td></td></tr>
<tr><td>13:30 - 15:10</td><td>BCC102</td><td>BCC263</td><td>BCC102</td><td>BCC263</td><td></td><td></td></tr>
<tr><td>15:20 - 17:00</td><td></td><td>BCC221</td><td></td><td>BCC221</td><td></td><td></td></tr>
<tr><td>17:10 - 18:50</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>19:00 - 20:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>21:00 - 22:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
</tbody>
</table>
</div>
<p><small>BCC102 - Matematica Discreta II | BCC203 - Estrutura de Dados II | BCC221 - Programacao Orientada a Objetos | BCC222 - Programacao Funcional | BCC263 - Arquitetura de Computadores | MTM112 - Introducao a Algebra Linear</small></p>

<h3>4o Periodo</h3>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
<thead><tr><th>Horario</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sabado</th></tr></thead>
<tbody>
<tr><td>07:30 - 09:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>08:20 - 10:00</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>10:10 - 11:50</td><td>BCC204</td><td>BCC361</td><td>BCC204</td><td>BCC361</td><td></td><td></td></tr>
<tr><td>13:30 - 15:10</td><td></td><td>BCC264</td><td></td><td>BCC264</td><td></td><td></td></tr>
<tr><td>15:20 - 17:00</td><td>BCC760 (P)</td><td>BCC322</td><td>BCC760</td><td>BCC322</td><td></td><td></td></tr>
<tr><td>17:10 - 18:50</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>19:00 - 20:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>21:00 - 22:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
</tbody>
</table>
</div>
<p><small>BCC204 - Teoria dos Grafos | BCC264 - Sistemas Operacionais | BCC322 - Engenharia de Software I | BCC361 - Redes de Computadores | BCC760 - Calculo Numerico | EST202 - Estatistica e Probabilidade</small></p>

<h3>5o Periodo</h3>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
<thead><tr><th>Horario</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sabado</th></tr></thead>
<tbody>
<tr><td>07:30 - 09:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>08:20 - 10:00</td><td>BCC323</td><td></td><td>BCC323</td><td></td><td></td><td></td></tr>
<tr><td>10:10 - 11:50</td><td>BCC244</td><td>BCC362</td><td>BCC244</td><td>BCC362</td><td></td><td></td></tr>
<tr><td>13:30 - 15:10</td><td>BCC241</td><td>BCC321</td><td>BCC241</td><td>BCC321</td><td></td><td></td></tr>
<tr><td>15:20 - 17:00</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>17:10 - 18:50</td><td></td><td></td><td></td><td></td><td>BCC502</td><td></td></tr>
<tr><td>19:00 - 20:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>21:00 - 22:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
</tbody>
</table>
</div>
<p><small>BCC241 - Projeto e Analise de Algoritmos | BCC244 - Teoria da Computacao | BCC321 - Banco de Dados I | BCC323 - Engenharia de Software II | BCC362 - Sistemas Distribuidos | BCC502 - Metodologia Cientifica em Ciencia da Computacao</small></p>

<h3>6o Periodo</h3>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
<thead><tr><th>Horario</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sabado</th></tr></thead>
<tbody>
<tr><td>07:30 - 09:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>08:20 - 10:00</td><td>BCC328</td><td></td><td>BCC328</td><td></td><td></td><td></td></tr>
<tr><td>10:10 - 11:50</td><td></td><td>BCC342</td><td></td><td>BCC342</td><td></td><td></td></tr>
<tr><td>13:30 - 15:10</td><td>BCC325</td><td>BCC326</td><td>BCC325</td><td>BCC326</td><td></td><td></td></tr>
<tr><td>15:20 - 17:00</td><td>BCC327</td><td></td><td>BCC327</td><td></td><td></td><td></td></tr>
<tr><td>17:10 - 18:50</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>19:00 - 20:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>21:00 - 22:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
</tbody>
</table>
</div>
<p><small>BCC325 - Inteligencia Artificial | BCC326 - Processamento de Imagens | BCC327 - Computacao Grafica | BCC328 - Construcao de Compiladores I | BCC342 - Introducao a Otimizacao</small></p>

<h3>7o Periodo</h3>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
<thead><tr><th>Horario</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sabado</th></tr></thead>
<tbody>
<tr><td>07:30 - 09:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>08:20 - 10:00</td><td></td><td></td><td></td><td></td><td></td><td>BCC392</td></tr>
<tr><td>10:10 - 11:50</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>13:30 - 15:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>15:20 - 17:00</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>17:10 - 18:50</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>19:00 - 20:40</td><td></td><td>BCC503</td><td></td><td></td><td></td><td></td></tr>
<tr><td>21:00 - 22:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
</tbody>
</table>
</div>
<p><small>BCC392 - Monografia I | BCC503 - Informatica e Sociedade | FIL101 - Introducao a Historia da Filosofia</small></p>

<h3>8o Periodo</h3>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
<thead><tr><th>Horario</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sabado</th></tr></thead>
<tbody>
<tr><td>07:30 - 09:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>08:20 - 10:00</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>10:10 - 11:50</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>13:30 - 15:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>15:20 - 17:00</td><td></td><td></td><td></td><td></td><td></td><td>BCC393</td></tr>
<tr><td>17:10 - 18:50</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>19:00 - 20:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>21:00 - 22:40</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
</tbody>
</table>
</div>
<p><small>BCC393 - Monografia II | DIR260 - Direito da Informatica</small></p>

<h3>Disciplinas Eletivas (oferecidas em 2026-1)</h3>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle">
<thead><tr><th>Horario</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sabado</th></tr></thead>
<tbody>
<tr><td>07:30 - 09:10</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>08:20 - 10:00</td><td>BCC444</td><td>BCC409 / BCC447</td><td>BCC444</td><td>BCC409 / BCC447</td><td></td><td></td></tr>
<tr><td>10:10 - 11:50</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
<tr><td>13:30 - 15:10</td><td>BCC109 (P) / BCC404 / BCC463 / BCC481</td><td>BCC109 / BCC443</td><td>BCC404 / BCC463 / BCC481</td><td>BCC109 / BCC443</td><td></td><td></td></tr>
<tr><td>15:20 - 17:00</td><td></td><td>BCC421 / BCC423 / BCC448</td><td></td><td>BCC421 / BCC423 / BCC448</td><td></td><td></td></tr>
<tr><td>17:10 - 18:50</td><td>BCC109 (P) / BCC402</td><td></td><td>BCC402</td><td></td><td>BCC425 (P)</td><td></td></tr>
<tr><td>19:00 - 20:40</td><td>BCC465</td><td></td><td>BCC465</td><td></td><td></td><td></td></tr>
<tr><td>21:00 - 22:40</td><td></td><td></td><td>BCC425</td><td></td><td>BCC425 (P)</td><td></td></tr>
</tbody>
</table>
</div>
<p><small>BCC109 - Eletronica para Computacao | BCC402 - Algoritmos e Programacao Avancada | BCC404 - Logica Aplicada a Computacao | BCC409 - Sistemas de Recomendacao | BCC421 - Computacao Movel | BCC423 - Criptografia e Seguranca de Sistemas | BCC425 - Sistemas Embutidos | BCC443 - Geoprocessamento e SIG | BCC444 - Mineracao de Dados | BCC447 - Programacao Paralela | BCC448 - Reconhecimento de Padroes | BCC463 - Otimizacao em Redes | BCC465 - Tecnicas de Otimizacao Multi-objetivo | BCC481 - Programacao Web</small></p>
HTML;
}
function horarios_cc_2026_outras_eletivas_html(): string {
    return <<<'HTML'
<h3>Outras eletivas (nao oferecidas em 2026-1)</h3>
<ul>
<li>BCC113 - Introducao ao Aprendizado de Maquina</li>
<li>BCC124 - Redes Complexas</li>
<li>BCC242 - Linguagens Formais e Automatos</li>
<li>BCC243 - Computabilidade</li>
<li>BCC261 - Sistemas de Computacao</li>
<li>BCC401 - Metodologia de Pesquisa em Ciencia da Computacao</li>
<li>BCC403 - Interface de Usuario Avancada para Wearable Computing</li>
<li>BCC405 - Otimizacao Nao Linear</li>
<li>BCC406 - Redes Neurais e Aprendizagem em Profundidade</li>
<li>BCC407 - Projeto e Analise de Experimentos Computacionais</li>
<li>BCC408 - Projeto de Circuitos Logicos Integrados usando HDL</li>
<li>BCC410 - Laboratorio de Startups</li>
<li>BCC422 - Computacao nas Nuvens</li>
<li>BCC424 - Redes de Sensores Sem Fio</li>
<li>BCC426 - Sistemas Tolerantes a Falhas</li>
<li>BCC427 - Teoria da Informacao</li>
<li>BCC428 - Analise de Midia Social</li>
<li>BCC441 - Banco de Dados II</li>
<li>BCC442 - Construcao de Compiladores II</li>
<li>BCC445 - Modelagem e Simulacao de Sistemas Terrestres</li>
<li>BCC446 - Programacao em Logica</li>
<li>BCC449 - Recuperacao de Informacao na Web</li>
<li>BCC450 - Gerencia de Dados na Web</li>
<li>BCC451 - Mineracao Web</li>
<li>BCC461 - Computacao Evolutiva</li>
<li>BCC462 - Inteligencia Computacional</li>
<li>BCC464 - Otimizacao Linear e Inteira</li>
<li>BCC466 - Tecnicas Metaheuristicas para Otimizacao Combinatoria</li>
<li>BCC482 - Gerencia de Projetos de Software</li>
<li>BCC483 - Qualidade de Software</li>
<li>BCC484 - Programacao para Dispositivos Moveis</li>
<li>BCC485 - Design de Interacao</li>
<li>BCC486 - Avaliacao de Sistemas Interativos</li>
<li>BCC487 - Dependabilidade</li>
<li>BCC488 - Programacao Funcional Avancada</li>
<li>BCC489 - Programacao Funcional e Desenvolvimento de Aplicacoes</li>
<li>BCC505 - Mineracao Web</li>
<li>BCC601 - Educacao a Distancia</li>
<li>BCC602 - Otimizacao em Cadeias de Suprimentos</li>
<li>BCC900 - Tecnologias Inovadoras I</li>
<li>BCC901 - Tecnologias Inovadoras II</li>
<li>BCC902 - Tecnologias Inovadoras III</li>
<li>BCC903 - Tecnologias Inovadoras IV</li>
<li>BCC904 - Topicos em Ciencia da Computacao I</li>
<li>BCC905 - Topicos em Ciencia da Computacao II</li>
<li>BCC906 - Tecnologias Emergentes na Computacao I</li>
<li>BCC907 - Tecnologias Emergentes na Computacao II</li>
<li>CAT141 - Teoria de Controle I</li>
<li>FIS216 - Fisica Eletro-eletronica</li>
<li>FIS827 - Introducao a Informacao Quantica</li>
<li>LET966 - Introducao a Libras</li>
<li>PRO315 - Logistica</li>
</ul>
HTML;
}
function horarios_default_data(): array {
    return [
        'title' => 'Horarios de Aula',
        'summary' => 'Consulta organizada dos horarios de aula por curso, periodo e turma.',
        'intro_html' => '<p>Consulte abaixo os horarios atualizados para alunos. A secretaria pode editar este conteudo pelo painel admin.</p>',
        'schedule_html' => horarios_cc_2026_template_html(),
        'other_electives_html' => horarios_cc_2026_outras_eletivas_html(),
        'links_html' => '<ul><li><a href="https://zeppelin10.ufop.br/HorarioAulas/index.xhtml" target="_blank" rel="noopener">Horario de Aulas UFOP (oficial)</a></li></ul>',
        'source_url' => 'https://zeppelin10.ufop.br/HorarioAulas/index.xhtml',
        'last_sync' => '',
    ];
}
function horarios_page_get(): array {
    $d = horarios_default_data();
    return [
        'title' => trim(site_setting_get('horarios_title', $d['title'])),
        'summary' => trim(site_setting_get('horarios_summary', $d['summary'])),
        'intro_html' => site_setting_get('horarios_intro_html', $d['intro_html']),
        'schedule_html' => site_setting_get('horarios_schedule_html', $d['schedule_html']),
        'other_electives_html' => site_setting_get('horarios_other_electives_html', $d['other_electives_html']),
        'links_html' => site_setting_get('horarios_links_html', $d['links_html']),
        'source_url' => trim(site_setting_get('horarios_source_url', $d['source_url'])),
        'last_sync' => trim(site_setting_get('horarios_last_sync', $d['last_sync'])),
    ];
}
function horarios_page_save(array $data): void {
    $d = horarios_default_data();
    $title = trim((string)($data['title'] ?? $d['title']));
    $summary = trim((string)($data['summary'] ?? $d['summary']));
    $intro = sanitize_rich_text((string)($data['intro_html'] ?? $d['intro_html']));
    $schedule = sanitize_rich_text((string)($data['schedule_html'] ?? $d['schedule_html']));
    $otherElectives = sanitize_rich_text((string)($data['other_electives_html'] ?? $d['other_electives_html']));
    $links = sanitize_rich_text((string)($data['links_html'] ?? $d['links_html']));
    $source = trim((string)($data['source_url'] ?? $d['source_url']));
    if ($title === '') {
        $title = $d['title'];
    }
    if ($summary === '') {
        $summary = $d['summary'];
    }
    site_setting_set('horarios_title', $title);
    site_setting_set('horarios_summary', $summary);
    site_setting_set('horarios_intro_html', $intro);
    site_setting_set('horarios_schedule_html', $schedule);
    site_setting_set('horarios_other_electives_html', $otherElectives);
    site_setting_set('horarios_links_html', $links);
    site_setting_set('horarios_source_url', $source !== '' ? $source : $d['source_url']);
}
function horarios_import_from_legacy(?string $sourceUrl = null): array {
    $current = horarios_page_get();
    $url = trim((string)($sourceUrl ?? $current['source_url']));
    if ($url === '' || preg_match('~^https?://~i', $url) !== 1) {
        return ['ok' => false, 'message' => 'URL de origem invalida.'];
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => 45, 'header' => "User-Agent: decom-horarios-import/1.0\r\n"],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $html = @file_get_contents($url, false, $ctx);
    if ($html === false) {
        return ['ok' => false, 'message' => 'Nao foi possivel acessar a pagina de horarios antiga.'];
    }
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8,ISO-8859-1,Windows-1252'));
    libxml_clear_errors();
    $xp = new DOMXPath($dom);
    $items = [];
    foreach ($xp->query('//a[@href]') as $a) {
        $href = trim((string)$a->getAttribute('href'));
        $label = trim(preg_replace('/\s+/u', ' ', (string)$a->textContent) ?? '');
        if ($href === '' || $label === '') {
            continue;
        }
        if (str_starts_with($href, '#') || str_starts_with(strtolower($href), 'mailto:')) {
            continue;
        }
        if (str_starts_with($href, '/')) {
            $href = 'https://www3.decom.ufop.br' . $href;
        } elseif (preg_match('~^https?://~i', $href) !== 1) {
            $href = rtrim($url, '/') . '/' . ltrim($href, './');
        }
        $h = strtolower($href);
        $isFile = preg_match('/\.(pdf|xls|xlsx|ods|doc|docx)$/i', $h) === 1;
        $isHorario = str_contains($h, 'horario') || str_contains(mb_strtolower($label, 'UTF-8'), 'horario');
        if (!$isFile && !$isHorario) {
            continue;
        }
        $key = md5($href . '|' . $label);
        $items[$key] = ['label' => $label, 'url' => $href];
        if (count($items) >= 120) {
            break;
        }
    }
    if (empty($items)) {
        return ['ok' => false, 'message' => 'Nenhum link de horario encontrado na pagina antiga.'];
    }
    $htmlLinks = "<ul>\n";
    foreach ($items as $it) {
        $htmlLinks .= '<li><a target="_blank" rel="noopener" href="' . htmlspecialchars($it['url'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8') . "</a></li>\n";
    }
    $htmlLinks .= "</ul>";
    horarios_page_save([
        'title' => $current['title'],
        'summary' => $current['summary'],
        'intro_html' => $current['intro_html'],
        'links_html' => $htmlLinks,
        'source_url' => $url,
    ]);
    site_setting_set('horarios_last_sync', date('Y-m-d H:i:s'));
    return ['ok' => true, 'count' => count($items), 'message' => 'Links importados com sucesso.'];
}
function atendimento_docentes_generate_table_html(): string {
    $docentes = docentes();
    if (empty($docentes)) {
        $docentes = [
            ['name' => 'Docente 1', 'room' => 'Sala COM01'],
            ['name' => 'Docente 2', 'room' => 'Sala COM02'],
            ['name' => 'Docente 3', 'room' => 'Sala COM03'],
        ];
    }
    $slots = ['08:30 - 10:30', '10:30 - 12:00', '13:30 - 15:30', '15:30 - 17:30', '17:30 - 19:00'];
    $table = '<div class="table-responsive"><table class="table table-bordered table-sm align-middle"><thead><tr><th>Professor(a)</th><th>Segunda</th><th>Terca</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Local</th></tr></thead><tbody>';
    foreach ($docentes as $idx => $d) {
        $nome = htmlspecialchars((string)($d['name'] ?? 'Docente'), ENT_QUOTES, 'UTF-8');
        $sala = htmlspecialchars((string)($d['room'] ?? 'DECOM/ICEB'), ENT_QUOTES, 'UTF-8');
        $seg = ($idx % 2 === 0) ? $slots[$idx % count($slots)] : '';
        $ter = ($idx % 3 === 0) ? $slots[($idx + 1) % count($slots)] : '';
        $qua = ($idx % 2 !== 0) ? $slots[($idx + 2) % count($slots)] : '';
        $qui = ($idx % 4 === 0) ? $slots[($idx + 3) % count($slots)] : '';
        $sex = ($idx % 5 === 0) ? $slots[($idx + 4) % count($slots)] : '';
        $table .= '<tr>'
            . '<td>' . $nome . '</td>'
            . '<td>' . htmlspecialchars($seg, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars($ter, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars($qua, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars($qui, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . htmlspecialchars($sex, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td>' . $sala . '</td>'
            . '</tr>';
    }
    $table .= '</tbody></table></div>';
    return $table;
}
function atendimento_docentes_default_data(): array {
    return [
        'title' => 'Horarios de Atendimento dos Docentes',
        'summary' => 'Tabela semanal de atendimento aos alunos por professor do DECOM.',
        'intro_html' => '<p>Consulte os horarios de atendimento docente abaixo. Esta pagina e atualizada pela secretaria via painel admin.</p>',
        'table_html' => atendimento_docentes_generate_table_html(),
        'notes_html' => '<p><small>Referencia institucional: planos de trabalho e atendimento publicados pelo DECOM/UFOP.</small></p>',
        'source_url' => 'https://www3.decom.ufop.br/decom/pessoal/planos_trabalho_publico/',
        'last_sync' => '',
    ];
}
function atendimento_docentes_get(): array {
    $d = atendimento_docentes_default_data();
    return [
        'title' => trim(site_setting_get('atendimento_docentes_title', $d['title'])),
        'summary' => trim(site_setting_get('atendimento_docentes_summary', $d['summary'])),
        'intro_html' => site_setting_get('atendimento_docentes_intro_html', $d['intro_html']),
        'table_html' => site_setting_get('atendimento_docentes_table_html', $d['table_html']),
        'notes_html' => site_setting_get('atendimento_docentes_notes_html', $d['notes_html']),
        'source_url' => trim(site_setting_get('atendimento_docentes_source_url', $d['source_url'])),
        'last_sync' => trim(site_setting_get('atendimento_docentes_last_sync', $d['last_sync'])),
    ];
}
function atendimento_docentes_save(array $data): void {
    $d = atendimento_docentes_default_data();
    $title = trim((string)($data['title'] ?? $d['title']));
    $summary = trim((string)($data['summary'] ?? $d['summary']));
    $intro = sanitize_rich_text((string)($data['intro_html'] ?? $d['intro_html']));
    $table = sanitize_rich_text((string)($data['table_html'] ?? $d['table_html']));
    $notes = sanitize_rich_text((string)($data['notes_html'] ?? $d['notes_html']));
    $source = trim((string)($data['source_url'] ?? $d['source_url']));
    site_setting_set('atendimento_docentes_title', $title !== '' ? $title : $d['title']);
    site_setting_set('atendimento_docentes_summary', $summary !== '' ? $summary : $d['summary']);
    site_setting_set('atendimento_docentes_intro_html', $intro);
    site_setting_set('atendimento_docentes_table_html', $table);
    site_setting_set('atendimento_docentes_notes_html', $notes);
    site_setting_set('atendimento_docentes_source_url', $source !== '' ? $source : $d['source_url']);
}
function atendimento_docentes_seed_from_people(): void {
    $current = atendimento_docentes_get();
    atendimento_docentes_save([
        'title' => $current['title'],
        'summary' => $current['summary'],
        'intro_html' => $current['intro_html'],
        'table_html' => atendimento_docentes_generate_table_html(),
        'notes_html' => $current['notes_html'],
        'source_url' => $current['source_url'],
    ]);
    site_setting_set('atendimento_docentes_last_sync', date('Y-m-d H:i:s'));
}
function normalize_menu_url(string $url, string $fallback): string {
    $url = trim($url);
    if ($url === '') {
        return $fallback;
    }
    if (preg_match('~^https?://~i', $url) === 1 || str_starts_with($url, '/')) {
        return $url;
    }
    return '/' . ltrim($url, '/');
}
function primary_menu_item(string $slot): array {
    $defaults = [
        'graduacao' => ['label' => 'Graduacao', 'url' => '/ensino/ciencia-computacao.php'],
        'pos_graduacao' => ['label' => 'Pós graduação', 'url' => '/ensino/pos-graduacao.php'],
    ];
    $default = $defaults[$slot] ?? ['label' => 'Menu', 'url' => '/'];
    $label = trim(site_setting_get('menu_' . $slot . '_label', $default['label']));
    $url = normalize_menu_url(site_setting_get('menu_' . $slot . '_url', $default['url']), $default['url']);
    return [
        'label' => $label !== '' ? $label : $default['label'],
        'url' => $url,
    ];
}
function ensure_ppgcc_tables(): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS ppgcc_page_content (
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
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        db()->exec(
            "CREATE TABLE IF NOT EXISTS ppgcc_graduates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                graduate_year INT NOT NULL,
                student_name VARCHAR(220) NOT NULL,
                source_url VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_ppgcc_graduate (graduate_year, student_name)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        db()->exec(
            "CREATE TABLE IF NOT EXISTS ppgcc_notices (
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
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        db()->exec(
            "CREATE TABLE IF NOT EXISTS ppgcc_selection_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_title VARCHAR(255) NOT NULL,
                item_title VARCHAR(255) NOT NULL,
                item_url VARCHAR(600) NOT NULL,
                item_hash CHAR(64) NOT NULL UNIQUE,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        db()->exec(
            "CREATE TABLE IF NOT EXISTS ppgcc_pages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(160) NOT NULL UNIQUE,
                title VARCHAR(220) NOT NULL,
                summary TEXT NOT NULL,
                content_html MEDIUMTEXT NOT NULL,
                source_url VARCHAR(600) DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('Failed ensuring PPGCC tables: ' . $e->getMessage());
    }
}
function simple_slugify(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    if ($text === '') {
        $text = 'item-' . bin2hex(random_bytes(4));
    }
    return substr($text, 0, 150);
}
function ppgcc_notice_unique_slug(string $base, ?int $ignoreId = null): string {
    ensure_ppgcc_tables();
    $slug = simple_slugify($base);
    $i = 1;
    while (true) {
        $sql = 'SELECT id FROM ppgcc_notices WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $ignoreId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $i++;
        $slug = substr(simple_slugify($base), 0, 145) . '-' . $i;
    }
}
function ppgcc_default_content(): array {
    return [
        'title' => 'Pos-graduacao em Computacao',
        'intro_html' => '<p>O PPGCC/UFOP oferece Mestrado e Doutorado em Ciencia da Computacao, com foco em pesquisa, inovacao tecnologica e formacao docente.</p>',
        'ingresso_html' => '<p>O ingresso ocorre por edital de processo seletivo. Os criterios incluem analise documental, etapas definidas em edital e requisitos academicos para cada nivel.</p>',
        'editais_html' => '<p>Editais recentes incluem selecao para ingresso (mestrado e doutorado), bolsa de doutorado e oportunidades de doutorado sanduiche (PDSE).</p>',
        'grade_html' => '<p>A grade curricular inclui disciplinas basicas e eletivas. Carga minima de creditos: mestrado (24) e doutorado (36), conforme normas do programa.</p>',
        'docencia_html' => '<p>O estagio em docencia e regulado por normas institucionais e do programa, podendo contabilizar creditos conforme regras vigentes.</p>',
        'bolsas_html' => '<p>O programa publica chamadas e criterios de bolsas (CAPES/CNPq/FAPEMIG e PROAP), sujeitos a disponibilidade e regras internas.</p>',
        'graduacao_html' => '<p>Alunos da graduacao podem cursar disciplinas isoladas da pos, conforme calendario e exigencias documentais divulgadas em cada periodo.</p>',
    ];
}
function ppgcc_content_get(): array {
    ensure_ppgcc_tables();
    try {
        $stmt = db()->query('SELECT * FROM ppgcc_page_content WHERE id = 1');
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        $default = ppgcc_default_content();
        $insert = db()->prepare(
            'INSERT INTO ppgcc_page_content
             (id, title, intro_html, ingresso_html, editais_html, grade_html, docencia_html, bolsas_html, graduacao_html)
             VALUES (1, :title, :intro_html, :ingresso_html, :editais_html, :grade_html, :docencia_html, :bolsas_html, :graduacao_html)'
        );
        $insert->execute([
            ':title' => $default['title'],
            ':intro_html' => $default['intro_html'],
            ':ingresso_html' => $default['ingresso_html'],
            ':editais_html' => $default['editais_html'],
            ':grade_html' => $default['grade_html'],
            ':docencia_html' => $default['docencia_html'],
            ':bolsas_html' => $default['bolsas_html'],
            ':graduacao_html' => $default['graduacao_html'],
        ]);
        return array_merge(['id' => 1], $default);
    } catch (Throwable $e) {
        error_log('Failed loading ppgcc content: ' . $e->getMessage());
        return array_merge(['id' => 1], ppgcc_default_content());
    }
}
function ppgcc_content_save(array $data): void {
    ensure_ppgcc_tables();
    $stmt = db()->prepare(
        'UPDATE ppgcc_page_content
         SET title = :title,
             intro_html = :intro_html,
             ingresso_html = :ingresso_html,
             editais_html = :editais_html,
             grade_html = :grade_html,
             docencia_html = :docencia_html,
             bolsas_html = :bolsas_html,
             graduacao_html = :graduacao_html
         WHERE id = 1'
    );
    $stmt->execute([
        ':title' => trim((string)($data['title'] ?? 'Pos-graduacao em Computacao')),
        ':intro_html' => sanitize_rich_text((string)($data['intro_html'] ?? '')),
        ':ingresso_html' => sanitize_rich_text((string)($data['ingresso_html'] ?? '')),
        ':editais_html' => sanitize_rich_text((string)($data['editais_html'] ?? '')),
        ':grade_html' => sanitize_rich_text((string)($data['grade_html'] ?? '')),
        ':docencia_html' => sanitize_rich_text((string)($data['docencia_html'] ?? '')),
        ':bolsas_html' => sanitize_rich_text((string)($data['bolsas_html'] ?? '')),
        ':graduacao_html' => sanitize_rich_text((string)($data['graduacao_html'] ?? '')),
    ]);
}
function ppgcc_graduate_years(): array {
    ensure_ppgcc_tables();
    try {
        $rows = db()->query('SELECT graduate_year, COUNT(*) AS total FROM ppgcc_graduates GROUP BY graduate_year ORDER BY graduate_year DESC')->fetchAll();
        return $rows ?: [];
    } catch (Throwable $e) {
        error_log('Failed loading graduate years: ' . $e->getMessage());
        return [];
    }
}
function ppgcc_graduates_by_year(int $year): array {
    ensure_ppgcc_tables();
    try {
        $stmt = db()->prepare('SELECT id, graduate_year, student_name, source_url FROM ppgcc_graduates WHERE graduate_year = :y ORDER BY student_name ASC');
        $stmt->execute([':y' => $year]);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Failed loading graduates by year: ' . $e->getMessage());
        return [];
    }
}
function ppgcc_notices(int $limit = 8, bool $onlyActive = true): array {
    ensure_ppgcc_tables();
    try {
        $limit = max(1, min($limit, 50));
        $sql = 'SELECT id, slug, title, summary, notice_type, notice_url, is_active, published_at
                FROM ppgcc_notices';
        if ($onlyActive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY published_at DESC, id DESC LIMIT ' . $limit;
        return db()->query($sql)->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Failed loading ppgcc notices: ' . $e->getMessage());
        return [];
    }
}
function ppgcc_notices_by_type(string $type, int $limit = 20, bool $onlyActive = true, int $offset = 0): array {
    ensure_ppgcc_tables();
    if (!in_array($type, ['edital', 'informacao'], true)) {
        return [];
    }
    try {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        $sql = 'SELECT id, slug, title, summary, notice_type, notice_url, is_active, published_at
                FROM ppgcc_notices
                WHERE notice_type = :type';
        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY published_at DESC, id DESC LIMIT :limit OFFSET :offset';
        $stmt = db()->prepare($sql);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Failed loading ppgcc notices by type: ' . $e->getMessage());
        return [];
    }
}
function ppgcc_notices_count_by_type(string $type, bool $onlyActive = true): int {
    ensure_ppgcc_tables();
    if (!in_array($type, ['edital', 'informacao'], true)) {
        return 0;
    }
    try {
        $sql = 'SELECT COUNT(*) FROM ppgcc_notices WHERE notice_type = :type';
        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }
        $stmt = db()->prepare($sql);
        $stmt->execute([':type' => $type]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('Failed counting ppgcc notices by type: ' . $e->getMessage());
        return 0;
    }
}
function ppgcc_notice_find(int $id): ?array {
    ensure_ppgcc_tables();
    $stmt = db()->prepare('SELECT * FROM ppgcc_notices WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
function ppgcc_selection_items_grouped(): array {
    ensure_ppgcc_tables();
    try {
        $rows = db()->query(
            'SELECT id, group_title, item_title, item_url, sort_order
             FROM ppgcc_selection_items
             ORDER BY sort_order ASC, id ASC'
        )->fetchAll();
        $grouped = [];
        foreach ($rows as $r) {
            $g = (string)$r['group_title'];
            if (!isset($grouped[$g])) {
                $grouped[$g] = [];
            }
            $grouped[$g][] = $r;
        }
        return $grouped;
    } catch (Throwable $e) {
        error_log('Failed loading selection items: ' . $e->getMessage());
        return [];
    }
}
function ppgcc_pages_list(bool $onlyActive = true): array {
    ensure_ppgcc_tables();
    try {
        $sql = 'SELECT id, slug, title, summary, content_html, source_url, sort_order, is_active
                FROM ppgcc_pages';
        if ($onlyActive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, title ASC, id ASC';
        return db()->query($sql)->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Failed loading ppgcc pages: ' . $e->getMessage());
        return [];
    }
}
function ppgcc_page_by_slug(string $slug): ?array {
    ensure_ppgcc_tables();
    try {
        $stmt = db()->prepare('SELECT * FROM ppgcc_pages WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('Failed loading ppgcc page by slug: ' . $e->getMessage());
        return null;
    }
}
function ppgcc_page_save(array $data, ?int $id = null): void {
    ensure_ppgcc_tables();
    $slug = simple_slugify((string)($data['slug'] ?? $data['title'] ?? 'pagina-pos'));
    $title = trim((string)($data['title'] ?? 'Pagina da Pos'));
    $summary = trim((string)($data['summary'] ?? ''));
    $contentHtml = sanitize_rich_text((string)($data['content_html'] ?? ''));
    $source = trim((string)($data['source_url'] ?? ''));
    $sortOrder = (int)($data['sort_order'] ?? 0);
    $isActive = (int)($data['is_active'] ?? 1) === 1 ? 1 : 0;

    if ($id !== null && $id > 0) {
        $stmt = db()->prepare(
            'UPDATE ppgcc_pages
             SET slug = :slug, title = :title, summary = :summary, content_html = :content_html,
                 source_url = :source_url, sort_order = :sort_order, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            ':slug' => $slug,
            ':title' => $title,
            ':summary' => $summary,
            ':content_html' => $contentHtml,
            ':source_url' => $source !== '' ? $source : null,
            ':sort_order' => $sortOrder,
            ':is_active' => $isActive,
            ':id' => $id,
        ]);
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO ppgcc_pages (slug, title, summary, content_html, source_url, sort_order, is_active)
         VALUES (:slug, :title, :summary, :content_html, :source_url, :sort_order, :is_active)
         ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            summary = VALUES(summary),
            content_html = VALUES(content_html),
            source_url = VALUES(source_url),
            sort_order = VALUES(sort_order),
            is_active = VALUES(is_active)'
    );
    $stmt->execute([
        ':slug' => $slug,
        ':title' => $title,
        ':summary' => $summary,
        ':content_html' => $contentHtml,
        ':source_url' => $source !== '' ? $source : null,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive,
    ]);
}
function ppgcc_import_subsite_pages(): array {
    ensure_ppgcc_tables();
    $startUrl = 'https://www3.decom.ufop.br/pos/inicio/';
    $ctx = stream_context_create([
        'http' => ['timeout' => 45, 'header' => "User-Agent: decom-ppgcc-subsite-import/1.0\r\n"],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $indexHtml = @file_get_contents($startUrl, false, $ctx);
    if ($indexHtml === false) {
        return ['ok' => false, 'imported' => 0, 'message' => 'Falha ao acessar a pagina inicial da pos antiga.'];
    }
    $normalize = static function (string $text): string {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    };
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($indexHtml, 'HTML-ENTITIES', 'UTF-8,ISO-8859-1,Windows-1252'));
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    $urls = ['https://www3.decom.ufop.br/pos/inicio/' => true];
    foreach ($xp->query('//a[@href]') as $a) {
        $href = trim((string)$a->getAttribute('href'));
        if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, '#')) {
            continue;
        }
        if (str_starts_with($href, '/')) {
            $href = 'https://www3.decom.ufop.br' . $href;
        } elseif (!preg_match('~^https?://~i', $href)) {
            $href = 'https://www3.decom.ufop.br/pos/' . ltrim($href, './');
        }
        $u = strtolower($href);
        if (!str_contains($u, 'www3.decom.ufop.br/pos/')) {
            continue;
        }
        if (str_contains($u, '/login') || str_contains($u, '/mail') || preg_match('/\.(pdf|doc|docx|xls|xlsx)$/i', $u) === 1) {
            continue;
        }
        $urls[$href] = true;
    }

    $order = 1;
    $imported = 0;
    foreach (array_keys($urls) as $url) {
        $html = @file_get_contents($url, false, $ctx);
        if ($html === false) {
            continue;
        }
        $d = new DOMDocument();
        libxml_use_internal_errors(true);
        $d->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8,ISO-8859-1,Windows-1252'));
        libxml_clear_errors();
        $x = new DOMXPath($d);

        $title = '';
        foreach ($x->query('//h1') as $h1) {
            $t = $normalize((string)$h1->textContent);
            if ($t !== '' && mb_strtolower($t, 'UTF-8') !== 'ppgcc' && mb_strtolower($t, 'UTF-8') !== 'menu') {
                $title = $t;
                break;
            }
        }
        if ($title === '') {
            $title = $normalize((string)($x->query('//title')->item(0)?->textContent ?? 'Pagina PPGCC'));
        }

        $blocks = [];
        foreach ($x->query('//main//*[self::h2 or self::h3 or self::p or self::li] | //article//*[self::h2 or self::h3 or self::p or self::li]') as $node) {
            $tag = strtolower((string)$node->nodeName);
            $txt = $normalize((string)$node->textContent);
            if ($txt === '' || mb_strlen($txt, 'UTF-8') < 3) {
                continue;
            }
            if (str_contains(mb_strtolower($txt, 'UTF-8'), 'departamento de comput') || str_contains(mb_strtolower($txt, 'UTF-8'), 'universidade federal de ouro preto campus')) {
                continue;
            }
            if ($tag === 'li') {
                $blocks[] = '<li>' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</li>';
            } elseif ($tag === 'h2' || $tag === 'h3') {
                $blocks[] = '<h3>' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</h3>';
            } else {
                $blocks[] = '<p>' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</p>';
            }
            if (count($blocks) >= 120) {
                break;
            }
        }
        if (empty($blocks)) {
            foreach ($x->query('//p') as $p) {
                $txt = $normalize((string)$p->textContent);
                if ($txt !== '' && mb_strlen($txt, 'UTF-8') > 10) {
                    $blocks[] = '<p>' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</p>';
                    if (count($blocks) >= 40) {
                        break;
                    }
                }
            }
        }
        if (empty($blocks)) {
            continue;
        }

        $summaryText = strip_tags($blocks[0]);
        $summary = mb_substr($summaryText, 0, 300, 'UTF-8');

        $path = parse_url($url, PHP_URL_PATH) ?: '/pos/pagina/';
        $slugRaw = trim(str_replace('/pos/', '', $path), '/');
        if ($slugRaw === '') {
            $slugRaw = 'inicio';
        }
        $slug = simple_slugify(str_replace('/', '-', $slugRaw));

        ppgcc_page_save([
            'slug' => $slug,
            'title' => $title,
            'summary' => $summary,
            'content_html' => implode("\n", $blocks),
            'source_url' => $url,
            'sort_order' => $order++,
            'is_active' => 1,
        ]);
        $imported++;
    }

    return ['ok' => true, 'imported' => $imported, 'message' => 'Importacao do subsite concluida.'];
}
function ppgcc_import_selection_page(): array {
    ensure_ppgcc_tables();
    $url = 'https://www3.decom.ufop.br/pos/processoseletivo/';
    $ctx = stream_context_create([
        'http' => ['timeout' => 45, 'header' => "User-Agent: decom-ppgcc-import/1.0\r\n"],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $html = @file_get_contents($url, false, $ctx);
    if ($html === false) {
        return ['ok' => false, 'inserted' => 0, 'message' => 'Falha ao acessar a fonte oficial.'];
    }

    $normalizeText = static function (string $text): string {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        return $text;
    };

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $normalizedHtml = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8,ISO-8859-1,Windows-1252');
    $dom->loadHTML($normalizedHtml);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    $nodes = $xp->query('//h2|//h3|//a[@href]');
    if (!$nodes) {
        return ['ok' => false, 'inserted' => 0, 'message' => 'Nao foi possivel interpretar o HTML da pagina oficial.'];
    }

    $relevantWords = [
        'edital', 'resultado', 'formulario', 'inscricao', 'comissao', 'barema',
        'pontuacao', 'planilha', 'candidato', 'lista', 'homologacao', 'curriculo', 'final',
    ];
    $groups = [];
    $currentGroup = '';
    foreach ($nodes as $node) {
        $name = strtolower((string)$node->nodeName);
        $text = $normalizeText((string)$node->textContent);
        if ($name === 'h2' || $name === 'h3') {
            if ($text !== '' && preg_match('/(edital|processos seletivos|comissao)/iu', $text) === 1) {
                $currentGroup = $text;
            }
            continue;
        }
        if ($name !== 'a' || $currentGroup === '' || $text === '') {
            continue;
        }
        $href = trim((string)$node->getAttribute('href'));
        if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, '#')) {
            continue;
        }
        if (str_starts_with($href, '/')) {
            $href = 'https://www3.decom.ufop.br' . $href;
        } elseif (!preg_match('~^https?://~i', $href)) {
            $href = 'https://www3.decom.ufop.br/pos/' . ltrim($href, './');
        }
        $urlLower = strtolower($href);
        $textLower = mb_strtolower($text, 'UTF-8');
        $isDocLink = str_contains($urlLower, 'drive.google.com') || str_contains($urlLower, 'docs.google.com') || str_contains($urlLower, 'forms.gle');
        $hasKeyword = false;
        foreach ($relevantWords as $w) {
            if (str_contains($textLower, $w)) {
                $hasKeyword = true;
                break;
            }
        }
        if (!$isDocLink && !$hasKeyword) {
            continue;
        }
        if (!isset($groups[$currentGroup])) {
            $groups[$currentGroup] = [];
        }
        $groups[$currentGroup][] = ['title' => $text, 'url' => $href];
    }

    try {
        db()->exec('DELETE FROM ppgcc_selection_items');
        $stmt = db()->prepare(
            'INSERT INTO ppgcc_selection_items (group_title, item_title, item_url, item_hash, sort_order)
             VALUES (:g, :t, :u, :h, :o)'
        );
        $order = 1;
        $inserted = 0;
        foreach ($groups as $groupTitle => $items) {
            $seen = [];
            foreach ($items as $it) {
                $hash = hash('sha256', $groupTitle . '|' . $it['title'] . '|' . $it['url']);
                if (isset($seen[$hash])) {
                    continue;
                }
                $seen[$hash] = true;
                $stmt->execute([
                    ':g' => $groupTitle,
                    ':t' => $it['title'],
                    ':u' => $it['url'],
                    ':h' => $hash,
                    ':o' => $order++,
                ]);
                $inserted++;
            }
        }
        return ['ok' => true, 'inserted' => $inserted, 'message' => 'Importacao concluida com sucesso.'];
    } catch (Throwable $e) {
        error_log('Failed importing selection page: ' . $e->getMessage());
        return ['ok' => false, 'inserted' => 0, 'message' => 'Falha ao salvar dados importados.'];
    }
}
function admin_email_config(): string {
    return trim((string)(getenv('ADMIN_EMAIL') ?: ''));
}
function admin_password_hash_config(): string {
    return trim((string)(getenv('ADMIN_PASSWORD_HASH') ?: ''));
}
function admin_roles(): array {
    return ['superadmin', 'editor', 'secretaria'];
}
function admin_normalize_role(string $role): string {
    $role = trim(mb_strtolower($role, 'UTF-8'));
    return in_array($role, admin_roles(), true) ? $role : 'editor';
}
function admin_role_permissions_map(): array {
    return [
        'superadmin' => [
            'view_dashboard',
            'manage_content',
            'manage_people',
            'manage_atendimento',
            'manage_menu',
            'manage_carousel',
            'manage_schedule',
            'manage_pos',
            'manage_users',
        ],
        'editor' => [
            'view_dashboard',
            'manage_content',
            'manage_carousel',
        ],
        'secretaria' => [
            'view_dashboard',
            'manage_content',
            'manage_people',
            'manage_atendimento',
            'manage_menu',
            'manage_carousel',
            'manage_schedule',
            'manage_pos',
        ],
    ];
}
function admin_permissions_for_role(string $role): array {
    $role = admin_normalize_role($role);
    $map = admin_role_permissions_map();
    return $map[$role] ?? [];
}
function ensure_admin_users_table(): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(30) NOT NULL DEFAULT 'editor',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_login_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('Failed ensuring admin_users table: ' . $e->getMessage());
    }
}
function ensure_default_admin_user(): void {
    ensure_admin_users_table();
    $email = admin_email_config();
    $hash = admin_password_hash_config();
    if ($email === '' || $hash === '') {
        return;
    }
    try {
        $stmt = db()->prepare('SELECT id FROM admin_users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return;
        }
        $insert = db()->prepare(
            'INSERT INTO admin_users (name, email, password_hash, role, is_active)
             VALUES (:name, :email, :password_hash, :role, 1)'
        );
        $insert->execute([
            ':name' => 'Administrador',
            ':email' => $email,
            ':password_hash' => $hash,
            ':role' => 'superadmin',
        ]);
    } catch (Throwable $e) {
        error_log('Failed ensuring default admin user: ' . $e->getMessage());
    }
}
function ensure_admin_auth_events_table(): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS admin_auth_events (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(190) NOT NULL,
                ip_address VARCHAR(64) NOT NULL,
                success TINYINT(1) NOT NULL DEFAULT 0,
                reason VARCHAR(120) NOT NULL DEFAULT '',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_auth_created (created_at),
                INDEX idx_auth_ip_email (ip_address, email, created_at)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('Failed ensuring admin_auth_events table: ' . $e->getMessage());
    }
}
function ensure_admin_audit_logs_table(): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                user_email VARCHAR(190) NOT NULL DEFAULT '',
                user_role VARCHAR(30) NOT NULL DEFAULT '',
                action VARCHAR(120) NOT NULL,
                target VARCHAR(190) NOT NULL DEFAULT '',
                details_json JSON NULL,
                ip_address VARCHAR(64) NOT NULL DEFAULT '',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_audit_created (created_at),
                INDEX idx_audit_user (user_id, created_at)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('Failed ensuring admin_audit_logs table: ' . $e->getMessage());
    }
}
function admin_client_ip(): string {
    $candidates = [
        (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
        (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
        (string)($_SERVER['REMOTE_ADDR'] ?? ''),
    ];
    foreach ($candidates as $ip) {
        if ($ip === '') {
            continue;
        }
        $parts = array_map('trim', explode(',', $ip));
        foreach ($parts as $p) {
            if (filter_var($p, FILTER_VALIDATE_IP)) {
                return $p;
            }
        }
    }
    return '0.0.0.0';
}
function admin_record_auth_event(string $email, bool $success, string $reason = ''): void {
    ensure_admin_auth_events_table();
    try {
        $stmt = db()->prepare(
            'INSERT INTO admin_auth_events (email, ip_address, success, reason)
             VALUES (:email, :ip_address, :success, :reason)'
        );
        $stmt->execute([
            ':email' => mb_strtolower(trim($email), 'UTF-8'),
            ':ip_address' => admin_client_ip(),
            ':success' => $success ? 1 : 0,
            ':reason' => substr($reason, 0, 120),
        ]);
    } catch (Throwable $e) {
        error_log('Failed recording admin auth event: ' . $e->getMessage());
    }
}
function admin_is_rate_limited(string $email): bool {
    ensure_admin_auth_events_table();
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) AS failures
             FROM admin_auth_events
             WHERE success = 0
               AND created_at >= (NOW() - INTERVAL 15 MINUTE)
               AND (email = :email OR ip_address = :ip_address)'
        );
        $stmt->execute([
            ':email' => mb_strtolower(trim($email), 'UTF-8'),
            ':ip_address' => admin_client_ip(),
        ]);
        $count = (int)$stmt->fetchColumn();
        return $count >= ADMIN_MAX_LOGIN_ATTEMPTS;
    } catch (Throwable $e) {
        error_log('Failed checking admin rate limit: ' . $e->getMessage());
        return false;
    }
}
function admin_audit_log(string $action, array $details = [], string $target = ''): void {
    ensure_admin_audit_logs_table();
    $user = admin_current_user();
    try {
        $stmt = db()->prepare(
            'INSERT INTO admin_audit_logs (user_id, user_email, user_role, action, target, details_json, ip_address)
             VALUES (:user_id, :user_email, :user_role, :action, :target, :details_json, :ip_address)'
        );
        $stmt->execute([
            ':user_id' => $user['id'] > 0 ? $user['id'] : null,
            ':user_email' => (string)$user['email'],
            ':user_role' => (string)$user['role'],
            ':action' => substr($action, 0, 120),
            ':target' => substr($target, 0, 190),
            ':details_json' => empty($details) ? null : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':ip_address' => admin_client_ip(),
        ]);
    } catch (Throwable $e) {
        error_log('Failed writing admin audit log: ' . $e->getMessage());
    }
}
function admin_validate_password_strength(string $password): ?string {
    if (strlen($password) < 10) {
        return 'A senha deve ter ao menos 10 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'A senha precisa conter ao menos uma letra maiuscula.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'A senha precisa conter ao menos uma letra minuscula.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'A senha precisa conter ao menos um numero.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'A senha precisa conter ao menos um caractere especial.';
    }
    return null;
}
function admin_current_user(): array {
    return [
        'id' => (int)($_SESSION['admin_user_id'] ?? 0),
        'name' => (string)($_SESSION['admin_user_name'] ?? 'Admin'),
        'email' => (string)($_SESSION['admin_user_email'] ?? ''),
        'role' => admin_normalize_role((string)($_SESSION['admin_role'] ?? 'superadmin')),
    ];
}
function admin_can(string $permission): bool {
    if (!is_admin_logged_in()) {
        return false;
    }
    $role = admin_current_user()['role'];
    if ($role === 'superadmin') {
        return true;
    }
    return in_array($permission, admin_permissions_for_role($role), true);
}
function require_admin_permission(string $permission): void {
    require_admin();
    if (admin_can($permission)) {
        return;
    }
    http_response_code(403);
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acesso negado</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"></head><body class="bg-light"><main class="container py-5"><div class="alert alert-danger"><h1 class="h4 mb-2">Acesso negado</h1><p class="mb-0">Sua conta nao possui permissao para acessar este modulo.</p></div><a class="btn btn-primary" href="/admin/dashboard.php">Voltar ao painel</a></main></body></html>';
    exit;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}
function is_valid_csrf_token(?string $token): bool {
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals((string)$_SESSION['csrf_token'], $token);
}
function admin_is_login_locked(): bool {
    $lockUntil = (int)($_SESSION['admin_lock_until'] ?? 0);
    return $lockUntil > time();
}
function admin_register_login_failure(string $email = '', string $reason = 'invalid_credentials'): void {
    $attempts = (int)($_SESSION['admin_login_attempts'] ?? 0) + 1;
    $_SESSION['admin_login_attempts'] = $attempts;
    if ($attempts >= ADMIN_MAX_LOGIN_ATTEMPTS) {
        $_SESSION['admin_lock_until'] = time() + ADMIN_LOCKOUT_SECONDS;
    }
    if ($email !== '') {
        admin_record_auth_event($email, false, $reason);
    }
}
function admin_clear_login_failures(): void {
    unset($_SESSION['admin_login_attempts'], $_SESSION['admin_lock_until']);
}
function admin_login(string $email, string $password): bool {
    ensure_default_admin_user();
    if (admin_is_login_locked()) {
        admin_record_auth_event($email, false, 'session_locked');
        return false;
    }
    try {
        $stmt = db()->prepare(
            'SELECT id, name, email, password_hash, role, is_active
             FROM admin_users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => trim($email)]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('Admin login query failed: ' . $e->getMessage());
        $user = false;
    }
    if (!$user || (int)($user['is_active'] ?? 0) !== 1) {
        admin_record_auth_event($email, false, 'unknown_or_inactive_user');
        return false;
    }
    if (!password_verify($password, (string)$user['password_hash'])) {
        admin_record_auth_event($email, false, 'invalid_password');
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['admin_ok'] = true;
    $_SESSION['admin_user_id'] = (int)$user['id'];
    $_SESSION['admin_user_name'] = (string)$user['name'];
    $_SESSION['admin_user_email'] = (string)$user['email'];
    $_SESSION['admin_role'] = admin_normalize_role((string)$user['role']);
    try {
        $upd = db()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id');
        $upd->execute([':id' => (int)$user['id']]);
    } catch (Throwable $e) {
        error_log('Failed updating admin last_login_at: ' . $e->getMessage());
    }
    admin_record_auth_event((string)$user['email'], true, 'login_success');
    admin_audit_log('admin_login_success', ['email' => (string)$user['email']], 'admin_login');
    admin_clear_login_failures();
    return true;
}
function admin_logout(): void {
    if (is_admin_logged_in()) {
        admin_audit_log('admin_logout', [], 'admin_logout');
    }
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool)($params['secure'] ?? false),
        'httponly' => (bool)($params['httponly'] ?? true),
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
    $_SESSION = [];
    session_destroy();
}
function admin_enable_remember_me(): void {
    $days = 30;
    $ttl = 60 * 60 * 24 * $days;
    $params = session_get_cookie_params();
    setcookie(session_name(), session_id(), [
        'expires' => time() + $ttl,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool)($params['secure'] ?? false),
        'httponly' => (bool)($params['httponly'] ?? true),
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
    $_SESSION['admin_remember'] = true;
}

function fetch_content_items(string $table): array {
    if (!in_array($table, ['news_items', 'edital_items', 'defesa_items', 'job_items'], true)) {
        return [];
    }
    $sql = "SELECT slug, title, summary, category, content, image FROM {$table} ORDER BY published_at DESC, id DESC";
    return db()->query($sql)->fetchAll();
}

function demo_news(): array {
    try {
        $items = fetch_content_items('news_items');
        if (!empty($items)) {
            return $items;
        }
    } catch (Throwable $e) {
        error_log('Failed loading news_items: ' . $e->getMessage());
    }
    return [];
}
function demo_editais(): array {
    try {
        $items = fetch_content_items('edital_items');
        if (!empty($items)) {
            return $items;
        }
    } catch (Throwable $e) {
        error_log('Failed loading edital_items: ' . $e->getMessage());
    }
    return [];
}
function demo_defesas(): array {
    try {
        $items = fetch_content_items('defesa_items');
        if (!empty($items)) {
            return $items;
        }
    } catch (Throwable $e) {
        error_log('Failed loading defesa_items: ' . $e->getMessage());
    }
    return [
      ['slug'=>'defesas-monografia-2026-1','title'=>'Defesas de monografia 2026/1','summary'=>'Agenda de bancas de monografia do semestre.','category'=>'Defesas','content'=>'Conteúdo demonstrativo para defesas.','image'=>'/assets/cards/noticia-pesquisa.svg']
    ];
}
function demo_jobs(): array {
    try {
        $items = fetch_content_items('job_items');
        if (!empty($items)) {
            return $items;
        }
    } catch (Throwable $e) {
        error_log('Failed loading job_items: ' . $e->getMessage());
    }
    return [
      ['slug'=>'vaga-estagio-web','title'=>'Vaga de estágio em desenvolvimento web','summary'=>'Empresa parceira busca estudante com noções de PHP e banco de dados.','category'=>'Carreiras','content'=>'Conteúdo demonstrativo para estágios e empregos.','image'=>'/assets/cards/noticia-portal.svg']
    ];
}
function find_demo_item(string $slug): ?array {
    foreach (array_merge(demo_news(), demo_editais(), demo_defesas(), demo_jobs()) as $item) {
        if ($item['slug'] === $slug) return $item;
    }
    return null;
}
function card_image_for_slug(string $slug): string {
    $map = [
        'portal-em-teste' => '/assets/cards/noticia-portal.svg',
        'horarios-de-aula-disponiveis' => '/assets/cards/noticia-horarios.svg',
        'grupo-de-pesquisa-abre-chamada' => '/assets/cards/noticia-pesquisa.svg',
        'edital-monitoria-2026-1' => '/assets/cards/edital-monitoria.svg',
        'edital-bolsas-extensao' => '/assets/cards/edital-extensao.svg',
        'qualificacao-mestrado-eduardo-henke-2026-03-26' => '/assets/cards/noticia-pesquisa.svg',
        'horario-aulas-decom-2026-1' => '/assets/cards/noticia-horarios.svg',
        'defesa-doutorado-guilherme-augusto-2026-03-20' => '/assets/cards/noticia-portal.svg',
        'inicio-matriculas-isoladas-ppgcc-2026-1' => '/assets/cards/edital-extensao.svg',
        'grade-disciplinas-matricula-2026-1' => '/assets/cards/edital-monitoria.svg',
        'horarios-monitorias-decom' => '/assets/cards/edital-monitoria.svg',
        'defesas-monografia-2026-1' => '/assets/cards/noticia-pesquisa.svg',
        'vaga-estagio-web' => '/assets/cards/noticia-portal.svg',
    ];
    return $map[$slug] ?? '/assets/cards/noticia-default.svg';
}
function content_image(array $item): string {
    $image = trim((string)($item['image'] ?? ''));
    if ($image !== '') {
        return $image;
    }
    return card_image_for_slug((string)($item['slug'] ?? ''));
}
function fetch_people_items(string $type): array {
    if (!in_array($type, ['docente', 'funcionario'], true)) {
        return [];
    }
    $sql = "SELECT slug, name, role_type, position, degree, website_url, lattes_url, email, phone, room, interests, bio, photo_url
            FROM people_items
            WHERE role_type = :role_type
            ORDER BY sort_order ASC, name ASC, id ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute([':role_type' => $type]);
    return $stmt->fetchAll();
}
function person_initials(string $name): string {
    $name = trim($name);
    if ($name === '') {
        return 'DE';
    }
    $parts = preg_split('/\s+/', $name) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1, 'UTF-8');
    $last = mb_substr($parts[count($parts) - 1] ?? '', 0, 1, 'UTF-8');
    $initials = mb_strtoupper($first . $last, 'UTF-8');
    return $initials !== '' ? $initials : 'DE';
}
function person_photo_placeholder(string $name): string {
    $palette = [
        ['#0f4c81', '#0f8ccf'],
        ['#1f6f5f', '#2bb673'],
        ['#6c3a9c', '#8b5cf6'],
        ['#7a2e2e', '#ef4444'],
        ['#3f3f46', '#71717a'],
    ];
    $index = abs(crc32($name)) % count($palette);
    [$from, $to] = $palette[$index];
    $initials = person_initials($name);
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="320" viewBox="0 0 320 320">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="' . $from . '"/><stop offset="100%" stop-color="' . $to . '"/>'
        . '</linearGradient></defs>'
        . '<rect width="320" height="320" fill="url(#g)"/>'
        . '<text x="50%" y="53%" dominant-baseline="middle" text-anchor="middle"'
        . ' fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="108" font-weight="700">'
        . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8')
        . '</text></svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}
function person_photo_url(array $item): string {
    $photo = trim((string)($item['photo_url'] ?? ''));
    if ($photo !== '') {
        return $photo;
    }
    return person_photo_placeholder((string)($item['name'] ?? 'DECOM'));
}
function docentes(): array {
    try {
        $items = fetch_people_items('docente');
        if (!empty($items)) {
            return $items;
        }
    } catch (Throwable $e) {
        error_log('Failed loading docente profiles: ' . $e->getMessage());
    }
    return [
        ['name'=>'Ana Paula Ribeiro','position'=>'Professora Adjunta','degree'=>'Doutora em Ciência da Computação','website_url'=>'','lattes_url'=>'','email'=>'ana.ribeiro@ufop.edu.br','phone'=>'(31) 3559-1601','room'=>'Instituto de Ciências Exatas e Biológicas','interests'=>'Engenharia de software e sistemas distribuídos.','bio'=>'Atua em engenharia de software e sistemas distribuídos.','photo_url'=>''],
        ['name'=>'Bruno Carvalho Mendes','position'=>'Professor Associado','degree'=>'Doutor em Ciência da Computação','website_url'=>'','lattes_url'=>'','email'=>'bruno.mendes@ufop.edu.br','phone'=>'(31) 3559-1602','room'=>'Instituto de Ciências Exatas e Biológicas','interests'=>'Inteligência artificial e mineração de dados.','bio'=>'Atua em inteligência artificial e mineração de dados.','photo_url'=>''],
        ['name'=>'Camila Freitas Lopes','position'=>'Professora Adjunta','degree'=>'Doutora em Computação','website_url'=>'','lattes_url'=>'','email'=>'camila.lopes@ufop.edu.br','phone'=>'(31) 3559-1603','room'=>'Instituto de Ciências Exatas e Biológicas','interests'=>'Computação gráfica e IHC.','bio'=>'Atua em computação gráfica e interação humano-computador.','photo_url'=>''],
    ];
}
function funcionarios(): array {
    try {
        $items = fetch_people_items('funcionario');
        if (!empty($items)) {
            return $items;
        }
    } catch (Throwable $e) {
        error_log('Failed loading funcionario profiles: ' . $e->getMessage());
    }
    return [
        ['name'=>'Mariana Souza Almeida','position'=>'Secretária Administrativa','degree'=>'','website_url'=>'','lattes_url'=>'','email'=>'mariana.almeida@ufop.edu.br','phone'=>'(31) 3559-1692','room'=>'Instituto de Ciências Exatas e Biológicas','interests'=>'Atendimento acadêmico e administrativo.','bio'=>'Atendimento acadêmico e administrativo do departamento.','photo_url'=>''],
        ['name'=>'Paulo Henrique Silva','position'=>'Técnico em TI','degree'=>'','website_url'=>'','lattes_url'=>'','email'=>'paulo.silva@ufop.edu.br','phone'=>'(31) 3559-1693','room'=>'Instituto de Ciências Exatas e Biológicas','interests'=>'Infraestrutura e suporte de laboratórios.','bio'=>'Suporte de laboratórios, sistemas e infraestrutura local.','photo_url'=>''],
    ];
}
function fetch_research_labs(): array {
    try {
        $sql = "SELECT slug, name, summary, site_url
                FROM research_labs
                WHERE is_active = 1
                ORDER BY sort_order ASC, name ASC, id ASC";
        return db()->query($sql)->fetchAll();
    } catch (Throwable $e) {
        error_log('Failed loading research_labs: ' . $e->getMessage());
        return [];
    }
}
function research_labs_data(): array {
    $items = fetch_research_labs();
    if (!empty($items)) {
        return $items;
    }
    return [
        ['slug' => 'csilab', 'name' => 'CSILab', 'summary' => 'Laboratorio de Computacao de Sistemas Inteligentes.', 'site_url' => 'https://csilab.ufop.br/'],
        ['slug' => 'gaid', 'name' => 'GAID', 'summary' => 'Laboratorio Tematico em Gerencia e Analise Inteligente de Dados.', 'site_url' => 'http://www.decom.ufop.br/gaid/'],
        ['slug' => 'goal', 'name' => 'GOAL', 'summary' => 'Laboratorio Tematico em Otimizacao e Algoritmos.', 'site_url' => 'http://www.goal.ufop.br'],
        ['slug' => 'imobilis', 'name' => 'iMobilis', 'summary' => 'Laboratorio Tematico em Computacao Movel.', 'site_url' => 'http://www2.decom.ufop.br/imobilis/'],
        ['slug' => 'kryptolab', 'name' => 'KryptoLab', 'summary' => 'Laboratorio de Criptografia e Seguranca de Redes.', 'site_url' => 'https://kryptolab.decom.ufop.br'],
        ['slug' => 'lcad', 'name' => 'LCAD', 'summary' => 'Laboratorio de Computacao Aplicada e Desenvolvimento.', 'site_url' => 'https://lcad.ufop.br/'],
        ['slug' => 'lapdi', 'name' => 'LaPDI', 'summary' => 'Laboratorio Tematico em Processamento de Imagens.', 'site_url' => 'http://www.decom.ufop.br/lapdi/'],
        ['slug' => 'terralab', 'name' => 'TerraLab', 'summary' => 'Laboratorio Tematico em Simulacao e Geoprocessamento.', 'site_url' => 'http://www.decom.ufop.br/terralab/'],
        ['slug' => 'xr4good', 'name' => 'XR4Good', 'summary' => 'Laboratorio Tematico de Realidade Estendida.', 'site_url' => 'http://xr4goodlab.decom.ufop.br/'],
    ];
}
function fetch_research_projects(): array {
    try {
        $sql = "SELECT slug, title, project_type, summary, site_url, coordinator
                FROM research_projects
                WHERE is_active = 1
                ORDER BY sort_order ASC, title ASC, id ASC";
        return db()->query($sql)->fetchAll();
    } catch (Throwable $e) {
        error_log('Failed loading research_projects: ' . $e->getMessage());
        return [];
    }
}
function research_projects_data(): array {
    $items = fetch_research_projects();
    if (!empty($items)) {
        return $items;
    }
    return [
        [
            'slug' => 'projeto-ia-educacao',
            'title' => 'IA aplicada ao apoio ao ensino',
            'project_type' => 'pesquisa',
            'summary' => 'Projeto focado em modelos de aprendizado de maquina para suporte a atividades educacionais.',
            'site_url' => '',
            'coordinator' => 'DECOM/UFOP',
        ],
        [
            'slug' => 'projeto-extensao-cultura-digital',
            'title' => 'Cultura digital e formacao em tecnologia',
            'project_type' => 'extensao',
            'summary' => 'Projeto de extensao com oficinas e atividades para aproximar comunidade e computacao.',
            'site_url' => '',
            'coordinator' => 'DECOM/UFOP',
        ],
    ];
}
function course_data(string $slug): array {
    $courses = [
        'ciencia-da-computacao' => ['name'=>'Bacharelado em Ciência da Computação','summary'=>'Curso voltado à formação sólida em fundamentos da computação, algoritmos, software e sistemas.','content'=>'A proposta curricular contempla programação, algoritmos, estruturas de dados, arquitetura de computadores, bancos de dados, engenharia de software, redes e teoria da computação.','modality'=>'Bacharelado','duration'=>'8 semestres','shift'=>'Integral'],
        'inteligencia-artificial' => ['name'=>'Bacharelado em Inteligência Artificial','summary'=>'Curso voltado à formação em aprendizado de máquina, ciência de dados e sistemas inteligentes.','content'=>'A matriz curricular inclui fundamentos matemáticos, programação, otimização, aprendizado de máquina, mineração de dados e visão computacional.','modality'=>'Bacharelado','duration'=>'8 semestres','shift'=>'Integral'],
    ];
    return $courses[$slug] ?? ['name'=>'Curso','summary'=>'','content'=>'','modality'=>'','duration'=>'','shift'=>''];
}
function page_data(string $slug): array {
    $pages = [
      'quem-somos'=>['title'=>'Quem somos','summary'=>'Apresentação institucional do departamento, sua trajetória e suas áreas de atuação.','content'=>'O Departamento de Computação atua em ensino, pesquisa e extensão, oferecendo cursos de graduação e desenvolvendo ações acadêmicas e tecnológicas.'],
      'comunicacao-logo'=>['title'=>'Comunicação e logo','summary'=>'Diretrizes para uso do nome, identidade visual e materiais institucionais.','content'=>'Esta página pode concentrar versões do logotipo e padrões de comunicação institucional do departamento.'],
      'localizacao'=>['title'=>'Localização','summary'=>'Informações de localização física, acesso e referência institucional.','content'=>'O departamento está localizado no campus universitário, com atendimento presencial em dias úteis.'],
      'mapa-campus'=>['title'=>'Mapa do campus','summary'=>'Mapa de acesso e referência espacial da unidade acadêmica.','content'=>'Página preparada para receber mapa interativo ou orientações de deslocamento.'],
      'horarios-de-aula'=>['title'=>'Horários de Aula','summary'=>'Consulta organizada dos horários de aula por curso, período ou turma.','content'=>'Esta página concentra quadros de horários dos alunos, horários por docente ou planilhas por semestre letivo.'],
      'informacoes-uteis'=>['title'=>'Informações Úteis','summary'=>'Orientações acadêmicas, formulários e instruções operacionais para estudantes.','content'=>'Inclua aqui calendários, orientações de matrícula, aproveitamento de estudos, equivalências e monitorias.'],
      'monografias'=>['title'=>'Monografias','summary'=>'Informações sobre disciplinas de monografia, banca, documentação e cronogramas.','content'=>'Esta página centraliza regulamentos, modelos de documentos, agendas de defesas e orientações para discentes e orientadores.'],
      'pesquisa'=>['title'=>'Pesquisa','summary'=>'Apresentação de linhas de pesquisa, grupos, projetos e produção científica.','content'=>'Esta seção organiza laboratórios, grupos de pesquisa, projetos financiados e oportunidades de iniciação científica.'],
      'extensao'=>['title'=>'Extensão','summary'=>'Catálogo de projetos e ações de extensão vinculados ao departamento.','content'=>'Esta seção apresenta programas, projetos, oficinas, cursos e ações extensionistas.'],
      'cocic'=>['title'=>'Graduacao','summary'=>'Pagina da graduacao com apresentacao do curso, estrutura academica e informacoes uteis para alunos.','content'=>'A graduacao pode publicar aqui informacoes sobre matriz curricular, orientacoes academicas, documentos, calendario e comunicados aos estudantes.'],
    ];
    return $pages[$slug] ?? ['title'=>'Página','summary'=>'','content'=>''];
}
