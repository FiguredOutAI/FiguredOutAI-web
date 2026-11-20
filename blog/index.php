<?php
require dirname(__DIR__) . '/app/lib.php';
require dirname(__DIR__) . '/app/layout.php';

$tag = isset($_GET['tag']) && valid_slug((string)$_GET['tag']) ? (string)$_GET['tag'] : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$posts = live_posts($tag);
$tags = all_tags();
if ($tag && !isset($tags[$tag])) { http_response_code(404); require dirname(__DIR__) . '/404.php'; exit; }

$per = (int)cfg('posts_per_page', 9);
$pages = max(1, (int)ceil(count($posts) / $per));
if ($page > $pages) { http_response_code(404); require dirname(__DIR__) . '/404.php'; exit; }
$slice = array_slice($posts, ($page - 1) * $per, $per);

$base = $tag ? "/blog/tag/$tag" : '/blog';
$pageUrl = fn($n) => $n <= 1 ? "$base/" : "$base/page/$n";
$tagName = $tag ? $tags[$tag]['name'] : null;
$title = $tag ? "$tagName articles | FiguredOutAI Blog" : 'Blog: AI agents, automation & infrastructure | FiguredOutAI';
if ($page > 1) $title = "Page $page · " . $title;
$desc = $tag ? "Articles on $tagName from FiguredOutAI: practical guides on building and running AI in business."
             : 'Practical writing on AI agents, workflow automation, AI infrastructure and AI-driven sales, from the team at FiguredOutAI.';

$extra = '';
if ($page > 1) $extra .= '<link rel="prev" href="' . e(url(ltrim($pageUrl($page - 1), '/'))) . "\">\n";
if ($page < $pages) $extra .= '<link rel="next" href="' . e(url(ltrim($pageUrl($page + 1), '/'))) . "\">\n";

page_open([
    'title' => $title, 'description' => $desc, 'noindex' => $tag && (int)$tags[$tag]['count'] < 3, // thin tag pages: noindex,follow 'canonical' => url(ltrim($pageUrl($page), '/')), 'extra' => $extra, 'og_kicker' => 'Blog',
    'jsonld' => [
        ['@context' => 'https://schema.org', '@type' => 'Blog', 'name' => 'FiguredOutAI Blog', 'url' => url('blog/'), 'publisher' => org_jsonld(),
         'blogPost' => array_map(fn($p) => ['@type' => 'BlogPosting', 'headline' => $p['title'], 'url' => post_url($p), 'datePublished' => date('c', $p['ts'])], $slice)],
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => url('blog/')],
            $tag ? ['@type' => 'ListItem', 'position' => 3, 'name' => $tagName, 'item' => url("blog/tag/$tag/")] : null]))],
    ],
], ['css' => ['css/blog.css']]);
?>
<main id="main" class="blog-main">
  <section class="blog-hero">
    <div class="wrap">
      <nav class="crumbs mono" aria-label="Breadcrumb"><a href="/">Home</a><span>/</span><?php if ($tag): ?><a href="/blog/">Blog</a><span>/</span><span><?= e($tagName) ?></span><?php else: ?><span>Blog</span><?php endif; ?></nav>
      <h1><?php if ($tag): ?><?= e($tagName) ?><?php else: ?>Insights on building with <span class="soft">AI</span><?php endif; ?></h1>
      <p class="blog-lead"><?= e($desc) ?></p>
      <?php if ($tags): ?>
      <div class="chips" role="list">
        <a role="listitem" class="chip<?= !$tag ? ' on' : '' ?>" href="/blog/">All</a>
        <?php foreach ($tags as $slug => $t): ?><a role="listitem" class="chip<?= $tag === $slug ? ' on' : '' ?>" href="/blog/tag/<?= e($slug) ?>/"><?= e($t['name']) ?> <em><?= (int)$t['count'] ?></em></a><?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="blog-list">
    <div class="wrap">
      <?php if (!$slice): ?>
        <div class="empty"><h2>First articles are on the way.</h2><p>Subscribe via <a href="/feed.xml" class="c">RSS</a> or check back soon.</p></div>
      <?php else: ?>
        <?php if ($page === 1 && !$tag): $f = array_shift($slice); ?>
        <a class="feature-post reveal" href="/blog/<?= e($f['slug']) ?>">
          <div class="pc-img"><?php if ($f['cover']): ?><img src="<?= e($f['cover']) ?>" alt="<?= e($f['cover_alt']) ?>" fetchpriority="high"><?php else: ?><span class="pc-ph" aria-hidden="true"></span><?php endif; ?></div>
          <div class="fp-body">
            <div class="pc-meta mono">Latest · <?= e($f['tags'][0] ?? 'Insight') ?> · <?= reading_time($f['content']) ?> min read</div>
            <h2><?= e($f['title']) ?></h2>
            <p><?= e(excerpt($f, 220)) ?></p>
            <span class="read mono">Read article →</span>
          </div>
        </a>
        <?php endif; ?>
        <div class="post-grid">
          <?php foreach ($slice as $i => $p): ?>
          <a class="post-card reveal d<?= ($i % 3) + 1 ?>" href="/blog/<?= e($p['slug']) ?>">
            <div class="pc-img"><?php if ($p['cover']): ?><img src="<?= e($p['cover']) ?>" alt="<?= e($p['cover_alt']) ?>" loading="lazy" decoding="async"><?php else: ?><span class="pc-ph" aria-hidden="true"></span><?php endif; ?></div>
            <div class="pc-body">
              <div class="pc-meta mono"><?= e($p['tags'][0] ?? 'Insight') ?> · <?= reading_time($p['content']) ?> min read</div>
              <h3><?= e($p['title']) ?></h3>
              <p><?= e(excerpt($p, 140)) ?></p>
              <time class="pc-date mono" datetime="<?= date('c', $p['ts']) ?>"><?= fmt_date($p['ts']) ?></time>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Pagination">
          <?php if ($page > 1): ?><a class="btn btn-g btn-sm" href="<?= e($pageUrl($page - 1)) ?>" rel="prev">← Newer</a><?php endif; ?>
          <span class="mono">Page <?= $page ?> of <?= $pages ?></span>
          <?php if ($page < $pages): ?><a class="btn btn-g btn-sm" href="<?= e($pageUrl($page + 1)) ?>" rel="next">Older →</a><?php endif; ?>
        </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="blog-cta">
    <div class="wrap">
      <div class="mini-cta reveal">
        <div><h2>Have a process worth <span class="soft">automating?</span></h2><p>Tell us about it. We'll show you what AI can take off your team's plate.</p></div>
        <a href="/#contact" class="btn btn-p">Start a project</a>
      </div>
    </div>
  </section>
</main>
<?php page_close();
