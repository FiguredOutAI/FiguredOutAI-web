<?php
/**
 * Waitlist sign-up endpoint (JSON).
 *  step=start   email (or a Google credential) → saves a "partial" entry, returns a signed ref
 *  step=finish  ref + name/business/phone/channels/consent → marks it "joined", sends the welcome email
 */
require __DIR__ . '/app/lib.php';
require __DIR__ . '/app/waitlist.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
function out(array $d, int $code = 200): void { http_response_code($code); echo json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit; }
function fail(string $msg, int $code = 400): void { out(['ok' => false, 'error' => $msg], $code); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST only.', 405);
$str = fn($k, $n) => trim(mb_substr((string)($_POST[$k] ?? ''), 0, $n));
$step = $str('step', 10);

// Bots: honeypot and too-fast submits.
if ($str('website', 200) !== '') out(['ok' => true, 'ref' => '', 'stage' => 'joined']);
$t = (int)($_POST['t'] ?? 0);
if ($step === 'start' && $t && time() - $t < 2) fail('That was fast. Suspiciously fast. Try again?');

// Rate limit: 20 requests per visitor per hour. Hostinger's CDN can sit in front of PHP,
// so use the forwarded client address when there is one (otherwise every visitor shares the CDN's IP).
$fwd = trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0]);
$visitor = filter_var($fwd, FILTER_VALIDATE_IP) ? $fwd : client_ip();
$rlFile = CONTENT . '/waitlist_rate.json';
$rl = json_read($rlFile, []);
$k = hash('sha256', $visitor);
foreach ($rl as $kk => $times) { $rl[$kk] = array_values(array_filter($times, fn($x) => $x > time() - 3600)); if (!$rl[$kk]) unset($rl[$kk]); }
if (count($rl[$k] ?? []) >= 20) fail('Easy there. Try again in a bit.', 429);
$rl[$k][] = time();
json_write($rlFile, $rl);

// One writer at a time, so two people signing up in the same second don't overwrite each other.
$lockFh = fopen(CONTENT . '/waitlist.lock', 'c');
$lock = fn() => flock($lockFh, LOCK_EX);
$unlock = fn() => flock($lockFh, LOCK_UN);

if ($step === 'start') {
    $method = 'email'; $name = ''; $picture = '';
    if (($cred = (string)($_POST['credential'] ?? '')) !== '') {
        $g = google_verify($cred);
        if (!$g) fail("Google sign-in didn't go through. Use your email instead?");
        [$email, $name, $picture, $method] = [$g['email'], $g['name'], $g['picture'], 'google'];
    } else {
        $email = strtolower($str('email', 160));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('That email looks made up. Try again?');
    }
    $lock();
    $all = waitlist_all();
    $utm = [];
    foreach (['source', 'medium', 'campaign', 'content', 'term'] as $u) { $v = $str("utm_$u", 120); if ($v !== '') $utm[$u] = $v; }

    $i = waitlist_find($all, 'email', $email);
    if ($i !== null) {
        if ($method === 'google') { $all[$i]['method'] = 'google'; if (!$all[$i]['name'] && $name) $all[$i]['name'] = $name; }
        waitlist_save($all);
        $w = $all[$i];
        // Already joined: say so, but hand out no ref, so nobody can edit someone else's details by typing their email.
        if ($w['stage'] === 'joined') out(['ok' => true, 'ref' => '', 'stage' => 'joined', 'already' => true]);
        out(['ok' => true, 'ref' => waitlist_ref($w['id']), 'stage' => $w['stage'], 'name' => $w['name'], 'already' => false]);
    }
    $w = ['id' => bin2hex(random_bytes(6)), 'at' => date('c'), 'joined_at' => null, 'stage' => 'partial', 'status' => 'waiting',
        'email' => $email, 'name' => $name, 'company' => '', 'phone' => '', 'channels' => [], 'consent' => false, 'method' => $method, 'picture' => $picture,
        'utm' => $utm, 'landing' => $str('landing', 300), 'referrer' => $str('referrer', 300), 'page' => $str('page', 300), 'welcome_sent' => false];
    array_unshift($all, $w);
    waitlist_save($all);
    out(['ok' => true, 'ref' => waitlist_ref($w['id']), 'stage' => 'partial', 'name' => $name, 'already' => false]);
}

if ($step === 'finish') {
    $lock();
    $all = waitlist_all();
    $id = waitlist_ref_id($str('ref', 60));
    $i = $id ? waitlist_find($all, 'id', $id) : null;
    if ($i === null) fail('We lost track of you. Refresh and try once more?');
    $w = $all[$i];

    $name = $str('name', 120) ?: $w['name'];
    $phone = preg_replace('~[^\d+]~', '', $str('phone', 30));
    if ($name === '') fail("What should we call you?");
    if ($phone !== '' && !preg_match('~^\+?\d{7,15}$~', $phone)) fail("That phone number doesn't look right. Add the country code, or leave it empty.");
    if (empty($_POST['consent'])) fail("Tick the box so we're allowed to tell you when you're in.");
    $channels = array_values(array_intersect(array_keys(WAITLIST_CHANNELS), (array)($_POST['channels'] ?? [])));

    $first = $w['stage'] !== 'joined';
    $w = array_merge($w, ['name' => $name, 'company' => $str('company', 160), 'phone' => $phone, 'channels' => $channels, 'consent' => true,
        'consent_at' => date('c'), 'consent_text' => 'Keep me posted about my access and launch updates by email or WhatsApp.', 'stage' => 'joined', 'joined_at' => $w['joined_at'] ?: date('c')]);
    $all[$i] = $w;
    waitlist_save($all);
    $total = waitlist_joined_count($all);
    $unlock();

    // Answer the visitor first, then send the emails, so a slow mail server never keeps them waiting.
    $body = json_encode(['ok' => true, 'stage' => 'joined', 'count' => waitlist_display_count(), 'first' => $first], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    header('Content-Length: ' . strlen($body));
    header('Connection: close');
    echo $body;
    if (function_exists('litespeed_finish_request')) litespeed_finish_request();   // Hostinger runs LiteSpeed
    elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    else { @ob_end_flush(); flush(); }
    ignore_user_abort(true);

    if ($first && !$w['welcome_sent']) {
        [$sub, $html, $text] = welcome_email($w);
        if (send_mail($w['email'], $sub, $html, $text)) {
            $lock(); $all = waitlist_all(); $j = waitlist_find($all, 'id', $w['id']);
            if ($j !== null) { $all[$j]['welcome_sent'] = date('c'); waitlist_save($all); }
            $unlock();
        }
        notify_team($w, $total);
    }
    exit;
}

fail('Unknown step.');
