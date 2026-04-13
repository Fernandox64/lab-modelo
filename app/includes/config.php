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
if (PHP_SAPI !== 'cli' && !headers_sent() && ob_get_level() === 0) {
    ob_start(function (string $buffer): string {
        return fix_mojibake_ptbr($buffer);
    });
}

const SITE_NAME = 'Departamento Modelo';
const SITE_SIGLA = 'DEP';
const SITE_UNIVERSITY = 'Universidade Modelo';
const SITE_EMAIL = 'departamento@instituicao.br';
const SITE_PHONE = '+55 00 0000-0000';
const SITE_ADDRESS = 'Endereco institucional do departamento';

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
function e($v): string { return htmlspecialchars(fix_mojibake_ptbr((string)$v), ENT_QUOTES, 'UTF-8'); }
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
function site_color_palettes(): array {
    return [
        'ufop-oficial' => [
            'name' => 'UFOP Oficial',
            'description' => 'Esquema oficial solicitado com cinza de apoio e vermelho institucional.',
            'vars' => [
                'yellow' => '#FFFFFF',
                'cyan' => '#919191',
                'blue' => '#962038',
                'navy' => '#962038',
                'bg' => '#F5F5F5',
                'text' => '#2B2B2B',
                'menu_bg' => '#962038',
                'menu_text' => '#FFFFFF',
                'menu_hover_bg' => '#7D1A2E',
                'menu_hover_text' => '#FFFFFF',
                'topbar_bg' => '#404040',
                'topbar_bg_alt' => '#919191',
                'hero_bg' => '#919191',
                'hero_bg_alt' => '#919191',
            ],
        ],
        'ufop-institucional-classica' => [
            'name' => 'UFOP Institucional Classica',
            'description' => 'Paleta institucional equilibrada para comunicacao formal.',
            'vars' => [
                'yellow' => '#FFFFFF',
                'cyan' => '#6E6E6E',
                'blue' => '#B5121B',
                'navy' => '#8F0F16',
                'bg' => '#F5F5F5',
                'text' => '#2B2B2B',
                'menu_bg' => '#404040',
                'menu_text' => '#FFFFFF',
                'menu_hover_bg' => '#2F2F2F',
                'menu_hover_text' => '#FFFFFF',
            ],
        ],
        'azul-institucional' => [
            'name' => 'Azul Institucional',
            'description' => 'Paleta padrao com tons de azul e destaque amarelo.',
            'vars' => [
                'yellow' => '#FFDE42',
                'cyan' => '#53CBF3',
                'blue' => '#5478FF',
                'navy' => '#111FA2',
                'bg' => '#F8FBFF',
                'text' => '#0F1A5F',
                'menu_bg' => '#111FA2',
                'menu_text' => '#FFFFFF',
                'menu_hover_bg' => '#5478FF',
                'menu_hover_text' => '#FFFFFF',
            ],
        ],
        'ufop' => [
            'name' => 'UFOP Classica',
            'description' => 'Paleta vinho e cinza inspirada no portal e na logomarca institucional da UFOP.',
            'vars' => [
                'yellow' => '#B08D57',
                'cyan' => '#B3BAC3',
                'blue' => '#8C1D40',
                'navy' => '#5C132B',
                'bg' => '#F7F7F8',
                'text' => '#2B2F33',
                'menu_bg' => '#404040',
                'menu_text' => '#FFFFFF',
                'menu_hover_bg' => '#2F2F2F',
                'menu_hover_text' => '#FFFFFF',
            ],
        ],
        'verde-campus' => [
            'name' => 'Verde Campus',
            'description' => 'Tons de verde para visual natural e academico.',
            'vars' => ['yellow' => '#B7E07A', 'cyan' => '#63D5B3', 'blue' => '#2F9E44', 'navy' => '#1F6B2B', 'bg' => '#F4FBF6', 'text' => '#183A1D'],
        ],
        'oceano-profundo' => [
            'name' => 'Oceano Profundo',
            'description' => 'Azul petroleo com contraste moderno.',
            'vars' => ['yellow' => '#F6C453', 'cyan' => '#4FD1C5', 'blue' => '#0EA5E9', 'navy' => '#0B3C5D', 'bg' => '#F1F8FC', 'text' => '#0B2239'],
        ],
        'por-do-sol' => [
            'name' => 'Por do Sol',
            'description' => 'Mistura de laranja, coral e azul noturno.',
            'vars' => ['yellow' => '#FFC857', 'cyan' => '#FF9B85', 'blue' => '#E76F51', 'navy' => '#264653', 'bg' => '#FFF8F3', 'text' => '#3A2D2A'],
        ],
        'grafite' => [
            'name' => 'Grafite',
            'description' => 'Neutra e sofisticada, com destaque dourado.',
            'vars' => ['yellow' => '#D4AF37', 'cyan' => '#9CA3AF', 'blue' => '#4B5563', 'navy' => '#1F2937', 'bg' => '#F7F7F8', 'text' => '#111827'],
        ],
        'vinho-academico' => [
            'name' => 'Vinho Academico',
            'description' => 'Tons de vinho com apoio quente.',
            'vars' => ['yellow' => '#F3C677', 'cyan' => '#E7A4B3', 'blue' => '#9F2B68', 'navy' => '#5C1A3A', 'bg' => '#FFF7FA', 'text' => '#3F1328'],
        ],
        'lavanda-tech' => [
            'name' => 'Lavanda Tech',
            'description' => 'Visual leve com violeta e azul claro.',
            'vars' => ['yellow' => '#F7D154', 'cyan' => '#A5B4FC', 'blue' => '#7C3AED', 'navy' => '#312E81', 'bg' => '#F7F5FF', 'text' => '#221B4B'],
        ],
        'turquesa-energia' => [
            'name' => 'Turquesa Energia',
            'description' => 'Paleta vibrante para portais dinamicos.',
            'vars' => ['yellow' => '#FDE047', 'cyan' => '#2DD4BF', 'blue' => '#14B8A6', 'navy' => '#0F766E', 'bg' => '#F0FDFA', 'text' => '#134E4A'],
        ],
        'terra-cobre' => [
            'name' => 'Terra Cobre',
            'description' => 'Paleta terrosa com boa legibilidade.',
            'vars' => ['yellow' => '#E9C46A', 'cyan' => '#D9A066', 'blue' => '#B5651D', 'navy' => '#6D3B1F', 'bg' => '#FCF7F2', 'text' => '#3E2723'],
        ],
        'vermelho-grafite' => [
            'name' => 'Vermelho Grafite',
            'description' => 'Variacao em tons de vermelho, cinza e preto para visual forte e moderno.',
            'vars' => ['yellow' => '#C9A66B', 'cyan' => '#A9ADB3', 'blue' => '#B91C1C', 'navy' => '#111111', 'bg' => '#F2F3F5', 'text' => '#1A1A1A'],
        ],
        'petroleo-verde' => [
            'name' => 'Petroleo Verde',
            'description' => 'Inspirada na paleta azul e verde da referencia enviada.',
            'vars' => ['yellow' => '#93C95F', 'cyan' => '#199C9B', 'blue' => '#115A73', 'navy' => '#070048', 'bg' => '#F2F7F8', 'text' => '#0C2530'],
        ],
        'areia-terracota' => [
            'name' => 'Areia Terracota',
            'description' => 'Inspirada na paleta terrosa clara da referencia enviada.',
            'vars' => ['yellow' => '#E5D89B', 'cyan' => '#D4CB7F', 'blue' => '#D8AE62', 'navy' => '#745A28', 'bg' => '#FAF6E8', 'text' => '#3C2F1A'],
        ],
        'oliva-terra' => [
            'name' => 'Oliva Terra',
            'description' => 'Inspirada na paleta oliva, cobre e grafite da referencia enviada.',
            'vars' => ['yellow' => '#B89658', 'cyan' => '#6F724B', 'blue' => '#A6472D', 'navy' => '#3A4F3B', 'bg' => '#F4F2EC', 'text' => '#2A2924'],
        ],
        'vermelho-intensificado' => [
            'name' => 'Vermelho Intensificado',
            'description' => 'Variacao moderna com alto contraste para destaques visuais.',
            'vars' => ['yellow' => '#FFFFFF', 'cyan' => '#8A8A8A', 'blue' => '#D71920', 'navy' => '#A10E14', 'bg' => '#EFEFEF', 'text' => '#1A1A1A'],
        ],
        'vermelho-premium' => [
            'name' => 'Vermelho Premium',
            'description' => 'Visual sofisticado para departamento com pesquisa e tecnologia.',
            'vars' => ['yellow' => '#E8E8E8', 'cyan' => '#9E0B0F', 'blue' => '#C4171D', 'navy' => '#1E3A5F', 'bg' => '#FFFFFF', 'text' => '#333333'],
        ],
    ];
}
function hex_to_rgb_css(string $hex): string {
    $hex = trim($hex);
    if (!preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $hex)) {
        return 'rgb(0, 0, 0)';
    }
    $raw = substr($hex, 1);
    if (strlen($raw) === 3) {
        $raw = $raw[0] . $raw[0] . $raw[1] . $raw[1] . $raw[2] . $raw[2];
    }
    $r = hexdec(substr($raw, 0, 2));
    $g = hexdec(substr($raw, 2, 2));
    $b = hexdec(substr($raw, 4, 2));
    return "rgb({$r}, {$g}, {$b})";
}
function current_site_palette_key(): string {
    $palettes = site_color_palettes();
    $saved = trim(site_setting_get('site_palette', 'ufop-oficial'));
    return array_key_exists($saved, $palettes) ? $saved : 'ufop-oficial';
}
function current_site_palette(): array {
    $palettes = site_color_palettes();
    return $palettes[current_site_palette_key()];
}
function set_current_site_palette(string $key): bool {
    $palettes = site_color_palettes();
    if (!array_key_exists($key, $palettes)) {
        return false;
    }
    site_setting_set('site_palette', $key);
    return true;
}
function current_site_palette_inline_css(): string {
    $palette = current_site_palette();
    $vars = $palette['vars'] ?? [];
    $yellow = (string)($vars['yellow'] ?? '#FFDE42');
    $cyan = (string)($vars['cyan'] ?? '#53CBF3');
    $blue = (string)($vars['blue'] ?? '#5478FF');
    $navy = (string)($vars['navy'] ?? '#111FA2');
    $bg = (string)($vars['bg'] ?? '#F8FBFF');
    $text = (string)($vars['text'] ?? '#0F1A5F');
    $menuBg = (string)($vars['menu_bg'] ?? '#FFFFFF');
    $menuText = (string)($vars['menu_text'] ?? $navy);
    $menuHoverBg = (string)($vars['menu_hover_bg'] ?? 'rgba(83, 203, 243, 0.2)');
    $menuHoverText = (string)($vars['menu_hover_text'] ?? $blue);
    $topbarBg = (string)($vars['topbar_bg'] ?? $navy);
    $topbarBgAlt = (string)($vars['topbar_bg_alt'] ?? $blue);
    $heroBg = (string)($vars['hero_bg'] ?? $navy);
    $heroBgAlt = (string)($vars['hero_bg_alt'] ?? $blue);
    return ':root{'
        . '--decom-yellow:' . $yellow . ';'
        . '--decom-cyan:' . $cyan . ';'
        . '--decom-blue:' . $blue . ';'
        . '--decom-navy:' . $navy . ';'
        . '--decom-yellow-rgb:' . hex_to_rgb_css($yellow) . ';'
        . '--decom-cyan-rgb:' . hex_to_rgb_css($cyan) . ';'
        . '--decom-blue-rgb:' . hex_to_rgb_css($blue) . ';'
        . '--decom-navy-rgb:' . hex_to_rgb_css($navy) . ';'
        . '--decom-bg:' . $bg . ';'
        . '--decom-text:' . $text . ';'
        . '--decom-menu-bg:' . $menuBg . ';'
        . '--decom-menu-text:' . $menuText . ';'
        . '--decom-menu-hover-bg:' . $menuHoverBg . ';'
        . '--decom-menu-hover-text:' . $menuHoverText . ';'
        . '--decom-topbar-bg:' . $topbarBg . ';'
        . '--decom-topbar-bg-alt:' . $topbarBgAlt . ';'
        . '--decom-hero-bg:' . $heroBg . ';'
        . '--decom-hero-bg-alt:' . $heroBgAlt . ';'
        . '}';
}
function normalize_optional_logo_url(string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $url) === 1 || str_starts_with($url, '/')) {
        return $url;
    }
    return '/' . ltrim($url, '/');
}
function header_ufop_logo_url(): string {
    return '/assets/images/ufop_logo_2.png';
}
function header_department_logo(): array {
    $url = normalize_optional_logo_url(site_setting_get('header_department_logo_url', ''));
    $link = normalize_menu_url(site_setting_get('header_department_logo_link', '/'), '/');
    $alt = trim(site_setting_get('header_department_logo_alt', SITE_NAME . ' - logo'));
    if ($url === '') {
        $url = '/assets/images/lab-logo-placeholder.svg';
    }
    return [
        'url' => $url,
        'link' => $link,
        'alt' => $alt !== '' ? $alt : (SITE_NAME . ' - logo'),
    ];
}
function hero_carousel_defaults(): array {
    return [
        [
            'id' => 'default-1',
            'image' => '/assets/images/carousel/lab-1.png',
            'badge' => 'Portal Institucional',
            'title' => 'Bem-vindo ao portal do departamento',
            'text' => 'Noticias, editais, comunicados e servicos academicos em um unico lugar.',
        ],
    ];
}
function hero_slide_normalize(array $slide, string $fallbackId): array {
    $id = trim((string)($slide['id'] ?? ''));
    if ($id === '') {
        $id = $fallbackId;
    }
    $image = trim((string)($slide['image'] ?? ''));
    if ($image === '' || $image === '/assets/images/carousel/tech-circuit.jpg') {
        $image = '/assets/images/carousel/lab-1.png';
    }
    return [
        'id' => $id,
        'image' => $image,
        'badge' => trim((string)($slide['badge'] ?? '')),
        'title' => trim((string)($slide['title'] ?? '')),
        'text' => trim((string)($slide['text'] ?? '')),
    ];
}
function hero_carousel_get(): array {
    $json = trim(site_setting_get('hero_carousel_json', ''));
    if ($json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded) && !empty($decoded)) {
            $out = [];
            $idx = 1;
            foreach ($decoded as $slide) {
                if (!is_array($slide)) {
                    continue;
                }
                $out[] = hero_slide_normalize($slide, 'slide-' . $idx);
                $idx++;
            }
            if (!empty($out)) {
                return $out;
            }
        }
    }

    // Compatibilidade com estrutura antiga (3 slides em keys separadas).
    $legacy = [];
    for ($i = 1; $i <= 3; $i++) {
        $image = trim(site_setting_get("hero_slide_{$i}_image", ''));
        $badge = trim(site_setting_get("hero_slide_{$i}_badge", ''));
        $title = trim(site_setting_get("hero_slide_{$i}_title", ''));
        $text = trim(site_setting_get("hero_slide_{$i}_text", ''));
        if ($image === '' && $badge === '' && $title === '' && $text === '') {
            continue;
        }
        $legacy[] = [
            'id' => 'legacy-' . $i,
            'image' => $image,
            'badge' => $badge,
            'title' => $title,
            'text' => $text,
        ];
    }
    if (!empty($legacy)) {
        return $legacy;
    }

    return hero_carousel_defaults();
}
function hero_carousel_save(array $slides): void {
    $normalized = [];
    $idx = 1;
    foreach ($slides as $slide) {
        if (!is_array($slide)) {
            continue;
        }
        $n = hero_slide_normalize($slide, 'slide-' . $idx);
        if ($n['image'] === '' && $n['title'] === '' && $n['text'] === '' && $n['badge'] === '') {
            continue;
        }
        $normalized[] = $n;
        $idx++;
    }
    if (empty($normalized)) {
        $normalized = hero_carousel_defaults();
    }
    site_setting_set('hero_carousel_json', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
function horarios_cc_2026_template_html(): string {
    return <<<'HTML'
<h2>Horarios de Aula - Curso de Graduacao (2026-1)</h2>
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
<p><small>BCC109 - Eletronica para Computacao | BCC201 - Introducao a Programacao | BCC265 - Eletronica para Computacao | BCC501 - Introducao a Curso de Graduacao | MTM122 - Calculo Diferencial e Integral I | MTM131 - Geometria Analitica e Calculo Vetorial</small></p>

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
<p><small>BCC241 - Projeto e Analise de Algoritmos | BCC244 - Teoria da Computacao | BCC321 - Banco de Dados I | BCC323 - Engenharia de Software II | BCC362 - Sistemas Distribuidos | BCC502 - Metodologia Cientifica em Curso de Graduacao</small></p>

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
<p><small>BCC325 - Curso de Graduacao 2 | BCC326 - Processamento de Imagens | BCC327 - Computacao Grafica | BCC328 - Construcao de Compiladores I | BCC342 - Introducao a Otimizacao</small></p>

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
<li>BCC401 - Metodologia de Pesquisa em Curso de Graduacao</li>
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
<li>BCC904 - Topicos em Curso de Graduacao I</li>
<li>BCC905 - Topicos em Curso de Graduacao II</li>
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
        'links_html' => '<ul><li><a href="https://zeppelin10.ufop.br/HorarioAulas/index.xhtml" target="_blank" rel="noopener">Horario de Aulas institucional (oficial)</a></li></ul>',
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
function people_scope_normalize(string $scope): string {
    $scope = trim(mb_strtolower($scope, 'UTF-8'));
    return $scope === 'pos' ? 'pos' : 'principal';
}
function people_scope_label(string $scope): string {
    return people_scope_normalize($scope) === 'pos' ? 'Pos-graduacao' : 'Principal';
}
function people_scope_setting_prefix(string $scope): string {
    return people_scope_normalize($scope) === 'pos' ? 'pos_' : '';
}
function atendimento_docentes_generate_table_html(string $scope = 'principal'): string {
    $docentes = docentes($scope);
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
        $sala = htmlspecialchars((string)($d['room'] ?? 'Departamento/Unidade'), ENT_QUOTES, 'UTF-8');
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
function atendimento_docentes_default_data(string $scope = 'principal'): array {
    $scopeNorm = people_scope_normalize($scope);
    $scopeLabel = $scopeNorm === 'pos' ? 'da Pos-graduacao' : 'do Departamento';
    $sourceDefault = $scopeNorm === 'pos'
        ? '/pos/inicio.php'
        : 'https://www3.decom.ufop.br/decom/pessoal/planos_trabalho_publico/';
    return [
        'title' => 'Horarios de Atendimento dos Docentes ' . $scopeLabel,
        'summary' => 'Tabela semanal de atendimento aos alunos por professor ' . mb_strtolower($scopeLabel, 'UTF-8') . '.',
        'intro_html' => '<p>Consulte os horarios de atendimento docente abaixo. Esta pagina e atualizada pela secretaria via painel admin.</p>',
        'table_html' => atendimento_docentes_generate_table_html($scopeNorm),
        'notes_html' => '<p><small>Referencia institucional: planos de trabalho e atendimento publicados pelo departamento.</small></p>',
        'source_url' => $sourceDefault,
        'last_sync' => '',
    ];
}
function atendimento_docentes_get(string $scope = 'principal'): array {
    $d = atendimento_docentes_default_data($scope);
    $prefix = people_scope_setting_prefix($scope);
    return [
        'title' => trim(site_setting_get($prefix . 'atendimento_docentes_title', $d['title'])),
        'summary' => trim(site_setting_get($prefix . 'atendimento_docentes_summary', $d['summary'])),
        'intro_html' => site_setting_get($prefix . 'atendimento_docentes_intro_html', $d['intro_html']),
        'table_html' => site_setting_get($prefix . 'atendimento_docentes_table_html', $d['table_html']),
        'notes_html' => site_setting_get($prefix . 'atendimento_docentes_notes_html', $d['notes_html']),
        'source_url' => trim(site_setting_get($prefix . 'atendimento_docentes_source_url', $d['source_url'])),
        'last_sync' => trim(site_setting_get($prefix . 'atendimento_docentes_last_sync', $d['last_sync'])),
    ];
}
function atendimento_docentes_save(array $data, string $scope = 'principal'): void {
    $d = atendimento_docentes_default_data($scope);
    $prefix = people_scope_setting_prefix($scope);
    $title = trim((string)($data['title'] ?? $d['title']));
    $summary = trim((string)($data['summary'] ?? $d['summary']));
    $intro = sanitize_rich_text((string)($data['intro_html'] ?? $d['intro_html']));
    $table = sanitize_rich_text((string)($data['table_html'] ?? $d['table_html']));
    $notes = sanitize_rich_text((string)($data['notes_html'] ?? $d['notes_html']));
    $source = trim((string)($data['source_url'] ?? $d['source_url']));
    site_setting_set($prefix . 'atendimento_docentes_title', $title !== '' ? $title : $d['title']);
    site_setting_set($prefix . 'atendimento_docentes_summary', $summary !== '' ? $summary : $d['summary']);
    site_setting_set($prefix . 'atendimento_docentes_intro_html', $intro);
    site_setting_set($prefix . 'atendimento_docentes_table_html', $table);
    site_setting_set($prefix . 'atendimento_docentes_notes_html', $notes);
    site_setting_set($prefix . 'atendimento_docentes_source_url', $source !== '' ? $source : $d['source_url']);
}
function atendimento_docentes_seed_from_people(string $scope = 'principal'): void {
    $prefix = people_scope_setting_prefix($scope);
    $current = atendimento_docentes_get($scope);
    atendimento_docentes_save([
        'title' => $current['title'],
        'summary' => $current['summary'],
        'intro_html' => $current['intro_html'],
        'table_html' => atendimento_docentes_generate_table_html($scope),
        'notes_html' => $current['notes_html'],
        'source_url' => $current['source_url'],
    ], $scope);
    site_setting_set($prefix . 'atendimento_docentes_last_sync', date('Y-m-d H:i:s'));
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
function fix_mojibake_ptbr(string $text): string {
    if ($text === '' || (strpos($text, 'Ãƒ') === false && strpos($text, 'Ã‚') === false)) {
        return $text;
    }
    $map = [
        'ÃƒÂ¡' => 'Ã¡', 'ÃƒÃ ' => 'Ã ', 'ÃƒÂ¢' => 'Ã¢', 'ÃƒÃ£' => 'Ã£', 'ÃƒÃ¤' => 'Ã¤',
        'ÃƒÃ©' => 'Ã©', 'ÃƒÃ¨' => 'Ã¨', 'ÃƒÃª' => 'Ãª', 'ÃƒÃ«' => 'Ã«',
        'ÃƒÃ­' => 'Ã­', 'ÃƒÃ¬' => 'Ã¬', 'ÃƒÃ®' => 'Ã®', 'ÃƒÃ¯' => 'Ã¯',
        'ÃƒÃ³' => 'Ã³', 'ÃƒÃ²' => 'Ã²', 'ÃƒÃ´' => 'Ã´', 'ÃƒÃµ' => 'Ãµ', 'ÃƒÃ¶' => 'Ã¶',
        'ÃƒÃº' => 'Ãº', 'ÃƒÃ¹' => 'Ã¹', 'ÃƒÃ»' => 'Ã»', 'ÃƒÃ¼' => 'Ã¼',
        'ÃƒÃ§' => 'Ã§', 'ÃƒÃ±' => 'Ã±',
        'ÃƒÃ' => 'Ã', 'ÃƒÃ€' => 'Ã€', 'ÃƒÃ‚' => 'Ã‚', 'ÃƒÃƒ' => 'Ãƒ', 'ÃƒÃ„' => 'Ã„',
        'ÃƒÃ‰' => 'Ã‰', 'ÃƒÃˆ' => 'Ãˆ', 'ÃƒÃŠ' => 'ÃŠ', 'ÃƒÃ‹' => 'Ã‹',
        'ÃƒÃ' => 'Ã', 'ÃƒÃŒ' => 'ÃŒ', 'ÃƒÃŽ' => 'ÃŽ', 'ÃƒÃ' => 'Ã',
        'ÃƒÃ“' => 'Ã“', 'ÃƒÃ’' => 'Ã’', 'ÃƒÃ”' => 'Ã”', 'ÃƒÃ•' => 'Ã•', 'ÃƒÃ–' => 'Ã–',
        'ÃƒÃš' => 'Ãš', 'ÃƒÃ™' => 'Ã™', 'ÃƒÃ›' => 'Ã›', 'ÃƒÃœ' => 'Ãœ',
        'ÃƒÃ‡' => 'Ã‡', 'ÃƒÃ‘' => 'Ã‘',
        'Ã‚Âº' => 'Âº', 'Ã‚Âª' => 'Âª', 'Ã‚Â°' => 'Â°',
        'Ã¢â‚¬â€œ' => 'â€“', 'Ã¢â‚¬â€' => 'â€”', 'Ã¢â‚¬Ëœ' => 'â€˜', 'Ã¢â‚¬â„¢' => 'â€™', 'Ã¢â‚¬Å“' => 'â€œ', 'Ã¢â‚¬Â' => 'â€',
        'Ã¢â‚¬Â¦' => 'â€¦', 'Ã¢â‚¬Â¢' => 'â€¢',
        'ÃƒÆ’Ã‚Â¡' => 'Ã¡', 'ÃƒÆ’Ã‚Â£' => 'Ã£', 'ÃƒÆ’Ã‚Â§' => 'Ã§', 'ÃƒÆ’Ã‚Â©' => 'Ã©', 'ÃƒÆ’Ã‚Âª' => 'Ãª', 'ÃƒÆ’Ã‚Â³' => 'Ã³', 'ÃƒÆ’Ã‚Âµ' => 'Ãµ', 'ÃƒÆ’Ã‚Âº' => 'Ãº',
        'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡' => 'Ã¡', 'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£' => 'Ã£', 'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§' => 'Ã§', 'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©' => 'Ã©', 'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âª' => 'Ãª', 'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³' => 'Ã³', 'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµ' => 'Ãµ', 'ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âº' => 'Ãº',
    ];
    $fixed = strtr($text, $map);
    if (strpos($fixed, 'Ãƒ') === false && strpos($fixed, 'Ã‚') === false) {
        return $fixed;
    }
    $decoded = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $fixed);
    return (is_string($decoded) && $decoded !== '') ? $decoded : $fixed;
}
function primary_menu_item(string $slot): array {
    $defaults = [
        'graduacao' => ['label' => 'Graduacao', 'url' => '/ensino/ciencia-computacao.php'],
        'pos_graduacao' => ['label' => 'PÃ³s-graduaÃ§Ã£o', 'url' => '/ensino/pos-graduacao.php'],
    ];
    $default = $defaults[$slot] ?? ['label' => 'Menu', 'url' => '/'];
    $label = trim(fix_mojibake_ptbr(site_setting_get('menu_' . $slot . '_label', $default['label'])));
    if ($slot === 'pos_graduacao' && ($label === 'Pos-graduacao' || $label === 'PÃƒÂ³s-graduaÃƒÂ§ÃƒÂ£o' || $label === 'PÃƒÆ’Ã‚Â³s-graduaÃƒÆ’Ã‚Â§ÃƒÆ’Ã‚Â£o')) {
        $label = 'PÃ³s-graduaÃ§Ã£o';
    }
    $url = normalize_menu_url(site_setting_get('menu_' . $slot . '_url', $default['url']), $default['url']);
    return [
        'label' => $label !== '' ? $label : $default['label'],
        'url' => $url,
    ];
}
function easter_date_ymd(int $year): string {
    $a = $year % 19;
    $b = intdiv($year, 100);
    $c = $year % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $month = intdiv($h + $l - 7 * $m + 114, 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}
function date_add_days(string $ymd, int $days): string {
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
    if (!$dt) {
        return $ymd;
    }
    return $dt->modify(($days >= 0 ? '+' : '') . $days . ' days')->format('Y-m-d');
}
function ufop_holidays_for_year(int $year): array {
    $easter = easter_date_ymd($year);
    $list = [
        sprintf('%04d-01-01', $year) => 'Confraternizacao Universal',
        date_add_days($easter, -48) => 'Carnaval (ponto facultativo)',
        date_add_days($easter, -47) => 'Carnaval (ponto facultativo)',
        date_add_days($easter, -2) => 'Paixao de Cristo',
        date_add_days($easter, -1) => 'Sabado de Aleluia (ponto facultativo)',
        date_add_days($easter, 60) => 'Corpus Christi (ponto facultativo)',
        sprintf('%04d-04-21', $year) => 'Tiradentes',
        sprintf('%04d-05-01', $year) => 'Dia do Trabalho',
        sprintf('%04d-09-07', $year) => 'Independencia do Brasil',
        sprintf('%04d-10-12', $year) => 'Nossa Senhora Aparecida',
        sprintf('%04d-11-02', $year) => 'Finados',
        sprintf('%04d-11-15', $year) => 'Proclamacao da Republica',
        sprintf('%04d-11-20', $year) => 'Dia da Consciencia Negra',
        sprintf('%04d-12-25', $year) => 'Natal',
    ];
    return $list;
}
function ufop_month_pt_to_number(string $month): int {
    $m = strtolower(trim($month));
    $m = strtr($m, ['ÃƒÆ’Ã‚Â§' => 'c', 'ÃƒÆ’Ã‚Â£' => 'a', 'ÃƒÆ’Ã‚Â¡' => 'a', 'ÃƒÆ’Ã‚Â¢' => 'a', 'ÃƒÆ’Ã‚Â©' => 'e']);
    $map = [
        'janeiro' => 1,
        'fevereiro' => 2,
        'marco' => 3,
        'marco.' => 3,
        'marÃƒÆ’Ã‚Â§o' => 3,
        'abril' => 4,
        'maio' => 5,
        'junho' => 6,
        'julho' => 7,
        'agosto' => 8,
        'setembro' => 9,
        'outubro' => 10,
        'novembro' => 11,
        'dezembro' => 12,
    ];
    return $map[$m] ?? 0;
}
function ufop_fetch_current_calendar_events(int $year, int $month): array {
    $eventsByDay = [];
    try {
        $stmt = db()->prepare(
            "SELECT title, category, published_at
             FROM news_items
             WHERE YEAR(published_at) = :y
               AND MONTH(published_at) = :m
               AND (
                    LOWER(COALESCE(category, '')) LIKE '%evento%'
                    OR LOWER(COALESCE(title, '')) LIKE '%evento%'
               )
             ORDER BY published_at ASC, id ASC
             LIMIT 200"
        );
        $stmt->execute([':y' => $year, ':m' => $month]);
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $publishedAt = (string)($row['published_at'] ?? '');
            $day = (int)substr($publishedAt, 8, 2);
            if ($day < 1 || $day > 31) {
                continue;
            }
            $title = trim((string)($row['title'] ?? 'Evento do departamento'));
            $eventsByDay[$day][] = [
                'type' => 'event',
                'title' => $title !== '' ? $title : 'Evento do departamento',
                'source' => 'Departamento',
            ];
        }
    } catch (Throwable $e) {
        error_log('Failed loading department events for calendar: ' . $e->getMessage());
    }
    return $eventsByDay;
}
function student_calendar_source_url(): string {
    $default = 'https://www.prograd.ufop.br/calendario-academico';
    $url = trim(site_setting_get('student_calendar_source_url', $default));
    if ($url === '' || preg_match('~^https?://~i', $url) !== 1) {
        return $default;
    }
    return $url;
}
function student_calendar_show_holidays(): bool {
    return site_setting_get('student_calendar_show_holidays', '1') !== '0';
}
function student_calendar_manual_events_for_month(int $year, int $month): array {
    $raw = trim(site_setting_get('student_calendar_manual_events', ''));
    if ($raw === '') {
        return [];
    }
    $out = [];
    $lines = preg_split("/(\r\n|\n|\r)/", $raw) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})\s*\|\s*(.+)$/', $line, $m)) {
            continue;
        }
        $y = (int)$m[1];
        $mo = (int)$m[2];
        $d = (int)$m[3];
        if ($y !== $year || $mo !== $month || $d < 1 || $d > 31) {
            continue;
        }
        $title = trim((string)$m[4]);
        if ($title === '') {
            continue;
        }
        $out[$d][] = ['type' => 'event', 'title' => $title, 'source' => 'Departamento (manual)'];
    }
    return $out;
}
function ufop_student_calendar(int $year = 0, int $month = 0): array {
    $now = new DateTimeImmutable('now');
    $year = $year > 0 ? $year : (int)$now->format('Y');
    $month = ($month >= 1 && $month <= 12) ? $month : (int)$now->format('n');

    $first = DateTimeImmutable::createFromFormat('Y-n-j', $year . '-' . $month . '-1');
    if (!$first) {
        $first = $now->modify('first day of this month');
    }

    $daysInMonth = (int)$first->format('t');
    $firstDow = (int)$first->format('w'); // 0=dom ... 6=sab

    $showHolidays = student_calendar_show_holidays();
    $holidays = $showHolidays ? ufop_holidays_for_year($year) : [];
    $events = ufop_fetch_current_calendar_events($year, $month);
    $manualEvents = student_calendar_manual_events_for_month($year, $month);
    $dayMap = [];
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $ymd = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dayMap[$d] = [];
        if (isset($holidays[$ymd])) {
            $dayMap[$d][] = ['type' => 'holiday', 'title' => $holidays[$ymd], 'source' => 'Calendario oficial'];
        }
        foreach (($events[$d] ?? []) as $ev) {
            $dayMap[$d][] = $ev;
        }
        foreach (($manualEvents[$d] ?? []) as $ev) {
            $dayMap[$d][] = $ev;
        }
    }

    $monthNames = [1 => 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return [
        'year' => $year,
        'month' => $month,
        'month_name' => $monthNames[$month] ?? (string)$month,
        'days_in_month' => $daysInMonth,
        'first_dow' => $firstDow,
        'weekdays' => ['d', 's', 't', 'q', 'q', 's', 's'],
        'days' => $dayMap,
        'source_url' => student_calendar_source_url(),
        'source_label' => 'Calendario oficial UFOP (PROGRAD)',
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
        error_log('Failed ensuring post-graduation tables: ' . $e->getMessage());
    }
}
function ppgcc_section_defaults(string $section): array {
    $slug = $section === 'extensao' ? 'extensao' : 'pesquisa';
    if ($slug === 'extensao') {
        return [
            'title' => 'Extensao da Pos-graduacao',
            'summary' => 'Projetos, acoes e programas de extensao vinculados a pos-graduacao.',
            'content_html' => '<p>Cadastre aqui os projetos, editais, oficinas e acoes de extensao da pos-graduacao.</p>',
        ];
    }
    return [
        'title' => 'Pesquisa da Pos-graduacao',
        'summary' => 'Linhas, grupos e projetos de pesquisa da pos-graduacao.',
        'content_html' => '<p>Cadastre aqui as linhas de pesquisa, laboratorios e projetos ativos da pos-graduacao.</p>',
    ];
}
function ppgcc_section_get(string $section): array {
    $slug = $section === 'extensao' ? 'extensao' : 'pesquisa';
    $defaults = ppgcc_section_defaults($slug);
    $prefix = 'ppgcc_section_' . $slug . '_';
    return [
        'section' => $slug,
        'title' => trim(site_setting_get($prefix . 'title', $defaults['title'])),
        'summary' => trim(site_setting_get($prefix . 'summary', $defaults['summary'])),
        'content_html' => site_setting_get($prefix . 'content_html', $defaults['content_html']),
    ];
}
function ppgcc_section_save(string $section, array $data): void {
    $slug = $section === 'extensao' ? 'extensao' : 'pesquisa';
    $defaults = ppgcc_section_defaults($slug);
    $prefix = 'ppgcc_section_' . $slug . '_';
    $title = trim((string)($data['title'] ?? $defaults['title']));
    $summary = trim((string)($data['summary'] ?? $defaults['summary']));
    $contentHtml = sanitize_rich_text((string)($data['content_html'] ?? $defaults['content_html']));
    site_setting_set($prefix . 'title', $title !== '' ? $title : $defaults['title']);
    site_setting_set($prefix . 'summary', $summary !== '' ? $summary : $defaults['summary']);
    site_setting_set($prefix . 'content_html', $contentHtml !== '' ? $contentHtml : $defaults['content_html']);
}
function laboratory_about_defaults(): array {
    return [
        'title' => 'O Laboratorio',
        'summary' => 'Laboratorio de pesquisa vinculado a departamento de universidade federal, com atuacao em ensino, pesquisa, extensao e inovacao.',
        'content_html' => '<p>Esta pagina apresenta a missao, as linhas de pesquisa e os objetivos estrategicos do laboratorio.</p><p>Inclua historico, infraestrutura, areas de atuacao, parcerias e indicadores de impacto.</p>',
    ];
}
function laboratory_about_get(): array {
    $defaults = laboratory_about_defaults();
    $prefix = 'laboratorio_about_';
    return [
        'title' => trim(site_setting_get($prefix . 'title', $defaults['title'])),
        'summary' => trim(site_setting_get($prefix . 'summary', $defaults['summary'])),
        'content_html' => site_setting_get($prefix . 'content_html', $defaults['content_html']),
    ];
}
function laboratory_about_save(array $data): void {
    $defaults = laboratory_about_defaults();
    $prefix = 'laboratorio_about_';
    $title = trim((string)($data['title'] ?? $defaults['title']));
    $summary = trim((string)($data['summary'] ?? $defaults['summary']));
    $contentHtml = sanitize_rich_text((string)($data['content_html'] ?? $defaults['content_html']));
    site_setting_set($prefix . 'title', $title !== '' ? $title : $defaults['title']);
    site_setting_set($prefix . 'summary', $summary !== '' ? $summary : $defaults['summary']);
    site_setting_set($prefix . 'content_html', $contentHtml !== '' ? $contentHtml : $defaults['content_html']);
}
function laboratory_about_carousel_defaults(): array {
    return [
        [
            'id' => 'about-default-1',
            'image' => '/assets/images/carousel/lab-2.png',
            'title' => 'Infraestrutura de pesquisa',
            'caption' => 'Espacos e equipamentos para atividades de ensino, pesquisa e extensao.',
        ],
    ];
}
function laboratory_about_carousel_slide_normalize(array $slide, string $fallbackId): array {
    $id = trim((string)($slide['id'] ?? ''));
    if ($id === '') {
        $id = $fallbackId;
    }
    $image = trim((string)($slide['image'] ?? ''));
    if ($image === '') {
        $image = '/assets/images/carousel/lab-2.png';
    }
    return [
        'id' => $id,
        'image' => $image,
        'title' => trim((string)($slide['title'] ?? '')),
        'caption' => trim((string)($slide['caption'] ?? '')),
    ];
}
function laboratory_about_carousel_get(): array {
    $json = trim(site_setting_get('laboratory_about_carousel_json', ''));
    if ($json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded) && !empty($decoded)) {
            $out = [];
            $idx = 1;
            foreach ($decoded as $slide) {
                if (!is_array($slide)) {
                    continue;
                }
                $out[] = laboratory_about_carousel_slide_normalize($slide, 'about-slide-' . $idx);
                $idx++;
            }
            if (!empty($out)) {
                return $out;
            }
        }
    }
    return laboratory_about_carousel_defaults();
}
function laboratory_about_carousel_save(array $slides): void {
    $normalized = [];
    $idx = 1;
    foreach ($slides as $slide) {
        if (!is_array($slide)) {
            continue;
        }
        $n = laboratory_about_carousel_slide_normalize($slide, 'about-slide-' . $idx);
        if ($n['image'] === '' && $n['title'] === '' && $n['caption'] === '') {
            continue;
        }
        $normalized[] = $n;
        $idx++;
    }
    if (empty($normalized)) {
        $normalized = laboratory_about_carousel_defaults();
    }
    site_setting_set('laboratory_about_carousel_json', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
function laboratory_contact_defaults(): array {
    return [
        'title' => 'Contato',
        'summary' => 'Fale com o laboratorio para informacoes institucionais, parcerias e projetos.',
        'email' => SITE_EMAIL,
        'phone' => SITE_PHONE,
        'address' => SITE_ADDRESS,
        'content_html' => '<p>Use os canais ao lado para contato institucional com o laboratorio.</p>',
    ];
}
function laboratory_contact_get(): array {
    $defaults = laboratory_contact_defaults();
    $prefix = 'laboratorio_contact_';
    return [
        'title' => trim(site_setting_get($prefix . 'title', $defaults['title'])),
        'summary' => trim(site_setting_get($prefix . 'summary', $defaults['summary'])),
        'email' => trim(site_setting_get($prefix . 'email', $defaults['email'])),
        'phone' => trim(site_setting_get($prefix . 'phone', $defaults['phone'])),
        'address' => trim(site_setting_get($prefix . 'address', $defaults['address'])),
        'content_html' => site_setting_get($prefix . 'content_html', $defaults['content_html']),
    ];
}
function laboratory_contact_save(array $data): void {
    $defaults = laboratory_contact_defaults();
    $prefix = 'laboratorio_contact_';
    $title = trim((string)($data['title'] ?? $defaults['title']));
    $summary = trim((string)($data['summary'] ?? $defaults['summary']));
    $email = trim((string)($data['email'] ?? $defaults['email']));
    $phone = trim((string)($data['phone'] ?? $defaults['phone']));
    $address = trim((string)($data['address'] ?? $defaults['address']));
    $contentHtml = sanitize_rich_text((string)($data['content_html'] ?? $defaults['content_html']));
    site_setting_set($prefix . 'title', $title !== '' ? $title : $defaults['title']);
    site_setting_set($prefix . 'summary', $summary !== '' ? $summary : $defaults['summary']);
    site_setting_set($prefix . 'email', $email !== '' ? $email : $defaults['email']);
    site_setting_set($prefix . 'phone', $phone !== '' ? $phone : $defaults['phone']);
    site_setting_set($prefix . 'address', $address !== '' ? $address : $defaults['address']);
    site_setting_set($prefix . 'content_html', $contentHtml !== '' ? $contentHtml : $defaults['content_html']);
}
function footer_defaults(): array {
    return [
        'brand_name' => SITE_NAME,
        'brand_sigla' => SITE_SIGLA,
        'brand_university' => SITE_UNIVERSITY,
        'contact_phone' => SITE_PHONE,
        'contact_email' => SITE_EMAIL,
        'show_calendar' => true,
        'links' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'O Laboratorio', 'url' => '/laboratorio/sobre.php'],
            ['label' => 'Equipe', 'url' => '/laboratorio/equipe.php'],
            ['label' => 'Projetos', 'url' => '/laboratorio/projetos.php'],
            ['label' => 'Publicacoes', 'url' => '/laboratorio/publicacoes.php'],
            ['label' => 'Cursos', 'url' => '/laboratorio/cursos.php'],
            ['label' => 'Parceiros', 'url' => '/laboratorio/parceiros.php'],
            ['label' => 'Tutoriais', 'url' => '/laboratorio/tutoriais.php'],
            ['label' => 'Blog', 'url' => '/laboratorio/blog.php'],
            ['label' => 'Eventos', 'url' => '/laboratorio/eventos.php'],
            ['label' => 'Contato', 'url' => '/contato/index.php'],
            ['label' => 'Noticias', 'url' => '/noticias/index.php'],
            ['label' => 'Editais', 'url' => '/noticias/editais.php'],
        ],
    ];
}
function footer_links_normalize(array $links): array {
    $out = [];
    foreach ($links as $link) {
        if (!is_array($link)) {
            continue;
        }
        $label = trim((string)($link['label'] ?? ''));
        $url = trim((string)($link['url'] ?? ''));
        if ($label === '' || $url === '') {
            continue;
        }
        $out[] = [
            'label' => $label,
            'url' => normalize_menu_url($url, '/'),
        ];
    }
    return $out;
}
function footer_get(): array {
    $d = footer_defaults();
    $rawLinks = trim(site_setting_get('footer_links_json', ''));
    $links = $d['links'];
    if ($rawLinks !== '') {
        $decoded = json_decode($rawLinks, true);
        if (is_array($decoded)) {
            $normalized = footer_links_normalize($decoded);
            if (!empty($normalized)) {
                $links = $normalized;
            }
        }
    }
    $rawShowCalendar = site_setting_get('footer_show_calendar', '__unset__');
    if ($rawShowCalendar === '__unset__') {
        $showCalendar = site_setting_get('show_student_calendar', '1') !== '0';
    } else {
        $showCalendar = $rawShowCalendar !== '0';
    }
    return [
        'brand_name' => trim(site_setting_get('footer_brand_name', $d['brand_name'])),
        'brand_sigla' => trim(site_setting_get('footer_brand_sigla', $d['brand_sigla'])),
        'brand_university' => trim(site_setting_get('footer_brand_university', $d['brand_university'])),
        'contact_phone' => trim(site_setting_get('footer_contact_phone', $d['contact_phone'])),
        'contact_email' => trim(site_setting_get('footer_contact_email', $d['contact_email'])),
        'show_calendar' => $showCalendar,
        'links' => $links,
    ];
}
function footer_save(array $data): void {
    $d = footer_defaults();
    $brandName = trim((string)($data['brand_name'] ?? $d['brand_name']));
    $brandSigla = trim((string)($data['brand_sigla'] ?? $d['brand_sigla']));
    $brandUniversity = trim((string)($data['brand_university'] ?? $d['brand_university']));
    $contactPhone = trim((string)($data['contact_phone'] ?? $d['contact_phone']));
    $contactEmail = trim((string)($data['contact_email'] ?? $d['contact_email']));
    $showCalendar = isset($data['show_calendar']) ? '1' : '0';
    $links = footer_links_normalize((array)($data['links'] ?? $d['links']));
    if (empty($links)) {
        $links = $d['links'];
    }

    site_setting_set('footer_brand_name', $brandName !== '' ? $brandName : $d['brand_name']);
    site_setting_set('footer_brand_sigla', $brandSigla !== '' ? $brandSigla : $d['brand_sigla']);
    site_setting_set('footer_brand_university', $brandUniversity !== '' ? $brandUniversity : $d['brand_university']);
    site_setting_set('footer_contact_phone', $contactPhone !== '' ? $contactPhone : $d['contact_phone']);
    site_setting_set('footer_contact_email', $contactEmail !== '' ? $contactEmail : $d['contact_email']);
    site_setting_set('footer_show_calendar', $showCalendar);
    site_setting_set('show_student_calendar', $showCalendar);
    site_setting_set('footer_links_json', json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
function laboratory_page_catalog(): array {
    return [
        'projetos' => [
            'label' => 'Projetos',
            'public_url' => '/laboratorio/projetos.php',
            'defaults' => [
                'title' => 'Projetos',
                'summary' => 'Projetos de pesquisa e extensao conduzidos pelo laboratorio.',
                'content_html' => '<p>Descreva os projetos ativos do laboratorio, objetivos, equipe envolvida e resultados esperados.</p>',
            ],
        ],
        'publicacoes' => [
            'label' => 'Publicacoes',
            'public_url' => '/laboratorio/publicacoes.php',
            'defaults' => [
                'title' => 'Publicacoes',
                'summary' => 'Producao cientifica do laboratorio, em parceria com o departamento.',
                'content_html' => '<p>Liste artigos, livros, capitulos e trabalhos em eventos produzidos pela equipe do laboratorio.</p>',
            ],
        ],
        'cursos' => [
            'label' => 'Cursos',
            'public_url' => '/laboratorio/cursos.php',
            'defaults' => [
                'title' => 'Cursos',
                'summary' => 'Formacoes e capacitacoes vinculadas ao laboratorio.',
                'content_html' => '<p>Cadastre cursos, oficinas e trilhas de formacao oferecidas pelo laboratorio.</p>',
            ],
        ],
        'parceiros' => [
            'label' => 'Parceiros',
            'public_url' => '/laboratorio/parceiros.php',
            'defaults' => [
                'title' => 'Parceiros',
                'summary' => 'Instituicoes e grupos que colaboram com o laboratorio.',
                'content_html' => '<p>Apresente os parceiros institucionais, academicos e do setor produtivo.</p>',
            ],
        ],
        'tutoriais' => [
            'label' => 'Tutoriais',
            'public_url' => '/laboratorio/tutoriais.php',
            'defaults' => [
                'title' => 'Tutoriais',
                'summary' => 'Guias tecnicos para atividades de pesquisa, desenvolvimento e reproducao de resultados.',
                'content_html' => '<p>Publique tutoriais tecnicos, boas praticas e guias de reproducibilidade.</p>',
            ],
        ],
        'blog' => [
            'label' => 'Blog',
            'public_url' => '/laboratorio/blog.php',
            'defaults' => [
                'title' => 'Blog',
                'summary' => 'Noticias, atualizacoes de pesquisa e textos tecnicos do laboratorio.',
                'content_html' => '<p>Espaco para comunicados, artigos curtos e atualizacoes das atividades do laboratorio.</p>',
            ],
        ],
        'eventos' => [
            'label' => 'Eventos',
            'public_url' => '/laboratorio/eventos.php',
            'defaults' => [
                'title' => 'Eventos',
                'summary' => 'Agenda de seminarios, oficinas, palestras e encontros do laboratorio.',
                'content_html' => '<p>Divulgue aqui os eventos promovidos pelo laboratorio e seus parceiros.</p>',
            ],
        ],
    ];
}
function laboratory_page_slug_normalize(string $slug): string {
    $slug = trim(mb_strtolower($slug, 'UTF-8'));
    $catalog = laboratory_page_catalog();
    return array_key_exists($slug, $catalog) ? $slug : 'projetos';
}
function laboratory_page_get(string $slug): array {
    $slug = laboratory_page_slug_normalize($slug);
    $catalog = laboratory_page_catalog();
    $meta = $catalog[$slug];
    $defaults = (array)$meta['defaults'];
    $prefix = 'laboratorio_page_' . $slug . '_';
    return [
        'slug' => $slug,
        'label' => (string)$meta['label'],
        'public_url' => (string)$meta['public_url'],
        'title' => trim(site_setting_get($prefix . 'title', (string)$defaults['title'])),
        'summary' => trim(site_setting_get($prefix . 'summary', (string)$defaults['summary'])),
        'content_html' => site_setting_get($prefix . 'content_html', (string)$defaults['content_html']),
    ];
}
function laboratory_page_save(string $slug, array $data): void {
    $slug = laboratory_page_slug_normalize($slug);
    $catalog = laboratory_page_catalog();
    $meta = $catalog[$slug];
    $defaults = (array)$meta['defaults'];
    $prefix = 'laboratorio_page_' . $slug . '_';
    $title = trim((string)($data['title'] ?? (string)$defaults['title']));
    $summary = trim((string)($data['summary'] ?? (string)$defaults['summary']));
    $contentHtml = sanitize_rich_text((string)($data['content_html'] ?? (string)$defaults['content_html']));
    site_setting_set($prefix . 'title', $title !== '' ? $title : (string)$defaults['title']);
    site_setting_set($prefix . 'summary', $summary !== '' ? $summary : (string)$defaults['summary']);
    site_setting_set($prefix . 'content_html', $contentHtml !== '' ? $contentHtml : (string)$defaults['content_html']);
}
function ensure_laboratory_page_items_table(): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS laboratory_page_items (
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
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('Failed ensuring laboratory_page_items table: ' . $e->getMessage());
    }
}
function laboratory_page_item_unique_slug(string $pageSlug, string $baseSlug, ?int $ignoreId = null): string {
    ensure_laboratory_page_items_table();
    $slug = simple_slugify($baseSlug);
    $i = 1;
    while (true) {
        $sql = 'SELECT id FROM laboratory_page_items WHERE page_slug = :page_slug AND slug = :slug';
        $params = [':page_slug' => $pageSlug, ':slug' => $slug];
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
        $slug = substr(simple_slugify($baseSlug), 0, 150 - strlen((string)$i) - 1) . '-' . $i;
    }
}
function laboratory_page_items_years(string $pageSlug): array {
    ensure_laboratory_page_items_table();
    try {
        $stmt = db()->prepare(
            "SELECT DISTINCT YEAR(published_at) AS y
             FROM laboratory_page_items
             WHERE page_slug = :page_slug AND is_active = 1
             ORDER BY y DESC"
        );
        $stmt->execute([':page_slug' => $pageSlug]);
        return array_values(array_filter(array_map(static fn(array $r): int => (int)$r['y'], $stmt->fetchAll()), static fn(int $y): bool => $y > 0));
    } catch (Throwable $e) {
        error_log('Failed loading laboratory page years: ' . $e->getMessage());
        return [];
    }
}
function laboratory_page_items_paginated(string $pageSlug, int $selectedYear = 0, int $currentPage = 1, int $perPage = 9): array {
    ensure_laboratory_page_items_table();
    $years = laboratory_page_items_years($pageSlug);
    if ($selectedYear <= 0 && !empty($years)) {
        $selectedYear = $years[0];
    }
    $currentPage = max(1, $currentPage);
    $totalItems = 0;
    $totalPages = 1;
    $items = [];

    try {
        if ($selectedYear > 0) {
            $countStmt = db()->prepare(
                "SELECT COUNT(*)
                 FROM laboratory_page_items
                 WHERE page_slug = :page_slug AND is_active = 1 AND YEAR(published_at) = :year"
            );
            $countStmt->execute([':page_slug' => $pageSlug, ':year' => $selectedYear]);
            $totalItems = (int)$countStmt->fetchColumn();
            $totalPages = max(1, (int)ceil($totalItems / max(1, $perPage)));
            $currentPage = min($currentPage, $totalPages);
            $offset = ($currentPage - 1) * $perPage;

            $stmt = db()->prepare(
                "SELECT id, slug, title, summary, category, content_html, image_url, external_url, published_at
                 FROM laboratory_page_items
                 WHERE page_slug = :page_slug AND is_active = 1 AND YEAR(published_at) = :year
                 ORDER BY sort_order ASC, published_at DESC, id DESC
                 LIMIT :limite OFFSET :offset"
            );
            $stmt->bindValue(':page_slug', $pageSlug, PDO::PARAM_STR);
            $stmt->bindValue(':year', $selectedYear, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = $stmt->fetchAll();
        }
    } catch (Throwable $e) {
        error_log('Failed loading laboratory page items: ' . $e->getMessage());
    }

    return [
        'years' => $years,
        'selected_year' => $selectedYear,
        'current_page' => $currentPage,
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'items' => $items,
    ];
}
function laboratory_page_items_latest(string $pageSlug, int $limit = 3): array {
    ensure_laboratory_page_items_table();
    $limit = max(1, min(20, $limit));
    try {
        $stmt = db()->prepare(
            "SELECT id, slug, title, summary, category, image_url, external_url, published_at
             FROM laboratory_page_items
             WHERE page_slug = :page_slug AND is_active = 1
             ORDER BY sort_order ASC, published_at DESC, id DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':page_slug', $pageSlug, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Failed loading latest laboratory page items: ' . $e->getMessage());
        return [];
    }
}
function laboratory_page_public_url(string $slug): string {
    $meta = laboratory_page_get($slug);
    return (string)($meta['public_url'] ?? '/');
}
function laboratory_page_build_url(string $slug, int $year, int $page): string {
    return laboratory_page_public_url($slug) . '?ano=' . urlencode((string)$year) . '&pagina=' . urlencode((string)$page);
}
function laboratory_page_item_find(string $pageSlug, string $itemSlug): ?array {
    ensure_laboratory_page_items_table();
    try {
        $stmt = db()->prepare(
            "SELECT id, page_slug, slug, title, summary, category, content_html, image_url, external_url, published_at
             FROM laboratory_page_items
             WHERE page_slug = :page_slug AND slug = :slug AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':page_slug' => $pageSlug, ':slug' => $itemSlug]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('Failed finding laboratory page item: ' . $e->getMessage());
        return null;
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
        'title' => 'Pos-graduacao do Departamento',
        'intro_html' => '<p>A pos-graduacao do departamento oferece formacao academica avancada com foco em pesquisa, inovacao e qualificacao docente.</p>',
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
        ':title' => trim((string)($data['title'] ?? 'Pos-graduacao do Departamento')),
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
            $title = $normalize((string)($x->query('//title')->item(0)?->textContent ?? 'Pagina da Pos-graduacao'));
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
function admin_default_seed_accounts(): array {
    $accounts = [
        [
            'name' => 'Superadmin',
            'email' => 'superadmin@departamento.local',
            'password' => 'Super@2026!',
            'role' => 'superadmin',
        ],
        [
            'name' => 'Editor',
            'email' => 'editor@departamento.local',
            'password' => 'Editor@2026!',
            'role' => 'editor',
        ],
        [
            'name' => 'Secretaria',
            'email' => 'secretaria@departamento.local',
            'password' => 'Secretaria@2026!',
            'role' => 'secretaria',
        ],
        [
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => 'SuperAdmin@2026',
            'role' => 'superadmin',
        ],
    ];

    $envEmail = admin_email_config();
    $envHash = admin_password_hash_config();
    if ($envEmail !== '' && $envHash !== '') {
        $found = false;
        foreach ($accounts as $account) {
            if (mb_strtolower((string)$account['email'], 'UTF-8') === mb_strtolower($envEmail, 'UTF-8')) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $accounts[] = [
                'name' => 'Administrador',
                'email' => $envEmail,
                'password_hash' => $envHash,
                'role' => 'superadmin',
            ];
        }
    }

    return $accounts;
}
function ensure_default_admin_user(): void {
    ensure_admin_users_table();
    try {
        foreach (admin_default_seed_accounts() as $account) {
            $email = trim((string)($account['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $name = trim((string)($account['name'] ?? 'Administrador'));
            $role = admin_normalize_role((string)($account['role'] ?? 'editor'));
            $seedHash = trim((string)($account['password_hash'] ?? ''));
            $seedPassword = (string)($account['password'] ?? '');

            $stmt = db()->prepare('SELECT id, password_hash, role, is_active, name FROM admin_users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $existing = $stmt->fetch();

            if (!$existing) {
                $insertHash = $seedHash !== '' ? $seedHash : password_hash($seedPassword, PASSWORD_DEFAULT);
                $insert = db()->prepare(
                    'INSERT INTO admin_users (name, email, password_hash, role, is_active)
                     VALUES (:name, :email, :password_hash, :role, 1)'
                );
                $insert->execute([
                    ':name' => $name !== '' ? $name : 'Administrador',
                    ':email' => $email,
                    ':password_hash' => $insertHash,
                    ':role' => $role,
                ]);
                continue;
            }

            $needsPasswordUpdate = false;
            if ($seedHash !== '') {
                $needsPasswordUpdate = !hash_equals((string)$existing['password_hash'], $seedHash);
            } elseif ($seedPassword !== '') {
                $needsPasswordUpdate = !password_verify($seedPassword, (string)$existing['password_hash']);
            }
            $needsMetaUpdate =
                (string)$existing['role'] !== $role
                || (int)$existing['is_active'] !== 1
                || trim((string)$existing['name']) !== $name;

            if ($needsPasswordUpdate || $needsMetaUpdate) {
                $updateHash = (string)$existing['password_hash'];
                if ($needsPasswordUpdate) {
                    $updateHash = $seedHash !== '' ? $seedHash : password_hash($seedPassword, PASSWORD_DEFAULT);
                }
                $update = db()->prepare(
                    'UPDATE admin_users
                     SET name = :name, password_hash = :password_hash, role = :role, is_active = 1
                     WHERE id = :id'
                );
                $update->execute([
                    ':id' => (int)$existing['id'],
                    ':name' => $name !== '' ? $name : 'Administrador',
                    ':password_hash' => $updateHash,
                    ':role' => $role,
                ]);
            }
        }
    } catch (Throwable $e) {
        error_log('Failed ensuring default admin users: ' . $e->getMessage());
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
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acesso negado</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"></head><body class="bg-light"><main class="container py-5"><div class="alert alert-danger"><h1 class="h4 mb-2">Acesso negado</h1><p class="mb-0">Sua conta nao possui permissao para acessar este modulo.</p></div><div class="d-flex gap-2"><a class="btn btn-primary" href="/admin/dashboard.php">Voltar ao painel</a><a class="btn btn-outline-secondary" href="/">Voltar ao site</a></div></main></body></html>';
    exit;
}
function render_admin_sidebar(string $active = 'dashboard'): void {
    $is = static fn(string $key): bool => $active === $key;
    $in = static fn(array $keys): bool => in_array($active, $keys, true);
    $grpContent = ['content_noticias', 'content_editais', 'content_carousel'];
    $grpHome = ['carousel', 'tema', 'menu', 'footer', 'lab_about', 'lab_about_carousel', 'lab_pages', 'lab_contact'];
    $openOnDashboard = $is('dashboard');
    ?>
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <style>
            .app-sidebar {
                height: 100vh;
                overflow: hidden;
            }
            .app-sidebar .sidebar-wrapper {
                height: calc(100vh - 3.5rem);
                overflow-y: auto;
                overflow-x: hidden;
                padding-bottom: .75rem;
            }
            .app-sidebar .menu-caret {
                display: inline-block;
                margin-left: .35rem;
                font-size: .8rem;
                opacity: .9;
                transition: transform .2s ease;
            }
            .app-sidebar .menu-open > .nav-link .menu-caret {
                transform: rotate(90deg);
            }
        </style>
        <div class="sidebar-brand">
            <a href="/admin/dashboard.php" class="brand-link text-decoration-none"><span class="brand-text fw-light">Portal Admin</span></a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                    <li class="nav-item<?= ($openOnDashboard || $in($grpContent)) ? ' menu-open' : '' ?>">
                        <a href="#" class="nav-link<?= ($openOnDashboard || $in($grpContent)) ? ' active' : '' ?>"><p>Conteudo <span class="menu-caret" aria-hidden="true">&rsaquo;</span></p></a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="/admin/content.php?type=noticias" class="nav-link<?= $is('content_noticias') ? ' active' : '' ?>"><p>Noticias</p></a></li>
                            <li class="nav-item"><a href="/admin/content.php?type=editais" class="nav-link<?= $is('content_editais') ? ' active' : '' ?>"><p>Editais</p></a></li>
                            <li class="nav-item"><a href="/admin/content-carousel.php" class="nav-link<?= $is('content_carousel') ? ' active' : '' ?>"><p>Carrossel Noticias/Editais</p></a></li>
                        </ul>
                    </li>

                    <li class="nav-item<?= ($openOnDashboard || $in($grpHome)) ? ' menu-open' : '' ?>">
                        <a href="#" class="nav-link<?= ($openOnDashboard || $in($grpHome)) ? ' active' : '' ?>"><p>Home e Visual <span class="menu-caret" aria-hidden="true">&rsaquo;</span></p></a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="/admin/carousel.php" class="nav-link<?= $is('carousel') ? ' active' : '' ?>"><p>Carrossel Home</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-sobre.php" class="nav-link<?= $is('lab_about') ? ' active' : '' ?>"><p>Pagina O Laboratorio</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-sobre-carousel.php" class="nav-link<?= $is('lab_about_carousel') ? ' active' : '' ?>"><p>Carrossel O Laboratorio</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-contato.php" class="nav-link<?= $is('lab_contact') ? ' active' : '' ?>"><p>Pagina Contato</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-paginas.php" class="nav-link<?= $is('lab_pages') ? ' active' : '' ?>"><p>Paginas do Laboratorio</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-paginas.php?slug=projetos" class="nav-link"><p>Editar Projetos</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-paginas.php?slug=publicacoes" class="nav-link"><p>Editar Publicacoes</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-paginas.php?slug=cursos" class="nav-link"><p>Editar Cursos</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-paginas.php?slug=parceiros" class="nav-link"><p>Editar Parceiros</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-paginas.php?slug=tutoriais" class="nav-link"><p>Editar Tutoriais</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-paginas.php?slug=blog" class="nav-link"><p>Editar Blog</p></a></li>
                            <li class="nav-item"><a href="/admin/laboratorio-paginas.php?slug=eventos" class="nav-link"><p>Editar Eventos</p></a></li>
                            <li class="nav-item"><a href="/admin/footer.php" class="nav-link<?= $is('footer') ? ' active' : '' ?>"><p>Rodape do Site</p></a></li>
                            <li class="nav-item"><a href="/admin/tema.php" class="nav-link<?= $is('tema') ? ' active' : '' ?>"><p>Tema e Cores</p></a></li>
                            <li class="nav-item"><a href="/admin/menu.php" class="nav-link<?= $is('menu') ? ' active' : '' ?>"><p>Menu Principal</p></a></li>
                        </ul>
                    </li>

                    <li class="nav-item"><a href="/admin/pessoal.php" class="nav-link<?= $is('pessoal') ? ' active' : '' ?>"><p>Equipe</p></a></li>

                    <?php if (admin_can('manage_users')): ?>
                        <li class="nav-item"><a href="/admin/users.php" class="nav-link<?= $is('users') ? ' active' : '' ?>"><p>Usuarios e Permissoes</p></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </aside>
    <?php
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

function ensure_content_carousel_images_table(): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS content_carousel_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                content_type ENUM('noticias','editais') NOT NULL,
                content_id INT NOT NULL,
                image_url VARCHAR(255) NOT NULL,
                caption VARCHAR(255) DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_content_carousel (content_type, content_id, sort_order, id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('Failed ensuring content_carousel_images table: ' . $e->getMessage());
    }
}
function content_table_by_type(string $type): string {
    return $type === 'editais' ? 'edital_items' : 'news_items';
}
function content_find_news_or_edital_by_slug(string $slug): ?array {
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }
    try {
        $stmt = db()->prepare("SELECT id, slug, title, summary, category, content, image FROM news_items WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            return ['item' => $row, 'content_type' => 'noticias', 'content_id' => (int)$row['id']];
        }
        $stmt = db()->prepare("SELECT id, slug, title, summary, category, content, image FROM edital_items WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            return ['item' => $row, 'content_type' => 'editais', 'content_id' => (int)$row['id']];
        }
    } catch (Throwable $e) {
        error_log('Failed finding content by slug: ' . $e->getMessage());
    }
    return null;
}
function content_carousel_images_get(string $type, int $contentId): array {
    ensure_content_carousel_images_table();
    if (!in_array($type, ['noticias', 'editais'], true) || $contentId <= 0) {
        return [];
    }
    try {
        $stmt = db()->prepare(
            "SELECT image_url, caption
             FROM content_carousel_images
             WHERE content_type = :content_type AND content_id = :content_id
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([':content_type' => $type, ':content_id' => $contentId]);
        return array_values(array_filter(array_map(static function (array $row): array {
            return [
                'image_url' => trim((string)($row['image_url'] ?? '')),
                'caption' => trim((string)($row['caption'] ?? '')),
            ];
        }, $stmt->fetchAll()), static fn(array $row): bool => $row['image_url'] !== ''));
    } catch (Throwable $e) {
        error_log('Failed loading content carousel images: ' . $e->getMessage());
        return [];
    }
}
function content_carousel_images_replace(string $type, int $contentId, array $slides): void {
    ensure_content_carousel_images_table();
    if (!in_array($type, ['noticias', 'editais'], true) || $contentId <= 0) {
        return;
    }
    $normalized = [];
    foreach ($slides as $idx => $slide) {
        if (!is_array($slide)) {
            continue;
        }
        $imageUrl = trim((string)($slide['image_url'] ?? ''));
        $caption = trim((string)($slide['caption'] ?? ''));
        if ($imageUrl === '') {
            continue;
        }
        $normalized[] = [
            'image_url' => normalize_menu_url($imageUrl, '/'),
            'caption' => $caption,
            'sort_order' => (int)($slide['sort_order'] ?? ($idx + 1)),
        ];
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare('DELETE FROM content_carousel_images WHERE content_type = :content_type AND content_id = :content_id');
        $del->execute([':content_type' => $type, ':content_id' => $contentId]);
        if (!empty($normalized)) {
            $ins = $pdo->prepare(
                'INSERT INTO content_carousel_images (content_type, content_id, image_url, caption, sort_order)
                 VALUES (:content_type, :content_id, :image_url, :caption, :sort_order)'
            );
            foreach ($normalized as $slide) {
                $ins->execute([
                    ':content_type' => $type,
                    ':content_id' => $contentId,
                    ':image_url' => $slide['image_url'],
                    ':caption' => $slide['caption'],
                    ':sort_order' => $slide['sort_order'],
                ]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
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
      ['slug'=>'defesas-monografia-2026-1','title'=>'Defesas de monografia 2026/1','summary'=>'Agenda de bancas de monografia do semestre.','category'=>'Defesas','content'=>'ConteÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºdo demonstrativo para defesas.','image'=>'/assets/cards/noticia-pesquisa.svg']
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
      ['slug'=>'vaga-estagio-web','title'=>'Vaga de estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gio em desenvolvimento web','summary'=>'Empresa parceira busca estudante com noÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes de PHP e banco de dados.','category'=>'Carreiras','content'=>'ConteÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºdo demonstrativo para estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gios e empregos.','image'=>'/assets/cards/noticia-portal.svg']
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
function ensure_people_items_scope_column(): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    try {
        db()->exec("ALTER TABLE people_items ADD COLUMN scope ENUM('principal','pos') NOT NULL DEFAULT 'principal' AFTER role_type");
    } catch (Throwable $e) {
        // Coluna ja existente ou banco ainda sem a tabela.
    }
}
function ensure_people_items_role_type_enum(): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;
    try {
        db()->exec("ALTER TABLE people_items MODIFY COLUMN role_type ENUM('docente','funcionario','estudante_graduacao','estudante_pos') NOT NULL DEFAULT 'docente'");
    } catch (Throwable $e) {
        // Banco ainda sem tabela ou alteracao nao necessaria.
    }
}
function people_role_type_options(): array {
    return [
        'docente' => 'Docente',
        'funcionario' => 'Funcionario',
        'estudante_graduacao' => 'Estudante da Graduacao',
        'estudante_pos' => 'Estudante da Pos',
    ];
}
function people_role_type_label(string $roleType): string {
    $options = people_role_type_options();
    return $options[$roleType] ?? 'Pessoa';
}
function fetch_people_items(string $type, string $scope = 'principal'): array {
    if (!array_key_exists($type, people_role_type_options())) {
        return [];
    }
    $scopeNorm = people_scope_normalize($scope);
    ensure_people_items_scope_column();
    ensure_people_items_role_type_enum();
    $sql = "SELECT slug, name, role_type, position, degree, website_url, lattes_url, email, phone, room, interests, bio, photo_url
            FROM people_items
            WHERE role_type = :role_type AND scope = :scope
            ORDER BY sort_order ASC, name ASC, id ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute([':role_type' => $type, ':scope' => $scopeNorm]);
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
    return person_photo_placeholder((string)($item['name'] ?? SITE_SIGLA));
}
function docentes(string $scope = 'principal'): array {
    try {
        $items = fetch_people_items('docente', $scope);
        if (!empty($items)) {
            return $items;
        }
    } catch (Throwable $e) {
        error_log('Failed loading docente profiles: ' . $e->getMessage());
    }
    return [
        ['name'=>'Ana Paula Ribeiro','position'=>'Professora Adjunta','degree'=>'Doutora em CiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia da ComputaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o','website_url'=>'','lattes_url'=>'','email'=>'ana.ribeiro@ufop.edu.br','phone'=>'(31) 3559-1601','room'=>'Instituto de CiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncias Exatas e BiolÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³gicas','interests'=>'Engenharia de software e sistemas distribuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­dos.','bio'=>'Atua em engenharia de software e sistemas distribuÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­dos.','photo_url'=>''],
        ['name'=>'Bruno Carvalho Mendes','position'=>'Professor Associado','degree'=>'Doutor em CiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia da ComputaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o','website_url'=>'','lattes_url'=>'','email'=>'bruno.mendes@ufop.edu.br','phone'=>'(31) 3559-1602','room'=>'Instituto de CiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncias Exatas e BiolÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³gicas','interests'=>'InteligÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia artificial e mineraÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o de dados.','bio'=>'Atua em inteligÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia artificial e mineraÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o de dados.','photo_url'=>''],
        ['name'=>'Camila Freitas Lopes','position'=>'Professora Adjunta','degree'=>'Doutora em ComputaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o','website_url'=>'','lattes_url'=>'','email'=>'camila.lopes@ufop.edu.br','phone'=>'(31) 3559-1603','room'=>'Instituto de CiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncias Exatas e BiolÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³gicas','interests'=>'ComputaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o grÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡fica e IHC.','bio'=>'Atua em computaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o grÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡fica e interaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o humano-computador.','photo_url'=>''],
    ];
}
function funcionarios(string $scope = 'principal'): array {
    try {
        $items = fetch_people_items('funcionario', $scope);
        if (!empty($items)) {
            return $items;
        }
    } catch (Throwable $e) {
        error_log('Failed loading funcionario profiles: ' . $e->getMessage());
    }
    return [
        ['name'=>'Mariana Souza Almeida','position'=>'SecretÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ria Administrativa','degree'=>'','website_url'=>'','lattes_url'=>'','email'=>'mariana.almeida@ufop.edu.br','phone'=>'(31) 3559-1692','room'=>'Instituto de CiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncias Exatas e BiolÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³gicas','interests'=>'Atendimento acadÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªmico e administrativo.','bio'=>'Atendimento acadÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªmico e administrativo do departamento.','photo_url'=>''],
        ['name'=>'Paulo Henrique Silva','position'=>'TÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â©cnico em TI','degree'=>'','website_url'=>'','lattes_url'=>'','email'=>'paulo.silva@ufop.edu.br','phone'=>'(31) 3559-1693','room'=>'Instituto de CiÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncias Exatas e BiolÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³gicas','interests'=>'Infraestrutura e suporte de laboratÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³rios.','bio'=>'Suporte de laboratÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³rios, sistemas e infraestrutura local.','photo_url'=>''],
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
        ['slug' => 'lab-inovacao', 'name' => 'Laboratorio de Inovacao', 'summary' => 'Pesquisa aplicada e prototipagem de solucoes tecnicas.', 'site_url' => ''],
        ['slug' => 'lab-dados', 'name' => 'Laboratorio de Dados', 'summary' => 'Analise de dados, estatistica aplicada e apoio a decisoes.', 'site_url' => ''],
        ['slug' => 'lab-sistemas', 'name' => 'Laboratorio de Sistemas', 'summary' => 'Arquiteturas, desenvolvimento de sistemas e infraestrutura.', 'site_url' => ''],
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
            'slug' => 'projeto-ensino-inovador',
            'title' => 'Ensino inovador e tecnologias educacionais',
            'project_type' => 'pesquisa',
            'summary' => 'Projeto focado em metodologias e ferramentas para melhoria do processo de ensino.',
            'site_url' => '',
            'coordinator' => 'Departamento',
        ],
        [
            'slug' => 'projeto-extensao-comunidade',
            'title' => 'Extensao e integracao com a comunidade',
            'project_type' => 'extensao',
            'summary' => 'Projeto de extensao com oficinas e atividades para fortalecer a relacao com a comunidade.',
            'site_url' => '',
            'coordinator' => 'Departamento',
        ],
    ];
}
function course_data(string $slug): array {
    $courses = [
        'ciencia-da-computacao' => ['name'=>'Curso de Graduacao 1','summary'=>'Descricao resumida do primeiro curso de graduacao do departamento.','content'=>'Este bloco deve apresentar objetivos, estrutura curricular, perfil de formacao e possibilidades de atuacao profissional.','modality'=>'Bacharelado','duration'=>'8 semestres','shift'=>'Integral'],
        'inteligencia-artificial' => ['name'=>'Curso de Graduacao 2','summary'=>'Descricao resumida do segundo curso de graduacao do departamento.','content'=>'Use este campo para descrever eixos formativos, componentes curriculares e diferenciais do curso.','modality'=>'Bacharelado','duration'=>'8 semestres','shift'=>'Integral'],
    ];
    return $courses[$slug] ?? ['name'=>'Curso','summary'=>'','content'=>'','modality'=>'','duration'=>'','shift'=>''];
}
function page_data(string $slug): array {
    $pages = [
      'quem-somos'=>['title'=>'Quem somos','summary'=>'Apresentacao institucional do departamento, sua trajetoria e suas areas de atuacao.','content'=>'Este departamento atua em ensino, pesquisa e extensao, oferecendo cursos e desenvolvendo acoes academicas para a comunidade.'],
      'comunicacao-logo'=>['title'=>'ComunicaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o e logo','summary'=>'Diretrizes para uso do nome, identidade visual e materiais institucionais.','content'=>'Esta pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gina pode concentrar versÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes do logotipo e padrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes de comunicaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o institucional do departamento.'],
      'localizacao'=>['title'=>'LocalizaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o','summary'=>'InformaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes de localizaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o fÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­sica, acesso e referÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia institucional.','content'=>'O departamento estÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ localizado no campus universitÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rio, com atendimento presencial em dias ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âºteis.'],
      'mapa-campus'=>['title'=>'Mapa do campus','summary'=>'Mapa de acesso e referÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncia espacial da unidade acadÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªmica.','content'=>'PÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gina preparada para receber mapa interativo ou orientaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes de deslocamento.'],
      'horarios-de-aula'=>['title'=>'HorÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios de Aula','summary'=>'Consulta organizada dos horÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios de aula por curso, perÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­odo ou turma.','content'=>'Esta pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gina concentra quadros de horÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios dos alunos, horÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios por docente ou planilhas por semestre letivo.'],
      'informacoes-uteis'=>['title'=>'InformaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes ÃƒÆ’Ã†â€™Ãƒâ€¦Ã‚Â¡teis','summary'=>'OrientaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes acadÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªmicas, formulÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios e instruÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes operacionais para estudantes.','content'=>'Inclua aqui calendÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡rios, orientaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes de matrÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­cula, aproveitamento de estudos, equivalÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªncias e monitorias.'],
      'monografias'=>['title'=>'Monografias','summary'=>'InformaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes sobre disciplinas de monografia, banca, documentaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o e cronogramas.','content'=>'Esta pÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gina centraliza regulamentos, modelos de documentos, agendas de defesas e orientaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes para discentes e orientadores.'],
      'pesquisa'=>['title'=>'Pesquisa','summary'=>'ApresentaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o de linhas de pesquisa, grupos, projetos e produÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o cientÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­fica.','content'=>'Esta seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o organiza laboratÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³rios, grupos de pesquisa, projetos financiados e oportunidades de iniciaÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o cientÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â­fica.'],
      'extensao'=>['title'=>'ExtensÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o','summary'=>'CatÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡logo de projetos e aÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes de extensÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o vinculados ao departamento.','content'=>'Esta seÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£o apresenta programas, projetos, oficinas, cursos e aÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â§ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âµes extensionistas.'],
      'cocic'=>['title'=>'Graduacao','summary'=>'Pagina da graduacao com apresentacao do curso, estrutura academica e informacoes uteis para alunos.','content'=>'A graduacao pode publicar aqui informacoes sobre matriz curricular, orientacoes academicas, documentos, calendario e comunicados aos estudantes.'],
    ];
    return $pages[$slug] ?? ['title'=>'PÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡gina','summary'=>'','content'=>''];
}





