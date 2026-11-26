<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('CONTENT', ROOT . '/content');
define('POSTS_DIR', CONTENT . '/posts');
define('UPLOADS', ROOT . '/uploads');

/* ---------- safety check: a half-finished upload shows a clear message instead of a blank 500 ---------- */
(function () {
    $need = ['app/config.php', 'app/markdown.php', 'app/layout.php', 'assets/css/site.css', 'assets/js/site.js', 'index.php'];
    $missing = array_values(array_filter($need, fn($f) => !is_file(ROOT . '/' . $f)));
    if (!$missing) return;
    http_response_code(503);
    header('Retry-After: 600');
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><meta name="robots" content="noindex"><title>FiguredOutAI · back shortly</title>'
       . '<body style="background:#05070A;color:#EEF1F4;font:16px/1.6 system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0">'
       . '<div style="max-width:520px;padding:24px"><h1 style="font-weight:500">We will be back in a few minutes.</h1>'
       . '<p style="color:#8B95A3">The site is being updated. Email <a style="color:#22E3F2" href="mailto:hello@figuredoutai.com">hello@figuredoutai.com</a> if you need us now.</p>'
       . '<!-- missing files: ' . htmlspecialchars(implode(', ', $missing)) . ' --></div>';
    exit;
})();

$GLOBALS['CFG'] = require __DIR__ . '/config.php';
date_default_timezone_set(cfg('timezone'));
require_once __DIR__ . '/markdown.php';

foreach ([CONTENT, POSTS_DIR, UPLOADS] as $d) { if (!is_dir($d)) @mkdir($d, 0755, true); }
// Re-create folder protection if it is ever lost (e.g. a folder replaced during an upload).
$denyAll = "Require all denied\n";
$uploadRules = "Options -Indexes -ExecCGI\n<FilesMatch \"\\.(php\\d?|phtml|phar|pl|py|cgi|sh|html?|svg|js)$\">\n  Require all denied\n</FilesMatch>\n";
foreach ([__DIR__ . '/.htaccess' => $denyAll, CONTENT . '/.htaccess' => $denyAll, UPLOADS . '/.htaccess' => $uploadRules] as $f => $rule) {
    if (!is_file($f)) @file_put_contents($f, $rule);
}
unset($denyAll, $uploadRules);

// Secrets never live in git. The signing secret (waitlist refs, web cron URL) is generated once
// on the server and kept in content/secret.json, which is never deployed or committed.
if (!cfg('cron_token')) {
    $secFile = CONTENT . '/secret.json';
    $sec = json_read($secFile, []);
    if (empty($sec['key'])) { $sec = ['key' => bin2hex(random_bytes(24)), 'created' => date('c')]; json_write($secFile, $sec); }
    $GLOBALS['CFG']['cron_token'] = $sec['key'];
    unset($secFile, $sec);
}

/* ---------------- basics ---------------- */
function cfg(string $k, $default = null) { return $GLOBALS['CFG'][$k] ?? $default; }
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = '/'): string { return rtrim(cfg('base_url'), '/') . '/' . ltrim($path, '/'); }
function post_url(array $p): string { return url('blog/' . $p['slug']); }
function now(): int { return time(); }

function json_read(string $file, $default = []) {
    if (!is_file($file)) return $default;
    $d = json_decode((string)file_get_contents($file), true);
    return is_array($d) ? $d : $default;
}
function json_write(string $file, $data): void {
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    rename($tmp, $file);
}
function settings(): array {
    static $s = null;
    if ($s === null) $s = json_read(CONTENT . '/settings.json', []);
    return $s;
}
function save_settings(array $s): void { json_write(CONTENT . '/settings.json', $s); }

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    $s = trim((string)$s, '-');
    return substr($s, 0, 80) ?: 'post';
}
function valid_slug(string $s): bool { return (bool)preg_match('~^[a-z0-9]+(?:-[a-z0-9]+)*$~', $s) && strlen($s) <= 80; }

/* ---------------- posts ---------------- */
function post_file(string $slug): string {
    if (!valid_slug($slug)) throw new RuntimeException('Invalid slug');
    return POSTS_DIR . '/' . $slug . '.json';
}
function all_posts(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $out = [];
    foreach (glob(POSTS_DIR . '/*.json') ?: [] as $f) {
        $p = json_read($f, null);
        if (!$p || empty($p['slug'])) continue;
        $p += ['tags' => [], 'status' => 'draft', 'publish_at' => date('c'), 'updated' => null, 'cover' => '', 'cover_alt' => '', 'description' => '', 'focus_keyword' => '', 'author' => cfg('default_author'), 'canonical' => '', 'noindex' => false];
        $p['ts'] = strtotime($p['publish_at']) ?: 0;
        $out[] = $p;
    }
    usort($out, fn($a, $b) => $b['ts'] <=> $a['ts']);
    return $cache = $out;
}
function is_live(array $p): bool { return $p['status'] === 'published' && $p['ts'] <= now(); }
function live_posts(?string $tag = null): array {
    return array_values(array_filter(all_posts(), fn($p) => is_live($p) && ($tag === null || in_array($tag, array_map('tag_slug', $p['tags']), true))));
}
function get_post(string $slug): ?array {
    if (!valid_slug($slug)) return null;
    foreach (all_posts() as $p) if ($p['slug'] === $slug) return $p;
    return null;
}
function save_post(array $p): void {
    unset($p['ts']);
    json_write(post_file($p['slug']), $p);
}
function delete_post(string $slug): void { $f = post_file($slug); if (is_file($f)) unlink($f); }
function tag_slug(string $t): string { return slugify($t); }
function all_tags(): array {
    $t = [];
    foreach (live_posts() as $p) foreach ($p['tags'] as $tag) { $k = tag_slug($tag); $t[$k] = ['name' => $tag, 'count' => ($t[$k]['count'] ?? 0) + 1]; }
    uasort($t, fn($a, $b) => $b['count'] <=> $a['count']);
    return $t;
}
/**
 * FAQPage structured data from a post's "## FAQ" section ("### Question" followed by its answer).
 * Returns [] when a post has no FAQ, so it can be spread into the JSON-LD list.
 */
function post_faq_jsonld(string $md): array {
    if (!preg_match('~^##\s+(?:FAQ|FAQs|Frequently asked questions)\s*$(.*?)(?=^##\s|\z)~ims', $md, $m)) return [];
    preg_match_all('~^###\s+(.+?)\s*$\n+(.+?)(?=^###\s|\z)~ms', $m[1], $qs, PREG_SET_ORDER);
    $items = [];
    foreach ($qs as $q) {
        $a = trim(preg_replace('~\s+~', ' ', html_entity_decode(strip_tags(md_to_html(trim($q[2]))), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($a !== '') $items[] = ['@type' => 'Question', 'name' => trim($q[1]), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a]];
    }
    return $items ? [['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items]] : [];
}
function reading_time(string $md): int { return max(1, (int)ceil(str_word_count(strip_tags($md)) / 220)); }
function word_count(string $md): int { return str_word_count(strip_tags($md)); }
function excerpt(array $p, int $len = 160): string {
    if ($p['description']) return $p['description'];
    $t = trim(preg_replace('~\s+~', ' ', strip_tags(md_to_html($p['content']))));
    return mb_strlen($t) > $len ? mb_substr($t, 0, $len - 1) . '…' : $t;
}
function related_posts(array $post, int $n = 3): array {
    $mine = array_map('tag_slug', $post['tags']);
    $scored = [];
    foreach (live_posts() as $p) {
        if ($p['slug'] === $post['slug']) continue;
        $score = count(array_intersect($mine, array_map('tag_slug', $p['tags']))) * 10 + ($p['ts'] / 1e10);
        $scored[] = [$score, $p];
    }
    usort($scored, fn($a, $b) => $b[0] <=> $a[0]);
    return array_map(fn($x) => $x[1], array_slice($scored, 0, $n));
}
function fmt_date(int $ts): string { return date('j M Y', $ts); }

/* ---------------- sessions, auth, csrf ---------------- */
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('foai_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
}
function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    start_session();
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Session expired. Go back and try again.'); }
}
/**
 * Admin login details live in content/admin.json ({user, hash}).
 * Deleting that one file resets the login without touching other settings.
 * Older installs kept only a password hash in settings.json; that is still accepted
 * and moved over on the next successful login.
 */
function admin_creds(): array {
    $a = json_read(CONTENT . '/admin.json', []);
    if (empty($a['hash']) && !empty(settings()['admin_hash'])) $a = ['user' => '', 'hash' => settings()['admin_hash']];
    return $a;
}
function save_admin_creds(string $user, string $hash): void {
    json_write(CONTENT . '/admin.json', ['user' => $user, 'hash' => $hash, 'updated' => date('c')]);
    $s = settings();
    if (isset($s['admin_hash'])) { unset($s['admin_hash']); save_settings($s); }
}
function clean_username(string $u): string { return strtolower(trim($u)); }
function valid_username(string $u): bool { return (bool)preg_match('~^[a-z0-9._@-]{3,60}$~', $u); }
function admin_login(string $user): void {
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    $_SESSION['admin_user'] = $user;
    $_SESSION['ua'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
}
function is_admin(): bool { start_session(); return !empty($_SESSION['admin']) && ($_SESSION['ua'] ?? '') === hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''); }
function require_admin(): void { if (!is_admin()) { header('Location: /admin/'); exit; } }
function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
function login_blocked(): bool {
    $a = json_read(CONTENT . '/login_attempts.json', []);
    $k = hash('sha256', client_ip());
    $recent = array_filter($a[$k] ?? [], fn($t) => $t > now() - 900);
    return count($recent) >= 5;
}
function login_failed(): void {
    $f = CONTENT . '/login_attempts.json';
    $a = json_read($f, []);
    $k = hash('sha256', client_ip());
    $a[$k] = array_values(array_filter($a[$k] ?? [], fn($t) => $t > now() - 900));
    $a[$k][] = now();
    json_write($f, $a);
}

/* ---------------- uploads ---------------- */
function handle_upload(array $file): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
    if ($file['size'] > 5 * 1024 * 1024) throw new RuntimeException('Image must be under 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
    if (!$ext || !@getimagesize($file['tmp_name'])) throw new RuntimeException('Only JPG, PNG, WebP or GIF images.');
    $dir = UPLOADS . '/' . date('Y/m');
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(8));
    // Convert to WebP when GD supports it, for speed.
    if (function_exists('imagewebp') && $ext !== 'gif') {
        $src = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
        if ($src) {
            $w = imagesx($src); $h = imagesy($src);
            if ($w > 1600) { $nh = (int)round($h * 1600 / $w); $dst = imagecreatetruecolor(1600, $nh); imagealphablending($dst, false); imagesavealpha($dst, true); imagecopyresampled($dst, $src, 0, 0, 0, 0, 1600, $nh, $w, $h); imagedestroy($src); $src = $dst; }
            imagewebp($src, "$dir/$name.webp", 82);
            imagedestroy($src);
            return '/uploads/' . date('Y/m') . "/$name.webp";
        }
    }
    move_uploaded_file($file['tmp_name'], "$dir/$name.$ext");
    return '/uploads/' . date('Y/m') . "/$name.$ext";
}

/* ---------------- IndexNow ---------------- */
function indexnow_ping(array $urls): bool {
    $key = cfg('indexnow_key');
    if (!$key || !$urls || str_contains(cfg('base_url'), 'localhost') || getenv('FOAI_DEV') || PHP_SAPI === 'cli-server') return false;
    $host = parse_url(cfg('base_url'), PHP_URL_HOST);
    $body = json_encode(['host' => $host, 'key' => $key, 'keyLocation' => url($key . '.txt'), 'urlList' => array_values($urls)]);
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json; charset=utf-8\r\n", 'content' => $body, 'timeout' => 6, 'ignore_errors' => true]]);
    $r = @file_get_contents('https://api.indexnow.org/indexnow', false, $ctx);
    $ok = isset($http_response_header[0]) && preg_match('~\s(200|202)\s~', $http_response_header[0]);
    $log = json_read(CONTENT . '/indexnow_log.json', []);
    array_unshift($log, ['at' => date('c'), 'urls' => $urls, 'status' => $http_response_header[0] ?? 'no response']);
    json_write(CONTENT . '/indexnow_log.json', array_slice($log, 0, 50));
    return (bool)$ok;
}

/* ---------------- head / SEO helpers ---------------- */
/** Branded link-preview image for a page (rendered by og.php, cached in uploads/og). */
const OG_DESIGN = '1';
function og_sig(string $t, string $k): string {
    return substr(hash_hmac('sha256', OG_DESIGN . '|' . $t . '|' . $k, (string)cfg('cron_token') . 'og'), 0, 24);
}
function og_image_url(string $title, string $kicker = ''): string {
    return url('og.php') . '?' . http_build_query(['t' => $title, 'k' => $kicker, 's' => og_sig($title, $kicker)]);
}
/** Page title without the trailing " | FiguredOutAI" brand suffix. */
function og_title_from(string $title): string {
    $t = trim((string)preg_replace('/\s*[|–—-]\s*Figured[Oo]ut ?AI( Blog)?\s*$/u', '', $title));
    return $t !== '' ? $t : $title;
}
function head_tags(array $m): string {
    $s = settings();
    $title = $m['title'] ?? cfg('site_name');
    $desc = $m['description'] ?? '';
    $canon = $m['canonical'] ?? url($_SERVER['REQUEST_URI'] ?? '/');
    $ogt = $m['og_title'] ?? og_title_from($title);
    $img = $m['image'] ?? og_image_url($ogt, $m['og_kicker'] ?? '');
    if (str_starts_with($img, '/')) $img = url($img);
    $o = '<title>' . e($title) . "</title>\n";
    $o .= '<meta name="description" content="' . e($desc) . "\">\n";
    $o .= '<link rel="canonical" href="' . e($canon) . "\">\n";
    if (!empty($m['noindex'])) $o .= "<meta name=\"robots\" content=\"noindex,follow\">\n";
    else $o .= "<meta name=\"robots\" content=\"index,follow,max-image-preview:large\">\n";
    $o .= '<meta property="og:site_name" content="' . e(cfg('site_name')) . "\">\n";
    $o .= "<meta property=\"og:locale\" content=\"en_US\">\n";
    $o .= '<meta property="og:type" content="' . e($m['type'] ?? 'website') . "\">\n";
    $o .= '<meta property="og:title" content="' . e($title) . "\">\n";
    $o .= '<meta property="og:description" content="' . e($desc) . "\">\n";
    $o .= '<meta property="og:url" content="' . e($canon) . "\">\n";
    $o .= '<meta property="og:image" content="' . e($img) . "\">\n";
    if (empty($m['image'])) $o .= "<meta property=\"og:image:width\" content=\"1200\">\n<meta property=\"og:image:height\" content=\"630\">\n<meta property=\"og:image:type\" content=\"image/png\">\n";
    $o .= '<meta property="og:image:alt" content="' . e($ogt) . "\">\n";
    $o .= "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    $o .= '<meta name="twitter:title" content="' . e($title) . "\">\n";
    $o .= '<meta name="twitter:description" content="' . e($desc) . "\">\n";
    $o .= '<meta name="twitter:image" content="' . e($img) . "\">\n";
    if (!empty($m['published'])) $o .= '<meta property="article:published_time" content="' . e($m['published']) . "\">\n";
    if (!empty($m['modified'])) $o .= '<meta property="article:modified_time" content="' . e($m['modified']) . "\">\n";
    $o .= '<link rel="alternate" type="application/rss+xml" title="' . e(cfg('site_name')) . ' Blog" href="' . e(url('feed.xml')) . "\">\n";
    if (!empty($s['gsc_verification'])) $o .= '<meta name="google-site-verification" content="' . e($s['gsc_verification']) . "\">\n";
    if (!empty($s['bing_verification'])) $o .= '<meta name="msvalidate.01" content="' . e($s['bing_verification']) . "\">\n";
    $o .= $m['extra'] ?? '';
    foreach ($m['jsonld'] ?? [] as $ld) $o .= '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . "</script>\n";
    return $o;
}
/* ---------- tracking + consent ---------- */
const CONSENT_VERSION = 1; // bump to ask every visitor again (e.g. after adding a new ad platform)

/** Validated tracking IDs from Admin → Settings. Empty values are dropped. */
function tracking_ids(): array {
    $s = settings();
    $rules = [
        'ga4' => ['ga4_id', '~^G-[A-Z0-9]{4,20}$~'],
        'ads' => ['gads_id', '~^AW-\d{6,15}$~'],
        'adsLead' => ['gads_lead_label', '~^[A-Za-z0-9_-]{6,40}$~'],
        'meta' => ['meta_pixel_id', '~^\d{10,20}$~'],
        'li' => ['linkedin_partner_id', '~^\d{4,12}$~'],
        'clarity' => ['clarity_id', '~^[a-z0-9]{6,20}$~'],
    ];
    $out = [];
    foreach ($rules as $k => [$key, $re]) { $v = (string)($s[$key] ?? ''); if (preg_match($re, $v)) $out[$k] = $v; }
    return $out;
}

/**
 * Head snippet: Google Consent Mode v2 defaults (everything denied) and the tag IDs.
 * Nothing is loaded here. assets/js/consent.js loads each tag only after the visitor opts in.
 */
function tracking_head(): string {
    $cfg = json_encode(['ids' => (object)tracking_ids(), 'v' => CONSENT_VERSION], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    return "<script>window.__TRACK=$cfg;window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}"
        . "gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',functionality_storage:'granted',security_storage:'granted',wait_for_update:500});"
        . "gtag('set','ads_data_redaction',true);gtag('set','url_passthrough',true);</script>\n";
}
/* ---------- social profiles ---------- */
/** Built-in defaults. Anything saved in Admin → Settings overrides these. */
const DEFAULT_LINKS = [
    'company' => ['linkedin' => 'https://www.linkedin.com/company/figuredout-ai/', 'instagram' => 'https://www.instagram.com/figuredoutai/', 'facebook' => 'https://www.facebook.com/profile.php?id=61596320947417'],
    'tanishk' => ['linkedin' => 'https://www.linkedin.com/in/tanishksinghal/', 'instagram' => 'https://www.instagram.com/tanishk.figureitout/', 'x' => 'https://x.com/tanishk_ai'],
    'yash'    => [],
];
function company_links(): array {
    $saved = array_filter(settings()['social'] ?? []);
    return array_filter(array_merge(DEFAULT_LINKS['company'], $saved));
}
function founder_links(string $who): array {
    $links = DEFAULT_LINKS[$who] ?? [];
    $li = settings()['founder_linkedin_' . $who] ?? '';
    if ($li) $links['linkedin'] = $li;
    return array_filter($links);
}
function social_icon(string $k): string {
    $i = [
        'linkedin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4.98 3.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0zM.5 8h4v16h-4V8zm7 0h3.8v2.2h.05c.53-1 1.84-2.2 3.8-2.2 4.06 0 4.85 2.67 4.85 6.14V24h-4v-8.1c0-1.93-.03-4.42-2.7-4.42-2.7 0-3.1 2.1-3.1 4.28V24h-4V8z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.6" r="1.1" fill="currentColor" stroke="none"/></svg>',
        'x' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.24 2.25h3.31l-7.23 8.26 8.5 11.24h-6.65l-5.21-6.82-5.97 6.82H1.68l7.73-8.84L1.25 2.25H8.1l4.71 6.23 5.43-6.23zm-1.16 17.52h1.83L7.08 4.13H5.12l11.96 15.64z"/></svg>',
        'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.95.93-1.95 1.88v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>',
        'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"/></svg>',
    ];
    return $i[$k] ?? '';
}
/** Round icon buttons. $who is used in the accessible label, e.g. "Tanishk Singhal on LinkedIn". */
function social_buttons(array $links, string $who): string {
    $names = ['linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'x' => 'X', 'facebook' => 'Facebook', 'youtube' => 'YouTube'];
    $o = '';
    foreach (['linkedin', 'x', 'instagram', 'facebook', 'youtube'] as $k) {
        if (empty($links[$k])) continue;
        $label = $who . ' on ' . $names[$k];
        $o .= '<a href="' . e($links[$k]) . '" target="_blank" rel="noopener me" aria-label="' . e($label) . '" title="' . e($label) . '">' . social_icon($k) . '</a>';
    }
    return $o ? '<div class="soc">' . $o . '</div>' : '';
}

/** Every spelling people use for the company. Helps Google treat them as one brand. */
/** Directory profiles that describe the company. Structured data only, not shown as buttons. */
const DIRECTORY_PROFILES = ['https://www.crunchbase.com/organization/figuredoutai', 'https://github.com/FiguredOutAI'];

const BRAND_NAMES = ['FiguredoutAI', 'Figured Out AI', 'FiguredOut AI', 'Figuredout AI', 'FiguredoutAI LLP', 'figuredoutai.com'];

function org_jsonld(): array {
    $social = array_values(array_unique(array_merge(array_values(company_links()), DIRECTORY_PROFILES)));
    return ['@context' => 'https://schema.org', '@type' => 'Organization', '@id' => url('/#organization'),
        'name' => 'FiguredOutAI', 'alternateName' => BRAND_NAMES, 'legalName' => 'FiguredoutAI LLP', 'url' => url('/'),
        'logo' => ['@type' => 'ImageObject', 'url' => url('assets/img/icon-512.png'), 'width' => 512, 'height' => 512],
        'image' => url('assets/img/og-image.jpg'), 'email' => cfg('contact_email'), 'telephone' => '+91-8107658699',
        'description' => 'FiguredOutAI builds an AI inbox for WhatsApp, Instagram and Messenger that handles customer chats on its own, and designs, builds and runs custom AI agents, workflow automation and software for businesses worldwide.',
        'slogan' => 'An AI inbox that handles itself.',
        'identifier' => ['@type' => 'PropertyValue', 'propertyID' => 'LLPIN', 'value' => 'ACZ-0822'],
        'address' => ['@type' => 'PostalAddress', 'addressCountry' => 'IN'],
        'areaServed' => 'Worldwide',
        'knowsAbout' => ['AI agents', 'Multi-agent systems', 'Workflow automation', 'AI infrastructure', 'LLMOps', 'Custom software', 'Conversational AI', 'AI inbox', 'WhatsApp automation', 'Instagram DM automation'],
        'founder' => [['@type' => 'Person', '@id' => url('about#tanishk-singhal'), 'name' => 'Tanishk Singhal', 'jobTitle' => 'Co-founder & CEO'] + (founder_links('tanishk') ? ['sameAs' => array_values(founder_links('tanishk'))] : []),
                      ['@type' => 'Person', '@id' => url('about#yashvardhan-awasthi'), 'name' => 'Yashvardhan Awasthi', 'jobTitle' => 'Co-founder & CTO']],
        'contactPoint' => [['@type' => 'ContactPoint', 'contactType' => 'sales', 'email' => cfg('contact_email'), 'url' => url('/#contact'), 'availableLanguage' => ['English', 'Hindi']]],
    ] + ($social ? ['sameAs' => $social] : []);
}
function website_jsonld(): array {
    return ['@context' => 'https://schema.org', '@type' => 'WebSite', '@id' => url('/#website'), 'name' => 'FiguredOutAI',
        'alternateName' => BRAND_NAMES, 'url' => url('/'), 'publisher' => ['@id' => url('/#organization')], 'inLanguage' => 'en'];
}
