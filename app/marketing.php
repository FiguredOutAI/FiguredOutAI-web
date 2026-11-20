<?php
/**
 * Marketing plumbing for waitlist sign-ups:
 *  - signed one-click unsubscribe links (RFC 8058 List-Unsubscribe headers)
 *  - Resend Contacts sync, so campaigns go out as Resend Broadcasts
 *  - Meta Conversions API "CompleteRegistration" for people who opted in to marketing
 * Everything is a no-op until its keys are saved in Admin → Settings → Waitlist.
 */
require_once __DIR__ . '/waitlist.php';

const META_GRAPH_VERSION = 'v27.0'; // bump when Meta deprecates it (about every 2 years)

/* ---------- unsubscribe links ---------- */
function unsub_token(string $email): string { return substr(hash_hmac('sha256', strtolower($email), (string)cfg('cron_token') . 'unsub'), 0, 32); }
function unsub_url(string $email): string { return url('unsubscribe') . '?e=' . rawurlencode(strtolower($email)) . '&t=' . unsub_token($email); }
function unsub_valid(string $email, string $token): bool { return $email !== '' && hash_equals(unsub_token($email), $token); }
/** Headers that make Gmail/Yahoo show their own "Unsubscribe" button (one click, no login). */
function unsub_headers(string $email): array {
    return ['List-Unsubscribe' => '<' . unsub_url($email) . '>, <mailto:' . cfg('contact_email') . '?subject=unsubscribe>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'];
}

/* ---------- Resend Contacts (the marketing list) ---------- */
/** Contacts need a full-access key; the send-only key is used when no separate one is saved. */
function resend_contacts_key(): string { return trim((string)(settings()['resend_contacts_key'] ?? '')) ?: resend_key(); }
function resend_segment(string $key): string { $id = trim((string)(settings()[$key] ?? '')); return preg_match('~^[a-f0-9-]{36}$~', $id) ? $id : ''; }

/**
 * Adds or updates a joined sign-up as a Resend contact.
 *  - Everyone who joined goes into the "All waitlist" segment (they consented to access and launch updates).
 *  - Only people who ticked the marketing box go into the "Marketing" segment. Send promotions to that one.
 *  - Anyone who used our unsubscribe link stays unsubscribed from every Broadcast.
 */
function resend_sync_contact(array $w): bool {
    $key = resend_contacts_key();
    if (!$key || empty(settings()['resend_sync'])) return false;
    $auth = ['Authorization: Bearer ' . $key];
    $email = rawurlencode($w['email']);
    $first = trim(explode(' ', (string)($w['name'] ?? ''))[0]);
    $props = ['unsubscribed' => !empty($w['unsubscribed']), 'first_name' => $first];
    [$code, $res] = http_json('POST', 'https://api.resend.com/contacts', ['email' => $w['email']] + $props, $auth);
    if ($code < 200 || $code >= 300) [$code, $res] = http_json('PATCH', "https://api.resend.com/contacts/$email", $props, $auth); // already a contact
    if ($code < 200 || $code >= 300) { error_log("Resend contact sync failed ($code): " . json_encode($res)); return false; }
    if ($all = resend_segment('resend_segment_all')) http_json('POST', "https://api.resend.com/contacts/$email/segments/$all", null, $auth);
    if ($mkt = resend_segment('resend_segment_marketing'))
        http_json(!empty($w['marketing']) && empty($w['unsubscribed']) ? 'POST' : 'DELETE', "https://api.resend.com/contacts/$email/segments/$mkt", null, $auth);
    return true;
}
function resend_unsubscribe(string $email): void {
    if ($key = resend_contacts_key()) http_json('PATCH', 'https://api.resend.com/contacts/' . rawurlencode($email), ['unsubscribed' => true], ['Authorization: Bearer ' . $key]);
}
function resend_delete_contact(string $email): void {
    if ($key = resend_contacts_key()) http_json('DELETE', 'https://api.resend.com/contacts/' . rawurlencode($email), null, ['Authorization: Bearer ' . $key]);
}

/* ---------- Meta Conversions API ---------- */
function meta_capi_ready(): bool {
    $s = settings();
    return preg_match('~^\d{10,20}$~', (string)($s['meta_pixel_id'] ?? '')) === 1 && trim((string)($s['meta_capi_token'] ?? '')) !== '';
}
/**
 * Sends one CompleteRegistration event for a sign-up who ticked the marketing box. Email, phone and first name
 * are normalised and SHA-256 hashed here; Meta never receives them in plain text. $eventId matches the browser
 * pixel's eventID so Meta counts the sign-up once.
 */
function meta_capi_signup(array $w, string $eventId, array $req): bool {
    if (!meta_capi_ready() || empty($w['marketing'])) return false;
    $s = settings();
    $h = fn($v) => hash('sha256', $v);
    $ud = ['em' => [$h(strtolower(trim($w['email'])))]];
    if ($ph = preg_replace('~\D~', '', (string)($w['phone'] ?? ''))) $ud['ph'] = [$h($ph)];
    if ($fn = strtolower(trim(explode(' ', (string)($w['name'] ?? ''))[0]))) $ud['fn'] = [$h($fn)];
    $ud['external_id'] = [$h($w['id'])];
    foreach (['client_ip_address' => $req['ip'] ?? '', 'client_user_agent' => $req['ua'] ?? '', 'fbp' => $req['fbp'] ?? '', 'fbc' => $req['fbc'] ?? ''] as $k => $v) if ($v !== '') $ud[$k] = $v;
    $event = ['event_name' => 'CompleteRegistration', 'event_time' => time(), 'event_id' => $eventId, 'action_source' => 'website',
        'event_source_url' => url(ltrim((string)($w['page'] ?: '/'), '/')), 'user_data' => $ud,
        'custom_data' => ['content_name' => 'waitlist', 'status' => 'joined']];
    $body = ['data' => [$event]];
    if ($test = trim((string)($s['meta_test_code'] ?? ''))) $body['test_event_code'] = $test;
    [$code, $res] = http_json('POST', 'https://graph.facebook.com/' . META_GRAPH_VERSION . '/' . $s['meta_pixel_id'] . '/events?access_token=' . rawurlencode(trim((string)$s['meta_capi_token'])), $body);
    if ($code >= 200 && $code < 300) return true;
    error_log("Meta CAPI failed ($code): " . json_encode($res));
    return false;
}
