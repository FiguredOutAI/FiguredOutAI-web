<?php
/**
 * Records cookie-consent choices as proof of consent (GDPR Art. 7(1), DPDP Act 2023).
 * Stores: random visitor consent ID, time, choice, policy version, method. No IP address, no name.
 */
require __DIR__ . '/app/lib.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$raw = file_get_contents('php://input', false, null, 0, 600);
$d = json_decode((string)$raw, true);
if (!is_array($d) || !preg_match('~^[A-Za-z0-9-]{8,64}$~', (string)($d['id'] ?? ''))) { http_response_code(400); exit; }
$method = preg_match('~^[a-z_]{3,30}$~', (string)($d['method'] ?? '')) ? $d['method'] : 'unknown';
$row = ['at' => date('c'), 'id' => $d['id'], 'analytics' => !empty($d['a']), 'marketing' => !empty($d['m']), 'v' => (int)($d['v'] ?? 0), 'method' => $method];

$file = CONTENT . '/consent_log.jsonl';
if (is_file($file) && filesize($file) > 5 * 1024 * 1024) rename($file, CONTENT . '/consent_log-' . date('Ymd-His') . '.jsonl');
file_put_contents($file, json_encode($row, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
http_response_code(204);
