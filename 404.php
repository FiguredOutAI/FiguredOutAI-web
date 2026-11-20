<?php
if (!function_exists('cfg')) require_once __DIR__ . '/app/lib.php';
if (!function_exists('page_open')) require_once __DIR__ . '/app/layout.php';
http_response_code(404);
page_open(['title' => 'Page not found | FiguredOutAI', 'description' => 'This page does not exist.', 'noindex' => true, 'canonical' => url('404')], ['css' => ['css/blog.css']]);
$recent = array_slice(live_posts(), 0, 3);
?>
<main id="main" class="blog-main">
  <section class="blog-hero" style="padding-bottom:100px">
    <div class="wrap">
      <div class="eyebrow">Error 404</div>
      <h1>This page hasn't been <span class="soft">figured out yet</span></h1>
      <p class="blog-lead">The link may be old or mistyped. Try the homepage or our latest writing.</p>
      <div class="cta-row" style="margin-top:32px"><a class="btn btn-p" href="/">Go home</a><a class="btn btn-g" href="/blog/">Read the blog</a></div>
      <?php if ($recent): ?>
      <div class="post-grid" style="margin-top:72px">
        <?php foreach ($recent as $p): ?>
        <a class="post-card" href="/blog/<?= e($p['slug']) ?>"><div class="pc-body"><div class="pc-meta mono"><?= e($p['tags'][0] ?? 'Insight') ?></div><h3><?= e($p['title']) ?></h3></div></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php page_close();
