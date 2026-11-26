<?php
/**
 * Product (FiguredOutAI inbox) sections shared by the homepage, /waitlist and any
 * future campaign landing page. Each section is a function, so a new landing page
 * is a short PHP file that calls the ones it needs (see docs/LANDING-PAGES.md).
 */
require_once __DIR__ . '/waitlist.php';

const PRODUCT_FAQ = [
    ['What is FiguredOutAI?', 'An AI inbox for WhatsApp, Instagram and Facebook Messenger that replies, scores and routes your customer chats on its own. The same team (FiguredoutAI LLP, founded by Tanishk Singhal and Yashvardhan Awasthi) also builds custom AI agents, automation and software for businesses worldwide.'],
    ['When can I use it?', "Early access opens in batches, and the waitlist goes first. Join, and we'll email you the moment it's your turn."],
    ['Which channels does it support?', 'WhatsApp, Instagram and Facebook Messenger at launch. More channels once these three behave.'],
    ['Will the AI say something weird to my customers?', "Not unsupervised. You decide what it can handle alone and what needs a human. Every reply and decision is logged, so nothing happens behind your back."],
    ['What does it cost?', 'Pricing lands before launch. The waitlist hears first.'],
    ['Can agencies white-label it?', "Yes. It's built for white-labelling, so agencies can sell it under their own brand. Pick “White-label partnership” in the form below and we'll talk."],
];

function channel_icon(string $c): string {
    $i = [
        'whatsapp' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.3a9.7 9.7 0 0 0-8.4 14.6L2.3 21.7l4.9-1.3A9.7 9.7 0 1 0 12 2.3z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path fill="currentColor" d="M8.7 7.5c.3-.5.6-.5.9-.5h.6c.2 0 .4.1.5.4l.8 1.9c.1.3 0 .5-.1.7l-.6.7c-.1.2-.1.4 0 .6.4.8 1 1.5 1.7 2.1.6.5 1.2.9 1.9 1.2.2.1.4 0 .5-.1l.8-.9c.2-.2.4-.3.7-.2l1.8.9c.3.1.4.3.4.5 0 .5-.2 1.4-.9 1.8-.6.4-1.5.6-2.4.4-1.6-.4-3.1-1.2-4.3-2.4-1.2-1.1-2.2-2.5-2.6-4-.3-1-.1-2 .3-2.6z"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="3" width="18" height="18" rx="5.2"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.6" r="1.1" fill="currentColor" stroke="none"/></svg>',
        'messenger' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.2C6.5 2.2 2.2 6.3 2.2 11.5c0 2.9 1.3 5.4 3.5 7.1v3.4l3.1-1.7c1 .3 2.1.4 3.2.4 5.5 0 9.8-4.1 9.8-9.2S17.5 2.2 12 2.2z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path fill="currentColor" d="M6.6 14.4l3.1-4.9 2.6 2 3.9-2.1-3.1 4.9-2.6-2z"/></svg>',
    ];
    return '<span class="ch ch-' . $c . '">' . ($i[$c] ?? '') . '</span>';
}

/** The intent score chip: the one signature detail, used only where a chat gets judged. */
function score_chip(string $level, int $n): string {
    $label = ['hi' => 'Hot', 'md' => 'Warm', 'lo' => 'Cold'][$level] ?? 'Warm';
    return '<span class="score ' . $level . '">' . $label . ' · ' . $n . '</span>';
}

/** The "Join N founders" line, or the batch line when the counter is hidden. */
function waitlist_counter_html(): string {
    $n = waitlist_display_count();
    if ($n === null) return '<p class="wl-count"><span class="dot"></span>Early access opens in batches. <b>Waitlist goes first.</b></p>';
    return '<p class="wl-count"><span class="dot"></span>Join <b data-wl-count>' . number_format($n) . '</b> founders who\'d rather not answer “price?” by hand.</p>';
}

/** Two-step waitlist form. Step 1: email or Google. Step 2: name, business, phone, channels, consent. */
function waitlist_form(string $id, bool $counter = true): string {
    $g = google_client_id();
    ob_start(); ?>
<div class="wl" data-wl id="<?= e($id) ?>">
  <form class="wl-s1" novalidate>
    <div class="wl-row">
      <label class="sr" for="<?= e($id) ?>-email">Your email</label>
      <input id="<?= e($id) ?>-email" name="email" type="email" required autocomplete="email" inputmode="email" placeholder="you@yourbrand.com">
      <button class="btn btn-p" type="submit">Join the waitlist <span class="arr">→</span></button>
    </div>
    <div class="hp" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div>
    <input type="hidden" name="t" value="<?= time() ?>">
    <?php if ($g): ?><div class="wl-or"><span>or</span></div><div class="wl-google" data-google></div><?php endif; ?>
    <small class="wl-msg" role="status"></small>
  </form>
  <form class="wl-s2" hidden novalidate>
    <div class="wl-s2-head"><b>Almost in.</b> <span>Ten more seconds. Promise.</span></div>
    <div class="wl-grid">
      <div><label for="<?= e($id) ?>-name">Name</label><input id="<?= e($id) ?>-name" name="name" required autocomplete="name" placeholder="What should we call you?"></div>
      <div><label for="<?= e($id) ?>-co">Business <em>optional</em></label><input id="<?= e($id) ?>-co" name="company" autocomplete="organization" placeholder="Your brand"></div>
      <div class="full"><label for="<?= e($id) ?>-ph">WhatsApp number <em>optional</em></label><input id="<?= e($id) ?>-ph" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="With country code, e.g. +44 7700 900123"></div>
      <fieldset class="full"><legend>Where do customers message you?</legend>
        <div class="wl-chips"><?php foreach (WAITLIST_CHANNELS as $k => $v): ?><label><input type="checkbox" name="channels[]" value="<?= $k ?>"><span><?= channel_icon($k) ?><?= e($v) ?></span></label><?php endforeach; ?></div>
      </fieldset>
      <label class="wl-consent full"><input type="checkbox" name="consent" value="1" required><span>Keep me posted about my access and launch updates by email or WhatsApp. Unsubscribe anytime. <a href="/privacy#waitlist">Privacy</a></span></label>
      <label class="wl-consent full"><input type="checkbox" name="marketing" value="1"><span>Also send me product news, tips and offers <em>(optional)</em>, and let FiguredOutAI use my email to show me relevant ads. <a href="/privacy#marketing">How</a></span></label>
    </div>
    <div class="wl-send"><button class="btn btn-p" type="submit">Lock my spot <span class="arr">→</span></button><small class="wl-msg" role="status"></small></div>
  </form>
  <div class="wl-done" hidden role="status">
    <b class="wl-done-h">You're in.</b>
    <p class="wl-done-p">Check your inbox (and, knowing email, your spam folder).</p>
    <button type="button" class="btn btn-g btn-sm" data-share>Copy invite link for a founder friend</button>
  </div>
  <?= $counter ? waitlist_counter_html() : '' ?>
</div>
<?php return (string)ob_get_clean();
}

/** Google sign-in config for site.js (only when a Client ID is saved). */
function waitlist_head_js(): string {
    $g = google_client_id();
    return $g ? '<script>window.__WL_GOOGLE=' . json_encode($g) . ';</script>' : '';
}

/**
 * Hero: copy on the left, the live inbox on the right with the 3D mark rising
 * behind it (scene.js anchors the mark to #logoAnchor). Campaign pages can pass
 * their own kicker, title (HTML) and lead (HTML).
 */
function product_hero(array $o = []): void {
    $o += [
        'h' => 'h1',
        'kicker' => 'WhatsApp · Instagram · Messenger',
        'title' => 'An AI inbox that <span class="soft">handles itself.</span>',
        'lead' => "Your customers DM you on three apps. FiguredOutAI answers all of them, figures out who's actually buying, and sends the rest where they belong. <strong>You get your evenings back.</strong>",
        'form' => 'wl-hero',
    ];
    $h = $o['h'] === 'h2' ? 'h2' : 'h1'; ?>
<section class="hero p-hero" id="top">
  <div class="wrap p-hero-grid">
    <div class="p-hero-copy">
      <a href="#platform" class="pill enter" style="--i:0"><b>EARLY ACCESS</b><span class="dot"></span> <?= e($o['kicker']) ?> <span class="arr">→</span></a>
      <<?= $h ?> class="split hero-title" id="h1"><?= $o['title'] ?></<?= $h ?>>
      <p class="lead"><?= $o['lead'] ?></p>
      <div><?= waitlist_form($o['form']) ?></div>
    </div>
    <div class="hero-stage mock-stage enter" style="--i:2" id="mockStage">
      <div class="logo-anchor" id="logoAnchor" aria-hidden="true"></div>
      <div class="mock" id="mock" role="img" aria-label="Preview of the FiguredOutAI inbox with demo chats from WhatsApp, Instagram and Messenger, each scored hot, warm or cold">
        <div class="mock-bar"><i></i><i></i><i></i><span>FiguredOutAI · Inbox</span><em>Demo data</em></div>
        <div class="mock-body">
          <div class="mock-side" aria-hidden="true">
            <div class="on">All chats <em id="cnt">128</em></div>
            <div><?= channel_icon('whatsapp') ?>WhatsApp <em>61</em></div>
            <div><?= channel_icon('instagram') ?>Instagram <em>44</em></div>
            <div><?= channel_icon('messenger') ?>Messenger <em>23</em></div>
            <hr>
            <div>Hot leads <em>46</em></div>
            <div>Needs you <em>7</em></div>
          </div>
          <div class="mock-main" aria-hidden="true">
            <div class="mock-head"><span>Live · handled by AI</span><span>Hot leads first</span></div>
            <div class="feed" id="feed"></div>
          </div>
        </div>
      </div>
      <div class="float-chip fc1" aria-hidden="true"><span>FIRST REPLY</span><b class="c">&lt; 5 sec</b></div>
      <div class="float-chip fc2" aria-hidden="true"><span>ROUTED TO SALES</span><b class="o">Hot · 94</b></div>
    </div>
  </div>
</section>
<?php }

function product_channels(): void { ?>
<div class="chan-sec">
  <div class="wrap chan-wrap">
    <div class="chan-copy reveal"><div class="mono chan-k">Plugs into</div><p>The three apps your customers actually use. <span>More once these three behave.</span></p></div>
    <div class="chan-tiles reveal">
      <div class="tile t-wa"><?= channel_icon('whatsapp') ?><span>WhatsApp</span></div>
      <div class="tile t-ig"><?= channel_icon('instagram') ?><span>Instagram</span></div>
      <div class="tile t-ms"><?= channel_icon('messenger') ?><span>Messenger</span></div>
    </div>
  </div>
</div>
<?php }

/** The product in four cards, each with a live piece of the inbox inside (2x2, soft brand glow underneath). */
function product_features(): void { ?>
<section id="platform" class="p-feats">
  <div class="wrap">
    <div class="sec-head center reveal">
      <div class="eyebrow">The product</div>
      <h2>Your DMs, <span class="soft">on autopilot.</span></h2>
      <p>Built for brands that sell in the DMs and are tired of living there.</p>
    </div>

    <div class="pgrid">
      <article class="bcard tilt reveal">
        <span class="pf-tag">01 · One inbox</span>
        <h3>Three apps. One tab. <span class="soft">Finally.</span></h3>
        <p>WhatsApp, Instagram and Messenger land in one feed. No more “wait, where did they message us?”</p>
        <div class="bvis"><div class="pv">
          <div class="pv-row"><?= channel_icon('whatsapp') ?><div><b>Riya</b><span>is the black one back in M?</span></div><em>now</em></div>
          <div class="pv-row"><?= channel_icon('instagram') ?><div><b>@thrift.with.sam</b><span>price??</span></div><em>1m</em></div>
          <div class="pv-row"><?= channel_icon('messenger') ?><div><b>Arjun</b><span>do you ship to Pune</span></div><em>3m</em></div>
        </div></div>
      </article>

      <article class="bcard tilt reveal">
        <span class="pf-tag">02 · Replies itself</span>
        <h3>Answers in seconds. Sounds like you, <span class="soft">not a bot.</span></h3>
        <p>It learns your products, prices and tone, then replies at 2 AM so you don't have to.</p>
        <div class="bvis"><div class="pv pv-chat">
          <div class="bub in">hey is the black one back in stock</div>
          <div class="bub out">It is! M or L? Sending the link before it sells out again.</div>
          <div class="pv-meta mono"><span class="dot"></span>Sent by AI · 3 sec · your tone</div>
        </div></div>
      </article>

      <article class="bcard tilt reveal">
        <span class="pf-tag">03 · Reads intent</span>
        <h3>Knows who's buying and who's <span class="soft">just vibing.</span></h3>
        <p>Every chat gets a score. Ready-to-buy goes to the top. “Nice pic” goes where it belongs.</p>
        <div class="bvis"><div class="pv">
          <div class="pv-row"><?= channel_icon('whatsapp') ?><div><b>Wants 40 units</b><span>for a corporate event</span></div><?= score_chip('hi', 94) ?></div>
          <div class="pv-row"><?= channel_icon('instagram') ?><div><b>“price?”</b><span>third time this week</span></div><?= score_chip('md', 58) ?></div>
          <div class="pv-row"><?= channel_icon('messenger') ?><div><b>“nice pic”</b><span>that's it, that's the message</span></div><?= score_chip('lo', 12) ?></div>
        </div></div>
      </article>

      <article class="bcard tilt reveal">
        <span class="pf-tag">04 · Routes it</span>
        <h3>Right chat, right person. <span class="soft">No screenshots forwarded.</span></h3>
        <p>Sales to sales, support to support, the weird ones to you. Every AI decision is logged, and you can jump into any chat.</p>
        <div class="bvis"><div class="pv pv-route">
          <div><span>Bulk order</span><i></i><b class="c">Sales</b></div>
          <div><span>Order not delivered</span><i></i><b>Support</b></div>
          <div><span>Collab request</span><i></i><b class="o">You, later</b></div>
        </div></div>
      </article>
    </div>

    <div class="wlabel reveal">
      <div><div class="eyebrow">For agencies</div><h3>Agency? Sell it <span class="soft">as yours.</span></h3><p>Your logo, your pricing, our AI doing the work.</p></div>
      <a href="#contact" class="btn btn-g magnetic" data-interest="White-label partnership">Talk white-label <span class="arr">→</span></a>
    </div>
  </div>
</section>
<?php }

function product_join(array $o = []): void {
    $o += ['title' => "Your inbox won't fix itself. <span class=\"soft\">Ours will.</span>",
           'lead' => 'Join the waitlist. We let people in batch by batch, and the early ones get the attention.']; ?>
<section id="join" class="cta join">
  <div class="wrap">
    <div class="cta-box reveal">
      <div class="eyebrow">Early access</div>
      <h2><?= $o['title'] ?></h2>
      <p><?= $o['lead'] ?></p>
      <?= waitlist_form('wl-join') ?>
    </div>
  </div>
</section>
<?php }

function product_jsonld(): array {
    return ['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => 'FiguredOutAI', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web',
        'description' => 'An AI inbox for WhatsApp, Instagram and Facebook Messenger that replies to customers, scores intent and routes every chat to the right person.',
        'url' => url('/'), 'publisher' => ['@id' => url('/#organization')]];
}
