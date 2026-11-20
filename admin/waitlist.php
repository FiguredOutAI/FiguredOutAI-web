<?php
require __DIR__ . '/_inc.php';
require dirname(__DIR__) . '/app/waitlist.php';
require dirname(__DIR__) . '/app/marketing.php';
require_admin();
$all = waitlist_all();
$statuses = ['waiting' => 'Waiting', 'invited' => 'Invited', 'onboarded' => 'Onboarded', 'removed' => 'Removed'];
$src = fn($w) => ($w['utm']['source'] ?? '') ?: (($r = parse_url((string)($w['referrer'] ?? ''), PHP_URL_HOST)) ? preg_replace('~^www\.~', '', $r) : 'direct');

// Ad audience: only people who ticked the marketing box and never unsubscribed.
// Columns follow Meta Custom Audience and Google Customer Match upload formats (they hash on upload).
if (($_GET['export'] ?? '') === 'audience') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="figuredoutai-ad-audience-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w');
    fputcsv($o, ['email', 'phone', 'fn']);
    foreach ($all as $w) {
        if (($w['stage'] ?? '') !== 'joined' || empty($w['marketing']) || !empty($w['unsubscribed'])) continue;
        fputcsv($o, [strtolower($w['email']), preg_replace('~[^\d+]~', '', (string)$w['phone']), strtolower(trim(explode(' ', (string)$w['name'])[0]))]);
    }
    exit;
}
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="figuredoutai-waitlist-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w');
    fputcsv($o, ['Signed up', 'Stage', 'Status', 'Name', 'Email', 'Phone', 'Business', 'Channels', 'Via', 'Consent', 'Marketing opt-in', 'Unsubscribed', 'UTM source', 'UTM medium', 'UTM campaign', 'UTM content', 'Landing page', 'Referrer', 'Welcome email']);
    $safe = fn($v) => preg_match('~^[=+\-@\t\r]~', (string)$v) ? "'" . $v : $v;
    foreach ($all as $w) fputcsv($o, array_map($safe, [$w['joined_at'] ?: $w['at'], $w['stage'], $w['status'], $w['name'], $w['email'], $w['phone'], $w['company'], implode(' ', $w['channels'] ?? []), $w['method'], $w['consent'] ? 'yes' : 'no', !empty($w['marketing']) ? 'yes' : 'no', !empty($w['unsubscribed']) ? 'yes' : 'no',
        $w['utm']['source'] ?? '', $w['utm']['medium'] ?? '', $w['utm']['campaign'] ?? '', $w['utm']['content'] ?? '', $w['landing'] ?? '', $w['referrer'] ?? '', $w['welcome_sent'] ? 'sent' : 'not sent']));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (string)($_POST['id'] ?? '');
    $fh = fopen(CONTENT . '/waitlist.lock', 'c'); flock($fh, LOCK_EX);
    $all = waitlist_all();
    foreach ($all as $i => $w) {
        if ($w['id'] !== $id) continue;
        if (($_POST['action'] ?? '') === 'delete') { resend_delete_contact($w['email']); array_splice($all, $i, 1); flash('Removed from the waitlist and from Resend contacts.'); }
        elseif (isset($statuses[$_POST['status'] ?? ''])) { $all[$i]['status'] = $_POST['status']; flash('Updated.'); }
        break;
    }
    waitlist_save($all);
    flock($fh, LOCK_UN);
    redirect('/admin/waitlist.php' . (isset($_GET['show']) ? '?show=' . rawurlencode((string)$_GET['show']) : ''));
}

$joined = array_values(array_filter($all, fn($w) => $w['stage'] === 'joined'));
$partial = array_values(array_filter($all, fn($w) => $w['stage'] !== 'joined'));
$today = count(array_filter($joined, fn($w) => substr((string)$w['joined_at'], 0, 10) === date('Y-m-d')));
$week = count(array_filter($joined, fn($w) => strtotime((string)$w['joined_at']) > time() - 7 * 86400));
$sources = []; foreach ($joined as $w) { $k = $src($w); $sources[$k] = ($sources[$k] ?? 0) + 1; } arsort($sources);
$chan = array_fill_keys(array_keys(WAITLIST_CHANNELS), 0); foreach ($joined as $w) foreach ($w['channels'] ?? [] as $c) if (isset($chan[$c])) $chan[$c]++;
$shown = waitlist_display_count();
$mode = settings()['wl_counter_mode'] ?? 'hidden';
$show = ($_GET['show'] ?? 'joined') === 'partial' ? 'partial' : 'joined';
$list = $show === 'partial' ? $partial : $joined;

admin_open('Waitlist', 'waitlist.php');
?>
<div class="a-head"><h1>Waitlist</h1><?php if ($all): ?><span style="display:flex;gap:8px"><a class="a-btn" href="?export=audience" title="Opted-in sign-ups only, for Meta Custom Audiences and Google Customer Match">Export ad audience</a><a class="a-btn" href="?export=csv">Export CSV</a></span><?php endif; ?></div>
<div class="a-stats">
  <div><b><?= count($joined) ?></b><span>Joined</span></div>
  <div><b><?= $today ?></b><span>Today</span></div>
  <div><b><?= $week ?></b><span>Last 7 days</span></div>
  <div><b><?= count($partial) ?></b><span>Gave email only</span></div>
</div>
<p class="a-muted">Site counter: <b><?= $shown === null ? 'hidden' : number_format($shown) ?></b> (mode: <?= e($mode) ?>). Change it in <a href="/admin/settings.php#waitlist">Settings → Waitlist</a>.
<?php if (!resend_key()): ?><br><b class="warn">Welcome emails go out through PHP mail() until you add a Resend API key in Settings, and they may land in spam.</b><?php endif; ?>
<?php if (!google_client_id()): ?><br>"Continue with Google" is hidden until you add a Google Client ID in Settings.<?php endif; ?></p>

<?php if ($joined): ?>
<div class="set-grid">
  <section class="a-card"><h2>Top sources</h2><table class="a-table"><?php foreach (array_slice($sources, 0, 8, true) as $k => $n): ?><tr><td><?= e($k) ?></td><td class="num"><?= $n ?></td></tr><?php endforeach; ?></table></section>
  <section class="a-card"><h2>Where they sell</h2><table class="a-table"><?php foreach ($chan as $k => $n): ?><tr><td><?= e(WAITLIST_CHANNELS[$k]) ?></td><td class="num"><?= $n ?></td></tr><?php endforeach; ?></table></section>
</div>
<?php endif; ?>

<div class="a-tabs"><a href="?show=joined" class="<?= $show === 'joined' ? 'on' : '' ?>">Joined (<?= count($joined) ?>)</a><a href="?show=partial" class="<?= $show === 'partial' ? 'on' : '' ?>">Email only (<?= count($partial) ?>)</a></div>
<?php if (!$list): ?>
  <section class="a-card"><p class="a-muted"><?= $show === 'joined' ? 'Nobody yet. Share the link and this fills up.' : 'Nobody dropped off after step 1.' ?></p></section>
<?php else: ?>
<section class="a-card a-table-wrap">
<table class="a-table wl">
  <thead><tr><th>Who</th><th>Business / phone</th><th>Channels</th><th>Source</th><th>When</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $w): ?>
    <tr>
      <td><b><?= e($w['name'] ?: '—') ?></b><br><a href="mailto:<?= e($w['email']) ?>"><?= e($w['email']) ?></a><?= $w['method'] === 'google' ? ' <span class="pill">Google</span>' : '' ?></td>
      <td><?= e($w['company'] ?: '—') ?><br><?php if ($w['phone']): ?><a href="https://wa.me/<?= e(ltrim($w['phone'], '+')) ?>" target="_blank" rel="noopener"><?= e($w['phone']) ?></a><?php else: ?><span class="a-muted">—</span><?php endif; ?></td>
      <td><?= e(implode(', ', array_map(fn($c) => WAITLIST_CHANNELS[$c] ?? $c, $w['channels'] ?? [])) ?: '—') ?></td>
      <td><?= e($src($w)) ?><?php if (!empty($w['utm']['campaign'])): ?><br><span class="a-muted small"><?= e($w['utm']['campaign']) ?></span><?php endif; ?></td>
      <td class="small"><?= e(date('j M, H:i', strtotime((string)($w['joined_at'] ?: $w['at'])))) ?><?php if ($w['stage'] === 'joined'): ?><br><span class="a-muted"><?= $w['welcome_sent'] ? 'welcome sent' : 'welcome not sent' ?></span><?php endif; ?></td>
      <td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($w['id']) ?>"><select name="status" onchange="this.form.submit()"><?php foreach ($statuses as $k => $v): ?><option value="<?= $k ?>" <?= $w['status'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></form></td>
      <td><form method="post" onsubmit="return confirm('Delete this person from the waitlist? This cannot be undone.')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($w['id']) ?>"><button name="action" value="delete" class="danger">Delete</button></form></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</section>
<?php endif; ?>
<?php admin_close();
