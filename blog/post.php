<?php
require dirname(__DIR__) . '/app/lib.php';
require dirname(__DIR__) . '/app/layout.php';

$slug = (string)($_GET['slug'] ?? '');
$post = get_post($slug);
$preview = false;
if ($post && !is_live($post)) {
    // Admins can preview drafts and scheduled posts.
    if (is_admin()) $preview = true; else $post = null;
}
if (!$post) {
    $redirects = json_read(CONTENT . '/redirects.json', []);
    if (isset($redirects[$slug]) && valid_slug($redirects[$slug])) { header('Location: /blog/' . $redirects[$slug], true, 301); exit; }
}
if (!$post) { http_response_code(404); require dirname(__DIR__) . '/404.php'; exit; }

$toc = [];
$html = md_to_html($post['content'], $toc);
$url = post_url($post);
$canon = $post['canonical'] ?: $url;
$img = $post['cover'] ?: og_image_url($post['title'], 'Blog' . (!empty($post['tags'][0]) ? ' · ' . $post['tags'][0] : ''));
$updated = $post['updated'] ? strtotime($post['updated']) : $post['ts'];
$related = related_posts($post, 3);
$words = word_count($post['content']);

page_open([
    'title' => $post['title'] . ' | FiguredOutAI',
    'description' => excerpt($post, 158),
    'canonical' => $canon, 'type' => 'article', 'image' => $img,
    'published' => date('c', $post['ts']), 'modified' => date('c', $updated),
    'noindex' => $preview || !empty($post['noindex']),
    'jsonld' => [
        ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['title'], 'description' => excerpt($post, 158),
         'image' => [str_starts_with($img, '/') ? url($img) : $img], 'datePublished' => date('c', $post['ts']), 'dateModified' => date('c', $updated),
         'wordCount' => $words, 'keywords' => implode(', ', $post['tags']),
         'author' => ['@type' => str_contains(strtolower($post['author']), 'team') ? 'Organization' : 'Person', 'name' => $post['author']],
         'publisher' => ['@type' => 'Organization', 'name' => 'FiguredOutAI', 'logo' => ['@type' => 'ImageObject', 'url' => url('assets/img/icon-512.png')]],
         'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url]],
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => url('blog/')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $url]]],
    ],
], ['css' => ['css/blog.css']]);
$share = rawurlencode($url); $st = rawurlencode($post['title']);
?>
<div class="readprog" aria-hidden="true"><i id="readprog"></i></div>
<main id="main" class="blog-main">
  <?php if ($preview): ?><div class="preview-bar mono">Preview · <?= $post['status'] === 'draft' ? 'Draft' : 'Scheduled for ' . e(date('j M Y, H:i', $post['ts'])) ?> · <a href="/admin/edit.php?slug=<?= e($post['slug']) ?>">Edit</a></div><?php endif; ?>
  <article class="article">
    <header class="art-head wrap">
      <nav class="crumbs mono" aria-label="Breadcrumb"><a href="/">Home</a><span>/</span><a href="/blog/">Blog</a><?php if ($post['tags']): ?><span>/</span><a href="/blog/tag/<?= e(tag_slug($post['tags'][0])) ?>/"><?= e($post['tags'][0]) ?></a><?php endif; ?></nav>
      <h1><?= e($post['title']) ?></h1>
      <?php if ($post['description']): ?><p class="art-dek"><?= e($post['description']) ?></p><?php endif; ?>
      <div class="art-meta mono">
        <span><?= e($post['author']) ?></span><span>·</span>
        <time datetime="<?= date('c', $post['ts']) ?>"><?= fmt_date($post['ts']) ?></time><span>·</span>
        <span><?= reading_time($post['content']) ?> min read</span>
        <?php if ($updated > $post['ts'] + 86400): ?><span>·</span><span>Updated <?= fmt_date($updated) ?></span><?php endif; ?>
      </div>
    </header>
    <?php if ($post['cover']): ?>
    <figure class="art-cover wrap"><img src="<?= e($post['cover']) ?>" alt="<?= e($post['cover_alt']) ?>" fetchpriority="high"></figure>
    <?php endif; ?>

    <div class="art-grid wrap">
      <aside class="art-side">
        <?php if (count($toc) >= 3): ?>
        <nav class="toc" aria-label="On this page">
          <div class="mono">On this page</div>
          <ol><?php foreach ($toc as $t): ?><li class="l<?= $t['level'] ?>"><a href="#<?= e($t['id']) ?>"><?= e($t['text']) ?></a></li><?php endforeach; ?></ol>
        </nav>
        <?php endif; ?>
        <div class="share">
          <div class="mono">Share</div>
          <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $share ?>" target="_blank" rel="noopener">LinkedIn</a>
          <a href="https://twitter.com/intent/tweet?url=<?= $share ?>&amp;text=<?= $st ?>" target="_blank" rel="noopener">X</a>
          <a href="https://wa.me/?text=<?= $st ?>%20<?= $share ?>" target="_blank" rel="noopener">WhatsApp</a>
          <button type="button" data-copy="<?= e($url) ?>">Copy link</button>
        </div>
      </aside>
      <div class="prose"><?= $html ?>
        <?php if ($post['tags']): ?>
        <div class="art-tags"><?php foreach ($post['tags'] as $t): ?><a class="chip" href="/blog/tag/<?= e(tag_slug($t)) ?>/"><?= e($t) ?></a><?php endforeach; ?></div>
        <?php endif; ?>
        <div class="author-box">
          <img src="/assets/img/icon-192.png" alt="" width="56" height="56" loading="lazy">
          <div><b><?= e($post['author']) ?></b><p>FiguredOutAI designs, builds and runs AI systems for businesses worldwide: agents, automation, custom software and AI infrastructure.</p></div>
        </div>
        <div class="mini-cta">
          <div><h2>Want this working <span class="soft">in your business?</span></h2><p>Book a free discovery call. We'll map where AI pays back fastest for you.</p></div>
          <a href="/#contact" class="btn btn-p">Talk to us</a>
        </div>
      </div>
    </div>
  </article>

  <?php if ($related): ?>
  <section class="related">
    <div class="wrap">
      <div class="eyebrow">Keep reading</div>
      <div class="post-grid">
        <?php foreach ($related as $p): ?>
        <a class="post-card" href="/blog/<?= e($p['slug']) ?>">
          <div class="pc-img"><?php if ($p['cover']): ?><img src="<?= e($p['cover']) ?>" alt="<?= e($p['cover_alt']) ?>" loading="lazy" decoding="async"><?php else: ?><span class="pc-ph" aria-hidden="true"></span><?php endif; ?></div>
          <div class="pc-body"><div class="pc-meta mono"><?= e($p['tags'][0] ?? 'Insight') ?> · <?= reading_time($p['content']) ?> min read</div><h3><?= e($p['title']) ?></h3></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
</main>
<?php page_close();
