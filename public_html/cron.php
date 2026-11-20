<?php
/**
 * Optional hourly job. Announces scheduled posts to search engines (IndexNow) once they go live.
 * Hostinger hPanel → Advanced → Cron Jobs → Custom:  php /home/USER/public_html/cron.php
 * or call https://yourdomain.com/cron.php?token=YOUR_CRON_TOKEN
 */
require __DIR__ . '/app/lib.php';
if (PHP_SAPI !== 'cli' && !hash_equals((string)cfg('cron_token'), (string)($_GET['token'] ?? ''))) { http_response_code(403); exit('Forbidden'); }

$stateFile = CONTENT . '/indexnow_state.json';
$state = json_read($stateFile, []);
$new = [];
foreach (live_posts() as $p) {
    $sig = $p['slug'] . '@' . ($p['updated'] ?? $p['ts']);
    if (empty($state[$sig])) { $new[] = post_url($p); $state[$sig] = time(); }
}
if ($new) { $new[] = url('blog/'); $new[] = url('sitemap.xml'); indexnow_ping($new); }
json_write($stateFile, $state);
header('Content-Type: text/plain');
echo $new ? 'Pinged: ' . implode(', ', $new) : 'Nothing new.';
