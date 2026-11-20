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
// Privacy: emails that never finished step 2 (no consent given) are removed after 30 days.
require_once __DIR__ . '/app/waitlist.php';
$lockFh = fopen(CONTENT . '/waitlist.lock', 'c');
if ($lockFh && flock($lockFh, LOCK_EX)) {
    $all = waitlist_all();
    $keep = array_values(array_filter($all, fn($w) => ($w['stage'] ?? '') === 'joined' || strtotime((string)($w['at'] ?? 'now')) > time() - 30 * 86400));
    if (count($keep) !== count($all)) waitlist_save($keep);
    flock($lockFh, LOCK_UN);
}

// Daily snapshot of server data (waitlist, leads, consent log, settings, posts) one level above
// public_html, so no deploy, bad upload or folder rename can lose it. Keeps 14 days.
$bdir = dirname(ROOT) . '/content-backups';
$today = $bdir . '/' . date('Y-m-d');
if (!is_dir($today) && @mkdir($today . '/posts', 0700, true)) {
    foreach (glob(CONTENT . '/*.json*') ?: [] as $f) if (is_file($f)) @copy($f, $today . '/' . basename($f));
    foreach (glob(POSTS_DIR . '/*') ?: [] as $f) if (is_file($f)) @copy($f, $today . '/posts/' . basename($f));
    $cut = date('Y-m-d', strtotime('-14 days'));
    foreach (glob($bdir . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        if (basename($d) >= $cut) continue;
        foreach (array_merge(glob("$d/posts/*") ?: [], glob("$d/*") ?: []) as $f) if (is_file($f)) @unlink($f);
        @rmdir("$d/posts"); @rmdir($d);
    }
}

header('Content-Type: text/plain');
echo $new ? 'Pinged: ' . implode(', ', $new) : 'Nothing new.';
