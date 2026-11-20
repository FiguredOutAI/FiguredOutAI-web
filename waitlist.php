<?php
/** Campaign landing page: the product only, no services. Not indexed (the homepage carries the SEO). */
require __DIR__ . '/app/lib.php';
require __DIR__ . '/app/layout.php';
require __DIR__ . '/app/product.php';
page_open([
  'title' => 'Join the waitlist | FiguredOutAI, the AI inbox that handles itself',
  'description' => 'One AI inbox for WhatsApp, Instagram and Messenger that replies, spots buyers and routes every chat. Early access opens in batches.',
  'canonical' => url('waitlist'),
  'noindex' => true,
  'og_title' => 'An AI inbox that handles itself', 'og_kicker' => 'Join the waitlist',
  'extra' => waitlist_head_js(),
  'jsonld' => [product_jsonld()],
], ['home' => true, 'landing' => true]);
?>
<main id="main">
<?php product_hero(); product_channels(); product_features(); ?>
<section id="faq">
  <div class="wrap faq-grid">
    <div><div class="eyebrow">FAQ</div><h2>Questions, <span class="soft">answered</span></h2></div>
    <div class="faq reveal">
      <?php foreach (PRODUCT_FAQ as $i => $q): ?>
      <details<?= $i === 0 ? ' open' : '' ?>><summary><?= e($q[0]) ?><i></i></summary><div class="ans"><?= e($q[1]) ?></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php product_join(); ?>
<section id="contact" class="cta custom mini">
  <div class="wrap"><p class="mini-note">Agency or need something custom? <a href="/#contact" class="tlink">Talk to us →</a></p></div>
</section>
</main>
<?php page_close(['home' => true, 'landing' => true]);
