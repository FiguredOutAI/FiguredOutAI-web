<?php
require dirname(__DIR__) . '/app/lib.php';
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
start_session();

function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function redirect(string $to): void { header('Location: ' . $to, true, 303); exit; }

function admin_open(string $title, string $active = ''): void {
    $f = flash();
    $nav = ['index.php' => 'Posts', 'edit.php' => 'New post', 'waitlist.php' => 'Waitlist', 'leads.php' => 'Leads', 'seo.php' => 'SEO & backlinks', 'settings.php' => 'Settings'];
    $newLeads = count(array_filter(json_read(CONTENT . '/leads.json', []), fn($l) => ($l['status'] ?? '') === 'new'));
    ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title><?= e($title) ?> · FiguredOutAI Admin</title>
<link rel="icon" href="/favicon.ico"><link rel="stylesheet" href="/assets/css/fonts.css"><link rel="stylesheet" href="/assets/css/admin.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/admin.css') ?>">
</head><body>
<header class="a-top">
  <a class="a-brand" href="/admin/"><img src="/assets/img/logo-mark.webp" alt="" width="24" height="22"><span>FiguredOutAI</span><em>Admin</em></a>
  <button class="a-burger" onclick="document.body.classList.toggle('nav-open')" aria-label="Menu">☰</button>
  <nav class="a-nav">
    <?php foreach ($nav as $href => $label): ?><a href="/admin/<?= $href ?>" class="<?= $active === $href ? 'on' : '' ?>"><?= $label ?><?php if ($href === 'leads.php' && $newLeads): ?> <b class="badge"><?= $newLeads ?></b><?php endif; ?></a><?php endforeach; ?>
    <a href="/" target="_blank" rel="noopener">View site ↗</a>
    <form method="post" action="/admin/logout.php"><?= csrf_field() ?><button>Log out</button></form>
  </nav>
</header>
<main class="a-main">
<?php if ($f): ?><div class="a-flash <?= e($f[1]) ?>"><?= e($f[0]) ?></div><?php endif; ?>
<?php
}
function admin_close(array $js = []): void {
    echo "</main>\n";
    foreach ($js as $j) echo '<script src="/assets/js/' . e($j) . '?v=' . filemtime(dirname(__DIR__) . '/assets/js/' . $j) . "\"></script>\n";
    echo "</body></html>";
}
function auth_page(string $title, string $inner): void {
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> · FiguredOutAI Admin</title><link rel="stylesheet" href="/assets/css/fonts.css"><link rel="stylesheet" href="/assets/css/admin.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/admin.css') ?>">
</head><body class="auth"><div class="auth-box"><img src="/assets/img/logo-mark.webp" alt="" width="44" height="40"><h1><?= e($title) ?></h1><?= $inner ?></div>
<script>document.querySelectorAll('.pw-eye').forEach(b=>b.addEventListener('click',()=>{const i=b.previousElementSibling,show=i.type==='password';i.type=show?'text':'password';b.textContent=show?'Hide':'Show';b.setAttribute('aria-label',show?'Hide password':'Show password');}));document.querySelector('.auth-box input:not([type=hidden])')?.focus();</script>
</body></html><?php
    exit;
}
