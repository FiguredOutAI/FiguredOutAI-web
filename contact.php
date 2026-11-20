<?php
require __DIR__ . '/app/lib.php';

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
function reply(bool $ok, string $msg, bool $json): void {
    if ($json) { header('Content-Type: application/json'); echo json_encode(['ok' => $ok, 'error' => $ok ? null : $msg]); exit; }
    header('Location: /' . ($ok ? '?sent=1' : '?error=' . rawurlencode($msg)) . '#contact', true, 303); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /#contact'); exit; }

$name = trim(mb_substr((string)($_POST['name'] ?? ''), 0, 120));
$email = trim(mb_substr((string)($_POST['email'] ?? ''), 0, 160));
$company = trim(mb_substr((string)($_POST['company'] ?? ''), 0, 160));
$interest = trim(mb_substr((string)($_POST['interest'] ?? ''), 0, 120));
$message = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 5000));

// Spam checks: honeypot, too-fast submit, link flood, per-IP rate limit.
if (($_POST['website'] ?? '') !== '') reply(true, '', $wantsJson);
$t = (int)($_POST['t'] ?? 0);
if ($t && time() - $t < 3) reply(false, 'That was quick. Please try again.', $wantsJson);
if (preg_match_all('~https?://~i', $message) > 3) reply(false, 'Please include fewer links.', $wantsJson);
if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) reply(false, 'Please add your name, a valid email and a message.', $wantsJson);

$rlFile = CONTENT . '/contact_rate.json';
$rl = json_read($rlFile, []);
$k = hash('sha256', client_ip());
$rl[$k] = array_values(array_filter($rl[$k] ?? [], fn($x) => $x > time() - 3600));
if (count($rl[$k]) >= 5) reply(false, 'Too many messages from this connection. Please email us instead.', $wantsJson);
$rl[$k][] = time();
json_write($rlFile, $rl);

$lead = ['id' => bin2hex(random_bytes(6)), 'at' => date('c'), 'name' => $name, 'email' => $email, 'company' => $company, 'interest' => $interest, 'message' => $message, 'page' => $_SERVER['HTTP_REFERER'] ?? '', 'status' => 'new'];
$leads = json_read(CONTENT . '/leads.json', []);
array_unshift($leads, $lead);
json_write(CONTENT . '/leads.json', $leads);

$clean = fn($s) => str_replace(["\r", "\n"], ' ', $s);
$subject = '=?UTF-8?B?' . base64_encode('New enquiry: ' . $clean($interest) . ' · ' . $clean($name)) . '?=';
$body = "Name: $name\nEmail: $email\nCompany: $company\nInterest: $interest\n\n$message\n\n— Sent from " . cfg('base_url');
$headers = "From: FiguredOutAI Website <" . cfg('mail_from') . ">\r\nReply-To: " . $clean($email) . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
@mail(cfg('contact_email'), $subject, $body, $headers);

reply(true, '', $wantsJson);
