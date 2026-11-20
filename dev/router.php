<?php
// Local testing only (php -S localhost:8080 -t public_html dev/router.php).
// Mirrors the rewrite rules in public_html/.htaccess. Not uploaded to Hostinger.
$root = realpath(__DIR__ . '/../public_html');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$go = function (string $file, array $get = []) use ($root) {
    $_GET = $get + $_GET;
    $_SERVER['SCRIPT_FILENAME'] = "$root/$file";
    chdir(dirname("$root/$file"));
    require "$root/$file";
    return true;
};
if (preg_match('~^/(app|content)(/|$)|^/admin/_|/\.(?!well-known/)~', $path)) { http_response_code(403); exit('Forbidden'); }
if (preg_match('~\.(json|md|log|tmp)$~', $path)) { http_response_code(403); exit('Forbidden'); }
$old = ['~^/services/?$~' => '/#capabilities', '~^/industries(/.*)?$~i' => '/#industries', '~^/contact/?$~' => '/#contact',
        '~^/(reference-build|decision-layer)/?$~' => '/#platform', '~^/why-us/?$~' => '/#principles', '~^/how-it-works/?$~' => '/#process'];
foreach ($old as $re => $to) if (preg_match($re, $path)) { header('Location: ' . $to, true, 301); exit; }
if ($path === '/sitemap.xml') return $go('sitemap.php');
if (preg_match('~^/(blog/)?feed\.xml$~', $path)) return $go('feed.php');
if (preg_match('~^/(privacy|terms|about|waitlist)/?$~', $path, $m)) return $go($m[1] . '.php');
if (preg_match('~^/lp/([a-z0-9-]+)/?$~', $path, $m) && is_file("$root/lp/{$m[1]}.php")) return $go("lp/{$m[1]}.php");
if ($path === '/blog') { header('Location: /blog/', true, 301); exit; }
if (preg_match('~^/blog/page/(\d+)/?$~', $path, $m)) return $go('blog/index.php', ['page' => $m[1]]);
if (preg_match('~^/blog/tag/([a-z0-9-]+)/?$~', $path, $m)) return $go('blog/index.php', ['tag' => $m[1]]);
if (preg_match('~^/blog/tag/([a-z0-9-]+)/page/(\d+)/?$~', $path, $m)) return $go('blog/index.php', ['tag' => $m[1], 'page' => $m[2]]);
if (preg_match('~^/blog/([a-z0-9-]+)/?$~', $path, $m) && !is_file($root . $path)) return $go('blog/post.php', ['slug' => $m[1]]);
if (is_file($root . $path)) return false;                       // static file or .php
if (is_dir($root . $path) && is_file($root . rtrim($path, '/') . '/index.php')) return $go(ltrim(rtrim($path, '/') . '/index.php', '/'));
http_response_code(404);
return $go('404.php');
