<?php
/** Shared <head>, header and footer for every public page. */

const ASSET_VER = '2.1.0';

function asset(string $p): string { return '/assets/' . $p . '?v=' . ASSET_VER; }

function page_open(array $meta, array $opt = []): void {
    $home = !empty($opt['home']);
    $css = $opt['css'] ?? [];
    $p = $home ? '' : '/';
    $sp = !empty($opt['landing']) ? '/' : $p; // landing pages have no services sections
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?= head_tags($meta) ?>
<meta name="theme-color" content="#05070A">
<link rel="icon" href="/favicon.ico?v=2" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png?v=2">
<link rel="icon" type="image/png" sizes="48x48" href="/favicon-48.png?v=2">
<link rel="icon" type="image/png" sizes="96x96" href="/favicon-96.png?v=2">
<link rel="icon" type="image/png" sizes="192x192" href="/favicon-192.png?v=2">
<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
<link rel="manifest" href="/site.webmanifest">
<link rel="preload" href="/assets/fonts/geist-300-600-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
<?php foreach ($css as $c): ?><link rel="stylesheet" href="<?= asset($c) ?>">
<?php endforeach; ?>
<?= tracking_head() ?>
</head>
<body class="<?= $home ? 'is-home' : 'is-inner' ?>"><script>document.body.classList.add('js')</script>
<a class="skip" href="#main">Skip to content</a>
<?php if ($home): ?>
<canvas id="scene" aria-hidden="true"></canvas>
<?php else: ?>
<div class="inner-bg" aria-hidden="true"></div>
<?php endif; ?>
<div class="cursor-glow" id="cglow" aria-hidden="true"></div>

<header id="hdr" class="<?= $home ? '' : 'scrolled' ?>">
  <div class="wrap nav">
    <a href="/" class="brand" aria-label="FiguredOutAI home"><picture><source srcset="/assets/img/logo-mark.webp" type="image/webp"><img src="/assets/img/logo-mark.png" alt="" width="29" height="26"></picture><span>FiguredOutAI</span></a>
    <nav aria-label="Main">
      <ul id="menu">
        <li><a href="<?= $p ?>#platform">Product</a></li>
        <li><a href="<?= $sp ?>#capabilities">Custom AI</a></li>
        <li><a href="<?= $p ?>#faq">FAQ</a></li>
        <li><a href="/blog/"<?= !$home && str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/blog') ? ' aria-current="page"' : '' ?>>Blog</a></li>
        <li><a href="<?= $sp ?>#contact">Book a call</a></li>
        <li class="menu-cta"><a href="<?= $p ?>#join">Join the waitlist</a></li>
      </ul>
    </nav>
    <a href="<?= $p ?>#join" class="btn btn-p btn-sm magnetic nav-cta">Join the waitlist <span class="arr">→</span></a>
    <button class="burger" id="burger" aria-label="Open menu" aria-expanded="false" aria-controls="menu"><i></i><i></i></button>
  </div>
</header>
<?php
}

function page_close(array $opt = []): void {
    $home = !empty($opt['home']);
    $p = $home ? '' : '/';
    $sp = !empty($opt['landing']) ? '/' : $p;
    $social = settings()['social'] ?? [];
    ?>
<footer>
  <div class="wrap">
    <div class="f-grid">
      <div class="about">
        <a href="/" class="brand"><picture><source srcset="/assets/img/logo-mark.webp" type="image/webp"><img src="/assets/img/logo-mark.png" alt="" width="29" height="26" loading="lazy"></picture><span>FiguredOutAI</span></a>
        <p>The AI inbox that handles itself, plus custom AI for businesses worldwide. We design, build and run the systems.</p>
      </div>
      <div><h6>Product</h6><ul><li><a href="<?= $p ?>#platform">The AI inbox</a></li><li><a href="<?= $p ?>#join">Join the waitlist</a></li><li><a href="<?= $sp ?>#capabilities">Custom AI</a></li><li><a href="<?= $sp ?>#stack">Infrastructure</a></li></ul></div>
      <div><h6>Company</h6><ul><li><a href="/about">About</a></li><li><a href="/blog/">Blog</a></li><li><a href="<?= $sp ?>#team">Leadership</a></li><li><a href="<?= $p ?>#faq">FAQ</a></li></ul></div>
      <div><h6>Connect</h6><ul>
        <li><a href="mailto:<?= e(cfg('contact_email')) ?>"><?= e(cfg('contact_email')) ?></a></li>
        <li><?= social_buttons(company_links(), 'FiguredOutAI') ?></li>
      </ul></div>
    </div>
    <div class="f-big" aria-hidden="true">FiguredOutAI</div>
    <div class="f-bot"><span>© <?= date('Y') ?> FiguredoutAI LLP. All rights reserved.</span><span><a href="/privacy">Privacy</a> · <a href="/privacy#cookies">Cookies</a> · <button type="button" class="link-btn" data-cookie-settings>Cookie settings</button> · <a href="/terms">Terms</a></span></div>
  </div>
</footer>
<?php if ($home): ?><script>
/* The three.js scene (about 670 KB) loads once the page is usable, so text and the waitlist form are interactive first. */
(function () { var go = function () { import('<?= asset('js/scene.js') ?>'); };
  var phone = matchMedia('(max-width: 820px)').matches; // phones: give the page a few quiet seconds first
  var idle = function () { setTimeout(function () { ('requestIdleCallback' in window) ? requestIdleCallback(go, { timeout: 2500 }) : go(); }, phone ? 3000 : 0); };
  document.readyState === 'complete' ? idle() : addEventListener('load', idle, { once: true }); })();
</script><?php endif; ?>
<script src="<?= asset('js/consent.js') ?>" defer></script>
<script src="<?= asset('js/site.js') ?>" defer></script>
</body>
</html>
<?php
}
