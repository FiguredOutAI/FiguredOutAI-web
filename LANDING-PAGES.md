# Campaign landing pages

Every marketing landing page is one short PHP file in `public_html/lp/`. The file `lp/<name>.php` is served at `https://figuredoutai.com/lp/<name>`. It reuses the same hero (live inbox plus the 3D mark), product sections and waitlist form as the homepage, so it stays on brand automatically. Sign-ups record the page and UTM tags, and you can see them in Admin → Waitlist.

## New page in 3 steps
1. Copy the template below into `public_html/lp/<name>.php`. Use lowercase letters, digits and dashes for the name, for example `lp/salons.php`.
2. Change the kicker, title, lead, FAQ and join copy. Keep the title short: one line plus a second, softer line inside `<span class="soft">`.
3. Upload that one file to `public_html/lp/` in Hostinger. A single new file is safe; only folder zips get renamed on extract. Link to it with UTM tags, for example `/lp/salons?utm_source=meta&utm_campaign=salons-nov`.

```php
<?php
require dirname(__DIR__) . '/app/lib.php';
require dirname(__DIR__) . '/app/layout.php';
require dirname(__DIR__) . '/app/product.php';
page_open([
  'title' => 'Salon bookings on WhatsApp, answered by AI | FiguredOutAI',
  'description' => 'One AI inbox for WhatsApp, Instagram and Messenger that books, answers prices and routes every chat. Join the waitlist.',
  'canonical' => url('lp/salons'),
  'noindex' => true,                       // campaign pages stay out of Google; the homepage carries SEO
  'og_title' => 'Your DMs book themselves', 'og_kicker' => 'Join the waitlist',
  'extra' => waitlist_head_js(),
  'jsonld' => [product_jsonld()],
], ['home' => true, 'landing' => true]);
?>
<main id="main">
<?php product_hero([
  'kicker' => 'Early access · for salons and studios',
  'title'  => 'Bookings that <span class="soft">answer themselves</span>',
  'lead'   => 'Clients ask for slots on WhatsApp and Instagram at midnight. FiguredOutAI replies, offers times and flags the ones ready to book. <strong>You do the hair, it does the DMs.</strong>',
]); product_channels(); product_features(); ?>
<section id="faq">
  <div class="wrap faq-grid">
    <div><div class="eyebrow">FAQ</div><h2>Questions, <span class="soft">answered</span></h2></div>
    <div class="faq">
      <?php foreach (PRODUCT_FAQ as $i => $q): ?>
      <details<?= $i === 0 ? ' open' : '' ?>><summary><?= e($q[0]) ?><i></i></summary><div class="ans"><?= e($q[1]) ?></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php product_join(['title' => 'Stop answering “any slots today?” <span class="soft">by hand</span>']); ?>
<section id="contact" class="cta custom mini">
  <div class="wrap"><p class="mini-note">Agency or need something custom? <a href="/#contact" class="tlink">Talk to us →</a></p></div>
</section>
</main>
<?php page_close(['home' => true, 'landing' => true]);
```

## What you can change per page
| Piece | How |
| --- | --- |
| Hero kicker, title, lead | `product_hero([...])`; leave any key out to use the homepage copy |
| Sections | Call only the ones you want: `product_channels()`, `product_features()` |
| Closing call to action | `product_join(['title' => ..., 'lead' => ...])` |
| Page title, share card | `title`, `description`, `og_title`, `og_kicker` in `page_open` |

## House rules for the copy
- Say what the inbox does for that audience in their own nouns (slots, orders, site visits), not "AI-powered".
- Headlines carry no trailing period. No exclamation marks.
- Only claim what is true today: early access, the three channels, and pricing announced before launch.
