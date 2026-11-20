<?php
require __DIR__ . '/_inc.php';
$creds = admin_creds();
$userField = fn($v = '') => '<label>Username<input name="username" value="' . e($v) . '" required autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="60"></label>';
$pwField = fn($name, $label, $ac) => '<label>' . $label . '<span class="pw"><input type="password" name="' . $name . '" required autocomplete="' . $ac . '"><button type="button" class="pw-eye" aria-label="Show password">Show</button></span></label>';

/* ---------- first-run: create the admin account ---------- */
if (empty($creds['hash'])) {
    $err = ''; $u = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $u = clean_username((string)($_POST['username'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');
        if (!valid_username($u)) $err = 'Username: 3 to 60 characters, using letters, numbers, dots, dashes or @.';
        elseif (strlen($pw) < 10) $err = 'Password needs at least 10 characters.';
        elseif ($pw !== ($_POST['password2'] ?? '')) $err = 'Passwords do not match.';
        else {
            save_admin_creds($u, password_hash($pw, PASSWORD_DEFAULT));
            admin_login($u);
            flash('Admin account created. Welcome, ' . $u . '.');
            redirect('/admin/');
        }
    }
    auth_page('Create admin account', ($err ? '<p class="a-err">' . e($err) . '</p>' : '<p>Choose the username and password you will use to log in. This screen only appears until an account exists.</p>') .
        '<form method="post">' . csrf_field() . $userField($u) . $pwField('password', 'Password <small>(10+ characters)</small>', 'new-password') . $pwField('password2', 'Repeat password', 'new-password') . '<button class="a-btn p">Create account</button></form>');
}

/* ---------- login ---------- */
if (!is_admin()) {
    $err = ''; $u = '';
    $legacy = ($creds['user'] ?? '') === ''; // older install: password only, username not chosen yet
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $u = clean_username((string)($_POST['username'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');
        $userOk = $legacy ? valid_username($u) : hash_equals($creds['user'], $u);
        $pwOk = password_verify($pw, $creds['hash']); // always checked, so timing doesn't reveal whether the username exists
        if (login_blocked()) $err = 'Too many attempts. Try again in 15 minutes.';
        elseif ($userOk && $pwOk) {
            if ($legacy) save_admin_creds($u, $creds['hash']);
            elseif (password_needs_rehash($creds['hash'], PASSWORD_DEFAULT)) save_admin_creds($u, password_hash($pw, PASSWORD_DEFAULT));
            admin_login($u);
            if ($legacy) flash('Username "' . $u . '" saved. Use it with your password from now on.');
            redirect('/admin/');
        } else { login_failed(); usleep(400000); $err = 'Wrong username or password.'; }
    }
    auth_page('Admin login', ($err ? '<p class="a-err">' . e($err) . '</p>' : ($legacy ? '<p>Choose a username to use from now on, and enter your current password.</p>' : '')) .
        '<form method="post">' . csrf_field() . $userField($u) . $pwField('password', 'Password', 'current-password') . '<button class="a-btn p">Log in</button></form>' .
        '<p class="small a-muted forgot">Forgot your login? Delete <code>content/admin.json</code> in Hostinger File Manager, then reopen this page to create a new account.</p>');
}

/* ---------- actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $slug = (string)($_POST['slug'] ?? '');
    if (($_POST['action'] ?? '') === 'delete' && get_post($slug)) { delete_post($slug); flash('Post deleted.'); }
    if (($_POST['action'] ?? '') === 'duplicate' && ($p = get_post($slug))) {
        $n = 2; while (get_post($slug . '-' . $n)) $n++;
        $p['slug'] = $slug . '-' . $n; $p['title'] .= ' (copy)'; $p['status'] = 'draft'; $p['created'] = date('c'); $p['updated'] = null;
        save_post($p); flash('Draft copy created.');
    }
    redirect('/admin/');
}

/* ---------- dashboard ---------- */
$posts = all_posts();
$live = array_filter($posts, 'is_live');
$scheduled = array_filter($posts, fn($p) => $p['status'] === 'published' && !is_live($p));
$drafts = array_filter($posts, fn($p) => $p['status'] === 'draft');
$last30 = array_filter($live, fn($p) => $p['ts'] > now() - 30 * 86400);
// Posting streak: consecutive days (ending today or yesterday) with at least one live post.
$days = array_flip(array_map(fn($p) => date('Y-m-d', $p['ts']), $live));
$streak = 0; $d = isset($days[date('Y-m-d')]) ? 0 : 1;
while (isset($days[date('Y-m-d', strtotime("-$d day"))])) { $streak++; $d++; }
// Next 14 days plan
$byDay = [];
foreach ($posts as $p) if ($p['status'] === 'published') $byDay[date('Y-m-d', $p['ts'])][] = $p;
$filter = $_GET['f'] ?? 'all';
$list = ['all' => $posts, 'live' => $live, 'scheduled' => $scheduled, 'drafts' => $drafts][$filter] ?? $posts;

admin_open('Posts', 'index.php');
?>
<div class="a-head"><h1>Posts</h1><a class="a-btn p" href="/admin/edit.php">+ New post</a></div>

<div class="a-stats">
  <div><b><?= count($live) ?></b><span>Live</span></div>
  <div><b><?= count($scheduled) ?></b><span>Scheduled</span></div>
  <div><b><?= count($drafts) ?></b><span>Drafts</span></div>
  <div><b><?= count($last30) ?></b><span>Published, last 30 days</span></div>
  <div class="hl"><b><?= $streak ?> 🔥</b><span>Daily posting streak</span></div>
</div>

<section class="a-card">
  <div class="a-card-h"><h2>Daily publishing plan · next 14 days</h2><span class="a-muted">Schedule a post for every day to keep the streak.</span></div>
  <div class="cal">
    <?php for ($i = 0; $i < 14; $i++): $day = date('Y-m-d', strtotime("+$i day")); $items = $byDay[$day] ?? []; ?>
    <div class="cal-d <?= $items ? 'has' : '' ?> <?= $i === 0 ? 'today' : '' ?>">
      <div class="cal-h"><?= $i === 0 ? 'Today' : date('D j M', strtotime($day)) ?></div>
      <?php foreach ($items as $p): ?><a href="/admin/edit.php?slug=<?= e($p['slug']) ?>" class="cal-p"><?= e(mb_strimwidth($p['title'], 0, 48, '…')) ?></a><?php endforeach; ?>
      <?php if (!$items): ?><a class="cal-add" href="/admin/edit.php?date=<?= $day ?>">+ Plan post</a><?php endif; ?>
    </div>
    <?php endfor; ?>
  </div>
</section>

<section class="a-card">
  <div class="a-tabs">
    <?php foreach (['all' => 'All', 'live' => 'Live', 'scheduled' => 'Scheduled', 'drafts' => 'Drafts'] as $k => $l): ?><a class="<?= $filter === $k ? 'on' : '' ?>" href="?f=<?= $k ?>"><?= $l ?></a><?php endforeach; ?>
  </div>
  <?php if (!$list): ?><p class="a-muted" style="padding:24px 4px">Nothing here yet.</p><?php else: ?>
  <div class="a-table-wrap"><table class="a-table">
    <thead><tr><th>Title</th><th>Status</th><th>Date</th><th>Words</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($list as $p): $st = is_live($p) ? 'live' : ($p['status'] === 'draft' ? 'draft' : 'scheduled'); ?>
      <tr>
        <td><a class="t" href="/admin/edit.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a><small>/blog/<?= e($p['slug']) ?></small></td>
        <td><span class="pill <?= $st ?>"><?= ucfirst($st) ?></span></td>
        <td class="nowrap"><?= e(date('j M Y, H:i', $p['ts'])) ?></td>
        <td><?= word_count($p['content']) ?></td>
        <td class="acts">
          <a href="/blog/<?= e($p['slug']) ?>" target="_blank" rel="noopener"><?= $st === 'live' ? 'View' : 'Preview' ?></a>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($p['slug']) ?>"><button name="action" value="duplicate">Duplicate</button></form>
          <form method="post" onsubmit="return confirm('Delete this post permanently?')"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($p['slug']) ?>"><button name="action" value="delete" class="danger">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php admin_close();
