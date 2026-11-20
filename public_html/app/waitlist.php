<?php
/** Waitlist: storage, counter, Google sign-in check and emails. Data lives in content/waitlist.json. */

const WAITLIST_FILE = CONTENT . '/waitlist.json';
const WAITLIST_CHANNELS = ['whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'messenger' => 'Messenger'];

function waitlist_all(): array { return json_read(WAITLIST_FILE, []); }
function waitlist_save(array $all): void { json_write(WAITLIST_FILE, $all); }

/** People who finished signing up (step 2). Step-1-only emails are kept as "partial" but not counted. */
function waitlist_joined_count(?array $all = null): int {
    return count(array_filter($all ?? waitlist_all(), fn($w) => ($w['stage'] ?? '') === 'joined'));
}

/**
 * Number shown on the site, or null to hide it.
 * Admin → Settings: mode hidden | real | offset, plus a minimum below which the number stays hidden.
 */
function waitlist_display_count(): ?int {
    $s = settings();
    $mode = $s['wl_counter_mode'] ?? 'hidden';
    if ($mode === 'hidden') return null;
    $n = waitlist_joined_count() + ($mode === 'offset' ? max(0, (int)($s['wl_counter_offset'] ?? 0)) : 0);
    return $n >= max(0, (int)($s['wl_counter_min'] ?? 0)) ? $n : null;
}

/** Signed reference so step 2 can only update the entry created in step 1. */
function waitlist_ref(string $id): string { return $id . '.' . substr(hash_hmac('sha256', $id, (string)cfg('cron_token') . 'wl'), 0, 20); }
function waitlist_ref_id(string $ref): ?string {
    [$id] = explode('.', $ref . '.', 2);
    return preg_match('~^[a-f0-9]{12}$~', $id) && hash_equals(waitlist_ref($id), $ref) ? $id : null;
}

function waitlist_find(array $all, string $key, string $val): ?int {
    foreach ($all as $i => $w) if (strcasecmp((string)($w[$key] ?? ''), $val) === 0) return $i;
    return null;
}

/* ---------- outbound HTTP (curl, with a stream fallback) ---------- */
function http_json(string $method, string $url, ?array $body = null, array $headers = []): array {
    $payload = $body === null ? null : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($payload !== null) $headers[] = 'Content-Type: application/json';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_HTTPHEADER => $headers]);
        if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $res = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'content' => $payload ?? '', 'timeout' => 8, 'ignore_errors' => true]]);
        $res = @file_get_contents($url, false, $ctx);
        preg_match('~\s(\d{3})\s~', $http_response_header[0] ?? '', $m);
        $code = (int)($m[1] ?? 0);
    }
    return [$code, is_string($res) ? (json_decode($res, true) ?: []) : []];
}

/* ---------- Google sign-in ---------- */
function google_client_id(): string {
    $id = trim((string)(settings()['google_client_id'] ?? ''));
    return preg_match('~^[0-9]+-[a-z0-9]+\.apps\.googleusercontent\.com$~', $id) ? $id : '';
}
/** Verifies a Google ID token with Google and returns [email, name, picture] or null. */
function google_verify(string $credential): ?array {
    $cid = google_client_id();
    if (!$cid || !preg_match('~^[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+$~', $credential)) return null;
    [$code, $t] = http_json('GET', 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($credential));
    if ($code !== 200) return null;
    $okIss = in_array($t['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
    $verified = ($t['email_verified'] ?? '') === 'true' || ($t['email_verified'] ?? false) === true;
    if (($t['aud'] ?? '') !== $cid || !$okIss || !$verified || (int)($t['exp'] ?? 0) < time()) return null;
    return ['email' => strtolower((string)$t['email']), 'name' => mb_substr(trim((string)($t['name'] ?? '')), 0, 120), 'picture' => (string)($t['picture'] ?? '')];
}

/* ---------- email ---------- */
function resend_key(): string { return trim((string)(settings()['resend_api_key'] ?? '')); }
function mail_sender(): string {
    $s = settings();
    $name = trim((string)($s['wl_from_name'] ?? '')) ?: 'FiguredOutAI';
    $addr = filter_var($s['wl_from_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: cfg('mail_from');
    return str_replace(['"', "\r", "\n"], '', $name) . ' <' . $addr . '>';
}
/** Sends through Resend when a key is set (good deliverability), otherwise PHP mail(). Returns true on success. */
function send_mail(string $to, string $subject, string $html, string $text, ?string $replyTo = null, array $headers = []): bool {
    $key = resend_key();
    if ($key) {
        $body = ['from' => mail_sender(), 'to' => [$to], 'subject' => $subject, 'html' => $html, 'text' => $text, 'reply_to' => $replyTo ?: cfg('contact_email')];
        if ($headers) $body['headers'] = $headers;
        [$code, $res] = http_json('POST', 'https://api.resend.com/emails', $body, ['Authorization: Bearer ' . $key]);
        if ($code >= 200 && $code < 300) return true;
        error_log('Resend failed (' . $code . '): ' . json_encode($res));
        return false;
    }
    $clean = fn($s) => str_replace(["\r", "\n"], ' ', $s);
    $extra = '';
    foreach ($headers as $k => $v) $extra .= $clean($k) . ': ' . $clean($v) . "\r\n";
    $hdr = 'From: ' . mail_sender() . "\r\nReply-To: " . $clean($replyTo ?: cfg('contact_email')) . "\r\n" . $extra . "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $text, $hdr);
}

function welcome_email(array $w): array {
    require_once __DIR__ . '/marketing.php';
    $first = trim(explode(' ', (string)($w['name'] ?? ''))[0]) ?: 'there';
    $email = (string)($w['email'] ?? cfg('contact_email'));
    $invite = url('waitlist') . '?utm_source=referral&utm_medium=email&utm_campaign=welcome';
    $unsub = unsub_url($email);
    $subject = "You're in. Your inbox is about to chill out.";
    $points = [
        ['Replies for you', 'WhatsApp, Instagram and Messenger answered in seconds, in your tone, even at 2 AM.'],
        ['Spots the buyers', 'Every chat gets a Hot, Warm or Cold score, so "price?" stops burying the 40-unit order.'],
        ['Routes the rest', 'Sales to sales, support to support, the weird ones to you. Every AI decision is logged.'],
    ];
    $social = array_intersect_key(company_links(), array_flip(['instagram', 'linkedin', 'x', 'youtube']));

    $text = "Hey $first,\n\nYou're on the FiguredOutAI waitlist. Your DMs are about to get a lot less needy.\n\n"
        . "What you're getting:\n" . implode("\n", array_map(fn($p) => "- {$p[0]}: {$p[1]}", $points)) . "\n\n"
        . "What happens next: we open access in batches and email you the moment it's your turn.\n\n"
        . "Know a founder drowning in DMs? Send them the waitlist: $invite\n\n"
        . "One favour: hit reply and tell us what's breaking in your inbox right now. We read every reply.\n\n"
        . "Tanishk\nCo-founder, FiguredOutAI\n" . url('/') . "\n\n"
        . ($social ? 'Follow the build: ' . implode(' · ', $social) . "\n\n" : '')
        . "Don't want these emails? Unsubscribe: $unsub\n";

    $p = 'margin:0 0 16px;font-size:16px;line-height:1.6;color:#1f2933';
    $rows = '';
    foreach ($points as [$h, $d]) $rows .= '<tr><td style="padding:0 0 14px"><div style="font-size:15px;font-weight:600;color:#05070A">' . e($h) . '</div><div style="font-size:15px;line-height:1.55;color:#4b5565">' . e($d) . '</div></td></tr>';
    $soc = '';
    foreach ($social as $k => $u) $soc .= '<a href="' . e($u) . '" style="color:#0E8C99;text-decoration:none;margin-right:14px">' . e(['linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'x' => 'X', 'youtube' => 'YouTube'][$k] ?? ucfirst($k)) . '</a>';
    $html = '<!doctype html><html><body style="margin:0;background:#f4f6f8;padding:32px 16px;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:540px;background:#ffffff;border-radius:16px;padding:36px 32px"><tr><td>'
        . '<div style="font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:#0E8C99;margin-bottom:20px">FiguredOutAI · Waitlist</div>'
        . '<h1 style="margin:0 0 16px;font-size:26px;line-height:1.2;color:#05070A;font-weight:600">You\'re in, ' . e($first) . '.</h1>'
        . '<p style="' . $p . '">Your DMs are about to get a lot less needy. Here is what you are getting:</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px;border-left:3px solid #22E3F2;padding-left:14px">' . $rows . '</table>'
        . '<p style="' . $p . '"><strong>What happens next</strong><br>We open access in batches and email you the moment it\'s your turn.</p>'
        . '<p style="' . $p . '"><strong>Know a founder drowning in DMs?</strong> Send them the waitlist.</p>'
        . '<p style="margin:0 0 24px"><a href="' . e($invite) . '" style="display:inline-block;background:#05070A;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;padding:12px 20px;border-radius:10px">Share the waitlist</a></p>'
        . '<p style="' . $p . '">One favour: hit reply and tell us what\'s breaking in your inbox right now. We read every reply, and it shapes what ships first.</p>'
        . '<p style="margin:24px 0 0;font-size:16px;line-height:1.5;color:#1f2933">Tanishk<br><span style="color:#6b7785">Co-founder, FiguredOutAI</span></p>'
        . ($soc ? '<p style="margin:20px 0 0;font-size:14px;color:#6b7785">Follow the build: ' . $soc . '</p>' : '')
        . '</td></tr></table>'
        . '<p style="font-size:12px;line-height:1.5;color:#8b95a3;margin:18px 0 0;max-width:540px">You got this because you joined the waitlist at <a href="' . e(url('/')) . '" style="color:#8b95a3">figuredoutai.com</a>. FiguredoutAI LLP, India. '
        . '<a href="' . e($unsub) . '" style="color:#8b95a3">Unsubscribe</a></p>'
        . '</td></tr></table></body></html>';
    return [$subject, $html, $text];
}

/** Short alert to the team for every completed sign-up. */
function notify_team(array $w, int $total): void {
    $lines = ["New waitlist sign-up (#$total)", '', 'Name: ' . $w['name'], 'Email: ' . $w['email'], 'Business: ' . ($w['company'] ?: '-'), 'Phone: ' . ($w['phone'] ?: '-'),
        'Channels: ' . (implode(', ', array_map(fn($c) => WAITLIST_CHANNELS[$c] ?? $c, $w['channels'] ?? [])) ?: '-'), 'Via: ' . $w['method'], 'Marketing opt-in: ' . (!empty($w['marketing']) ? 'yes' : 'no'),
        'Source: ' . (trim(($w['utm']['source'] ?? '') . ' / ' . ($w['utm']['medium'] ?? '') . ' / ' . ($w['utm']['campaign'] ?? ''), ' /') ?: ($w['referrer'] ?: 'direct')), '', url('admin/waitlist.php')];
    send_mail(cfg('contact_email'), 'Waitlist: ' . $w['name'] . ' (#' . $total . ')', nl2br(e(implode("\n", $lines))), implode("\n", $lines), $w['email']);
}
