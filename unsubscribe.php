<?php
/**
 * Unsubscribe from all FiguredOutAI emails.
 *  GET  /unsubscribe?e=…&t=…  shows a confirm button (link scanners in corporate inboxes only GET, so they can't unsubscribe anyone)
 *  POST /unsubscribe?e=…&t=…  unsubscribes. Gmail/Yahoo one-click (RFC 8058) posts "List-Unsubscribe=One-Click" here.
 * The token is an HMAC of the email, so nobody can unsubscribe someone else.
 */
require __DIR__ . '/app/lib.php';
require __DIR__ . '/app/layout.php';
require __DIR__ . '/app/marketing.php';
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$email = strtolower(trim(mb_substr((string)($_GET['e'] ?? $_POST['e'] ?? ''), 0, 160)));
$token = (string)($_GET['t'] ?? $_POST['t'] ?? '');
$valid = filter_var($email, FILTER_VALIDATE_EMAIL) && unsub_valid($email, $token);
$done = false;

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $fh = fopen(CONTENT . '/waitlist.lock', 'c'); flock($fh, LOCK_EX);
    $all = waitlist_all();
    if (($i = waitlist_find($all, 'email', $email)) !== null) {
        $all[$i]['unsubscribed'] = true;
        $all[$i]['unsubscribed_at'] = date('c');
        $all[$i]['marketing'] = false;
        waitlist_save($all);
    }
    flock($fh, LOCK_UN);
    resend_unsubscribe($email);
    $done = true;
    // One-click requests from mail providers only need a 200.
    if (($_POST['List-Unsubscribe'] ?? '') === 'One-Click') { header('Content-Type: text/plain'); exit('Unsubscribed'); }
}

page_open(['title' => 'Unsubscribe | FiguredOutAI', 'description' => 'Unsubscribe from FiguredOutAI emails.', 'noindex' => true, 'canonical' => url('unsubscribe')], ['css' => ['css/blog.css']]);
?>
<main id="main" class="blog-main">
  <section class="blog-hero" style="padding-bottom:120px">
    <div class="wrap">
      <?php if (!$valid): ?>
        <h1>That link <span class="soft">doesn't work.</span></h1>
        <p class="blog-lead">It may be incomplete or copied wrong. Reply "unsubscribe" to any of our emails, or write to <a class="tlink" href="mailto:<?= e(cfg('contact_email')) ?>"><?= e(cfg('contact_email')) ?></a>, and we'll take you off by hand.</p>
      <?php elseif ($done): ?>
        <h1>You're <span class="soft">unsubscribed.</span></h1>
        <p class="blog-lead">No more emails from FiguredOutAI to <b><?= e($email) ?></b>. Changed your mind? Join the waitlist again any time.</p>
        <div class="cta-row" style="margin-top:32px"><a class="btn btn-g" href="/">Back to the site</a></div>
      <?php else: ?>
        <h1>Unsubscribe <span class="soft">from our emails?</span></h1>
        <p class="blog-lead">We'll stop all emails to <b><?= e($email) ?></b>, including launch updates and your access invite.</p>
        <form method="post" action="/unsubscribe?e=<?= e(rawurlencode($email)) ?>&amp;t=<?= e($token) ?>" style="margin-top:32px">
          <button class="btn btn-p" type="submit">Unsubscribe me</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php page_close();
