<?php
require __DIR__ . '/_inc.php';
require_admin();
$s = settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'login') {
        $c = admin_creds();
        $newUser = clean_username((string)($_POST['username'] ?? ''));
        $newPw = (string)($_POST['new'] ?? '');
        if (!password_verify((string)($_POST['current'] ?? ''), $c['hash'] ?? '')) flash('Current password is wrong.', 'err');
        elseif (!valid_username($newUser)) flash('Username: 3 to 60 characters, using letters, numbers, dots, dashes or @.', 'err');
        elseif ($newPw !== '' && strlen($newPw) < 10) flash('New password needs at least 10 characters.', 'err');
        elseif ($newPw !== '' && $newPw !== ($_POST['new2'] ?? '')) flash('New passwords do not match.', 'err');
        else {
            save_admin_creds($newUser, $newPw !== '' ? password_hash($newPw, PASSWORD_DEFAULT) : $c['hash']);
            admin_login($newUser);
            flash($newPw !== '' ? 'Login details updated.' : 'Username updated.');
        }
    } elseif (in_array($_POST['action'] ?? '', ['waitlist', 'test_email'], true)) {
        require_once ROOT . '/app/waitlist.php';
        $s['wl_counter_mode'] = in_array($_POST['wl_counter_mode'] ?? '', ['hidden', 'real', 'offset'], true) ? $_POST['wl_counter_mode'] : 'hidden';
        $s['wl_counter_offset'] = max(0, min(1000000, (int)($_POST['wl_counter_offset'] ?? 0)));
        $s['wl_counter_min'] = max(0, min(1000000, (int)($_POST['wl_counter_min'] ?? 0)));
        $gid = trim((string)($_POST['google_client_id'] ?? ''));
        $s['google_client_id'] = preg_match('~^[0-9]+-[a-z0-9]+\.apps\.googleusercontent\.com$~', $gid) ? $gid : '';
        $rk = trim((string)($_POST['resend_api_key'] ?? ''));
        if (!empty($_POST['resend_clear'])) $s['resend_api_key'] = '';
        elseif ($rk !== '') $s['resend_api_key'] = preg_match('~^re_[A-Za-z0-9_]{10,80}$~', $rk) ? $rk : ($s['resend_api_key'] ?? '');
        $s['wl_from_name'] = trim(mb_substr(str_replace(['"', "\r", "\n", '<', '>'], '', (string)($_POST['wl_from_name'] ?? '')), 0, 60));
        // Marketing list (Resend Contacts + segments) and Meta Conversions API
        $ck = trim((string)($_POST['resend_contacts_key'] ?? ''));
        if (!empty($_POST['resend_contacts_clear'])) $s['resend_contacts_key'] = '';
        elseif ($ck !== '' && preg_match('~^re_[A-Za-z0-9_]{10,80}$~', $ck)) $s['resend_contacts_key'] = $ck;
        $s['resend_sync'] = !empty($_POST['resend_sync']);
        foreach (['resend_segment_all', 'resend_segment_marketing'] as $sk) { $v = strtolower(trim((string)($_POST[$sk] ?? ''))); $s[$sk] = preg_match('~^[a-f0-9-]{36}$~', $v) ? $v : ''; }
        $mt = trim((string)($_POST['meta_capi_token'] ?? ''));
        if (!empty($_POST['meta_capi_clear'])) $s['meta_capi_token'] = '';
        elseif ($mt !== '' && preg_match('~^[A-Za-z0-9]{40,400}$~', $mt)) $s['meta_capi_token'] = $mt;
        $tc = trim((string)($_POST['meta_test_code'] ?? ''));
        $s['meta_test_code'] = preg_match('~^TEST[A-Za-z0-9]{1,20}$~', $tc) ? $tc : '';
        $fe = trim((string)($_POST['wl_from_email'] ?? ''));
        $s['wl_from_email'] = filter_var($fe, FILTER_VALIDATE_EMAIL) && str_ends_with(strtolower($fe), '@figuredoutai.com') ? strtolower($fe) : '';
        save_settings($s);
        if ($gid !== '' && !$s['google_client_id']) flash('Saved, but that Google Client ID looks wrong. It should end in .apps.googleusercontent.com.', 'err');
        elseif ($rk !== '' && ($s['resend_api_key'] ?? '') !== $rk && empty($_POST['resend_clear'])) flash('Saved, but that Resend key looks wrong. It starts with re_.', 'err');
        elseif ($_POST['action'] === 'test_email') {
            [$sub, $html, $text] = welcome_email(['name' => 'Test Founder']);
            $sent = send_mail(cfg('contact_email'), '[Test] ' . $sub, $html, $text);
            flash($sent ? 'Test welcome email sent to ' . cfg('contact_email') . (resend_key() ? ' through Resend.' : ' through PHP mail(). Check spam.')
                        : 'Sending failed. Check the Resend key and that figuredoutai.com is verified in Resend.', $sent ? 'ok' : 'err');
        } else flash('Waitlist settings saved.');
        redirect('/admin/settings.php#waitlist');
    } else {
        $clean = fn($k, $re) => preg_match($re, trim((string)($_POST[$k] ?? ''))) ? trim((string)$_POST[$k]) : '';
        $s['gsc_verification'] = $clean('gsc_verification', '~^[A-Za-z0-9_-]{10,100}$~');
        $s['bing_verification'] = $clean('bing_verification', '~^[A-Fa-f0-9]{20,64}$~');
        $s['ga4_id'] = strtoupper($clean('ga4_id', '~^[Gg]-[A-Za-z0-9]{4,20}$~'));
        $s['clarity_id'] = strtolower($clean('clarity_id', '~^[A-Za-z0-9]{6,20}$~'));
        $s['gads_id'] = strtoupper($clean('gads_id', '~^[Aa][Ww]-\d{6,15}$~'));
        $s['gads_lead_label'] = $clean('gads_lead_label', '~^[A-Za-z0-9_-]{6,40}$~');
        $s['meta_pixel_id'] = $clean('meta_pixel_id', '~^\d{10,20}$~');
        $s['linkedin_partner_id'] = $clean('linkedin_partner_id', '~^\d{4,12}$~');
        foreach (['founder_linkedin_tanishk', 'founder_linkedin_yash'] as $k) { $v = trim((string)($_POST[$k] ?? '')); $s[$k] = preg_match('~^https://([a-z]+\.)?linkedin\.com/in/[A-Za-z0-9_%-]+/?$~', $v) ? $v : ''; }
        $s['social'] = [];
        foreach (['linkedin', 'instagram', 'x', 'youtube'] as $k) { $v = trim((string)($_POST["social_$k"] ?? '')); if ($v && filter_var($v, FILTER_VALIDATE_URL) && str_starts_with($v, 'https://')) $s['social'][$k] = $v; }
        save_settings($s);
        flash('Settings saved.');
    }
    redirect('/admin/settings.php');
}
if (isset($_GET['consent_csv']) && is_file(CONTENT . '/consent_log.jsonl')) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="figuredoutai-consent-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w');
    fputcsv($o, ['Time', 'Consent ID', 'Analytics', 'Marketing', 'Policy version', 'Method']);
    foreach (file(CONTENT . '/consent_log.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $r = json_decode($line, true);
        if ($r) fputcsv($o, [$r['at'], $r['id'], $r['analytics'] ? 'yes' : 'no', $r['marketing'] ? 'yes' : 'no', $r['v'], $r['method']]);
    }
    exit;
}
admin_open('Settings', 'settings.php');
$soc = company_links(); // saved values, falling back to the built-in defaults
?>
<div class="a-head"><h1>Settings</h1></div>
<div class="set-grid">
<form method="post" class="a-card"><?= csrf_field() ?>
  <h2>Search & analytics</h2>
  <label>Google Search Console verification code <small>(the content="…" value only)</small><input name="gsc_verification" value="<?= e($s['gsc_verification'] ?? '') ?>"></label>
  <label>Bing Webmaster verification code<input name="bing_verification" value="<?= e($s['bing_verification'] ?? '') ?>"></label>
  <h2 class="mt">Analytics <small class="a-muted">(loads only after a visitor accepts "Analytics")</small></h2>
  <label>Google Analytics 4 measurement ID<input name="ga4_id" value="<?= e($s['ga4_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX"></label>
  <label>Microsoft Clarity project ID <small>(heatmaps & session recordings)</small><input name="clarity_id" value="<?= e($s['clarity_id'] ?? '') ?>" placeholder="abc123xyz"></label>
  <h2 class="mt">Ads & retargeting <small class="a-muted">(loads only after a visitor accepts "Marketing")</small></h2>
  <label>Google Ads tag ID<input name="gads_id" value="<?= e($s['gads_id'] ?? '') ?>" placeholder="AW-123456789"></label>
  <label>Google Ads lead conversion label <small>(optional: counts contact-form enquiries)</small><input name="gads_lead_label" value="<?= e($s['gads_lead_label'] ?? '') ?>" placeholder="AbCdEfGhIjKlMn"></label>
  <label>Meta (Facebook/Instagram) Pixel ID<input name="meta_pixel_id" value="<?= e($s['meta_pixel_id'] ?? '') ?>" placeholder="123456789012345"></label>
  <label>LinkedIn Insight Tag partner ID<input name="linkedin_partner_id" value="<?= e($s['linkedin_partner_id'] ?? '') ?>" placeholder="1234567"></label>
  <p class="a-muted small">The cookie banner appears automatically once at least one ID is saved. Enquiries fire a "Lead" event to GA4, Google Ads and Meta, but only for visitors who consented.</p>
  <h2 class="mt">Social profiles <small class="a-muted">(footer links + Organization schema)</small></h2>
  <label>LinkedIn<input name="social_linkedin" value="<?= e($soc['linkedin'] ?? '') ?>" placeholder="https://www.linkedin.com/company/…"></label>
  <label>Instagram<input name="social_instagram" value="<?= e($soc['instagram'] ?? '') ?>" placeholder="https://www.instagram.com/…"></label>
  <label>X / Twitter<input name="social_x" value="<?= e($soc['x'] ?? '') ?>" placeholder="https://x.com/…"></label>
  <label>YouTube<input name="social_youtube" value="<?= e($soc['youtube'] ?? '') ?>" placeholder="https://www.youtube.com/@…"></label>
  <h2 class="mt">Founders <small class="a-muted">(shown on /about, links your names to the company for Google)</small></h2>
  <label>Tanishk Singhal's LinkedIn profile URL<input name="founder_linkedin_tanishk" value="<?= e(founder_links('tanishk')['linkedin'] ?? '') ?>" placeholder="https://www.linkedin.com/in/…"></label>
  <label>Yashvardhan Awasthi's LinkedIn profile URL<input name="founder_linkedin_yash" value="<?= e(founder_links('yash')['linkedin'] ?? '') ?>" placeholder="https://www.linkedin.com/in/…"></label>
  <button class="a-btn p" name="action" value="save">Save settings</button>
</form>

<div>
<?php $wm = $s['wl_counter_mode'] ?? 'hidden'; $rkey = (string)($s['resend_api_key'] ?? ''); ?>
<form method="post" class="a-card" id="waitlist"><?= csrf_field() ?>
  <h2>Waitlist</h2>
  <p class="a-muted small">Real sign-ups right now: <b><?php require_once ROOT . '/app/waitlist.php'; echo waitlist_joined_count(); ?></b>. <a href="/admin/waitlist.php">Open the waitlist →</a></p>
  <h3 class="mt">Counter on the site</h3>
  <div class="radio-row">
    <label><input type="radio" name="wl_counter_mode" value="hidden" <?= $wm === 'hidden' ? 'checked' : '' ?>> Hidden. The site says "Early access opens in batches" instead.</label>
    <label><input type="radio" name="wl_counter_mode" value="real" <?= $wm === 'real' ? 'checked' : '' ?>> Real sign-ups only.</label>
    <label><input type="radio" name="wl_counter_mode" value="offset" <?= $wm === 'offset' ? 'checked' : '' ?>> Real sign-ups plus a starting number.</label>
  </div>
  <label>Starting number <small>(only used with the third option)</small><input type="number" min="0" name="wl_counter_offset" value="<?= (int)($s['wl_counter_offset'] ?? 0) ?>"></label>
  <label>Only show the counter once it reaches <small>(0 = always show)</small><input type="number" min="0" name="wl_counter_min" value="<?= (int)($s['wl_counter_min'] ?? 0) ?>"></label>
  <p class="a-muted small">Heads-up: Google Ads and Meta can reject ads whose landing page shows inflated numbers. "Real" with a minimum (say 100) is the safe setting.</p>
  <h3 class="mt">Continue with Google</h3>
  <label>Google OAuth Client ID <small>(public; no secret needed)</small><input name="google_client_id" value="<?= e($s['google_client_id'] ?? '') ?>" placeholder="1234567890-abc123.apps.googleusercontent.com" spellcheck="false"></label>
  <h3 class="mt">Welcome email</h3>
  <label>Resend API key <small><?= $rkey ? '(saved: re_…' . e(substr($rkey, -4)) . '. Leave empty to keep it)' : '(send-only key from resend.com → API Keys)' ?></small><input type="password" name="resend_api_key" value="" placeholder="re_…" autocomplete="off"></label>
  <?php if ($rkey): ?><label class="small"><input type="checkbox" name="resend_clear" value="1" style="width:auto"> Remove the saved key</label><?php endif; ?>
  <label>Sender name<input name="wl_from_name" value="<?= e($s['wl_from_name'] ?? '') ?>" placeholder="Tanishk from FiguredOutAI"></label>
  <label>Sender address <small>(must be @figuredoutai.com)</small><input name="wl_from_email" value="<?= e($s['wl_from_email'] ?? '') ?>" placeholder="<?= e(cfg('mail_from')) ?>"></label>
  <?php $ckey = (string)($s['resend_contacts_key'] ?? ''); $mtok = (string)($s['meta_capi_token'] ?? ''); ?>
  <h3 class="mt">Marketing list (Resend)</h3>
  <p class="a-muted small">Every completed sign-up is copied to Resend → Contacts. Send campaigns from Resend → Broadcasts: promotions to the <b>Marketing</b> segment only (people who ticked the optional box), launch and access news to <b>All waitlist</b>. Resend adds its own unsubscribe link to every Broadcast.</p>
  <label class="small"><input type="checkbox" name="resend_sync" value="1" style="width:auto" <?= !empty($s['resend_sync']) ? 'checked' : '' ?>> Copy sign-ups to Resend Contacts</label>
  <label>Resend full-access key for contacts <small><?= $ckey ? '(saved: re_…' . e(substr($ckey, -4)) . ')' : '(the send-only key above cannot manage contacts)' ?></small><input type="password" name="resend_contacts_key" value="" placeholder="re_…" autocomplete="off"></label>
  <?php if ($ckey): ?><label class="small"><input type="checkbox" name="resend_contacts_clear" value="1" style="width:auto"> Remove the saved contacts key</label><?php endif; ?>
  <label>Segment ID: All waitlist <small>(Resend → Contacts → Segments)</small><input name="resend_segment_all" value="<?= e($s['resend_segment_all'] ?? '') ?>" placeholder="78261eea-8f8b-4381-83c6-79fa7120f1cf" spellcheck="false"></label>
  <label>Segment ID: Marketing opt-in<input name="resend_segment_marketing" value="<?= e($s['resend_segment_marketing'] ?? '') ?>" placeholder="78261eea-8f8b-4381-83c6-79fa7120f1cf" spellcheck="false"></label>
  <h3 class="mt">Meta Conversions API</h3>
  <p class="a-muted small">Sends a server-side "CompleteRegistration" to the Meta Pixel set in Tracking above, only for sign-ups who ticked the marketing box. Email, phone and first name are SHA-256 hashed before they leave this server. Pairs with the browser pixel (same event ID), so each sign-up counts once.</p>
  <label>Conversions API access token <small><?= $mtok ? '(saved: …' . e(substr($mtok, -4)) . ')' : '(Events Manager → your pixel → Settings → Generate access token)' ?></small><input type="password" name="meta_capi_token" value="" autocomplete="off"></label>
  <?php if ($mtok): ?><label class="small"><input type="checkbox" name="meta_capi_clear" value="1" style="width:auto"> Remove the saved token</label><?php endif; ?>
  <label>Test event code <small>(optional, e.g. TEST12345; clear it after testing)</small><input name="meta_test_code" value="<?= e($s['meta_test_code'] ?? '') ?>" placeholder="TEST12345"></label>
  <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="a-btn p" name="action" value="waitlist">Save waitlist settings</button><button class="a-btn" name="action" value="test_email">Save &amp; send me a test email</button></div>
</form>
<form method="post" class="a-card"><?= csrf_field() ?>
  <h2>Login details</h2>
  <label>Username<input name="username" value="<?= e(admin_creds()['user'] ?? '') ?>" required minlength="3" maxlength="60" autocomplete="username" autocapitalize="none" spellcheck="false"></label>
  <label>New password <small>(leave empty to keep the current one)</small><input type="password" name="new" minlength="10" autocomplete="new-password"></label>
  <label>Repeat new password<input type="password" name="new2" autocomplete="new-password"></label>
  <label>Current password <small>(required to save)</small><input type="password" name="current" required autocomplete="current-password"></label>
  <button class="a-btn" name="action" value="login">Update login</button>
</form>
<?php
// Consent stats, last 30 days (latest choice per visitor consent ID).
$latest = [];
$logFile = CONTENT . '/consent_log.jsonl';
if (is_file($logFile)) {
    $since = time() - 30 * 86400;
    foreach (file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $r = json_decode($line, true);
        if ($r && strtotime($r['at']) >= $since) $latest[$r['id']] = $r;
    }
}
$tot = count($latest);
$pa = $tot ? round(100 * count(array_filter($latest, fn($r) => $r['analytics'])) / $tot) : 0;
$pm = $tot ? round(100 * count(array_filter($latest, fn($r) => $r['marketing'])) / $tot) : 0;
?>
<section class="a-card">
  <h2>Cookie consent · last 30 days</h2>
  <?php if (!tracking_ids()): ?><p class="a-muted small">No analytics or ad IDs saved yet, so visitors don't see a cookie banner. Add an ID on the left to switch it on.</p><?php endif; ?>
  <dl class="kv"><dt>Visitors who chose</dt><dd><?= $tot ?></dd><dt>Allowed analytics</dt><dd><?= $pa ?>%</dd><dt>Allowed marketing</dt><dd><?= $pm ?>%</dd></dl>
  <?php if (is_file($logFile)): ?><p class="mt"><a class="a-btn" href="/admin/settings.php?consent_csv=1">Download consent records (CSV)</a></p><?php endif; ?>
  <p class="a-muted small mt">Records keep a random ID, time, choice and policy version, with no IP address or name. That is your proof of consent if a regulator or ad platform asks.</p>
</section>
<section class="a-card">
  <h2>Server settings</h2>
  <p class="a-muted small">Change these in <code>app/config.php</code> through the Hostinger File Manager.</p>
  <dl class="kv"><dt>Site URL</dt><dd><?= e(cfg('base_url')) ?></dd><dt>Enquiries go to</dt><dd><?= e(cfg('contact_email')) ?></dd><dt>Timezone</dt><dd><?= e(cfg('timezone')) ?> · now <?= date('j M H:i') ?></dd><dt>PHP</dt><dd><?= PHP_VERSION ?></dd></dl>
  <h3 class="mt">Hourly cron job (for scheduled posts)</h3>
  <p class="a-muted small">hPanel → Advanced → Cron Jobs → Custom, every hour:</p>
  <code class="block">php <?= e(ROOT) ?>/cron.php</code>
</section>
</div>
</div>
<?php admin_close();
