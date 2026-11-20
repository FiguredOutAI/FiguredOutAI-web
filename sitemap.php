<?php
require __DIR__ . '/app/lib.php';
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');
$posts = live_posts();
$latest = $posts ? max(array_map(fn($p) => $p['updated'] ? strtotime($p['updated']) : $p['ts'], $posts)) : filemtime(__DIR__ . '/index.php');
$u = function (string $loc, int $ts, string $freq, string $prio, ?string $img = null) {
    echo "  <url><loc>" . e($loc) . "</loc><lastmod>" . date('c', $ts) . "</lastmod><changefreq>$freq</changefreq><priority>$prio</priority>";
    if ($img) echo "<image:image><image:loc>" . e($img) . "</image:loc></image:image>";
    echo "</url>\n";
};
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
$u(url('/'), max($latest, filemtime(__DIR__ . '/index.php')), 'weekly', '1.0', url('assets/img/og-image.jpg'));
$u(url('blog/'), $latest, 'daily', '0.9');
$u(url('about'), filemtime(__DIR__ . '/about.php'), 'monthly', '0.8');
foreach ($posts as $p) {
    if (!empty($p['noindex']) || ($p['canonical'] && $p['canonical'] !== post_url($p))) continue;
    $img = $p['cover'] ? (str_starts_with($p['cover'], '/') ? url($p['cover']) : $p['cover']) : null;
    $u(post_url($p), $p['updated'] ? strtotime($p['updated']) : $p['ts'], 'monthly', '0.8', $img);
}
foreach (all_tags() as $slug => $t) $u(url("blog/tag/$slug/"), $latest, 'weekly', '0.5');
$u(url('privacy'), filemtime(__DIR__ . '/privacy.php'), 'yearly', '0.2');
$u(url('terms'), filemtime(__DIR__ . '/terms.php'), 'yearly', '0.2');
echo "</urlset>\n";
