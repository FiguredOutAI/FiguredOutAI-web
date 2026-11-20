<?php
/**
 * Health check for uptime monitoring (GitHub Actions "Uptime" workflow, UptimeRobot, etc.).
 * Reports only pass/fail per check, never paths, versions or settings.
 * 200 = everything a visitor needs works; 503 = something that breaks sign-ups is wrong.
 */
require __DIR__ . '/app/lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$probe = CONTENT . '/.health-' . bin2hex(random_bytes(4));
$storage = @file_put_contents($probe, '1') === 1;
if ($storage) @unlink($probe);
$free = @disk_free_space(CONTENT);

$checks = [
    'storage' => $storage,                                   // sign-ups, consent log and leads can be written
    'disk'    => $free === false || $free > 200 * 1024 * 1024, // at least 200 MB free
    'php'     => PHP_VERSION_ID >= 80200,
    'secret'  => (bool)cfg('cron_token'),
];
// Not fatal, but worth knowing: without a Resend key, welcome emails fall back to PHP mail().
$warn = ['email_provider' => trim((string)(settings()['resend_api_key'] ?? '')) !== ''];

$ok = !in_array(false, $checks, true);
http_response_code($ok ? 200 : 503);
echo json_encode(['ok' => $ok, 'checks' => $checks, 'info' => $warn, 'at' => gmdate('c')]);
