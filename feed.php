<?php
require __DIR__ . '/app/lib.php';
header('Content-Type: application/rss+xml; charset=utf-8');
header('X-Robots-Tag: noindex');
$posts = array_slice(live_posts(), 0, 30);
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
  <title>FiguredOutAI Blog</title>
  <link><?= e(url('blog/')) ?></link>
  <atom:link href="<?= e(url('feed.xml')) ?>" rel="self" type="application/rss+xml"/>
  <description>Practical writing on AI agents, automation, AI infrastructure and AI-driven sales.</description>
  <language>en</language>
  <lastBuildDate><?= date(DATE_RSS, $posts[0]['ts'] ?? time()) ?></lastBuildDate>
  <image><url><?= e(url('assets/img/icon-192.png')) ?></url><title>FiguredOutAI Blog</title><link><?= e(url('blog/')) ?></link></image>
<?php foreach ($posts as $p): $html = md_to_html($p['content']); ?>
  <item>
    <title><?= e($p['title']) ?></title>
    <link><?= e(post_url($p)) ?></link>
    <guid isPermaLink="true"><?= e(post_url($p)) ?></guid>
    <pubDate><?= date(DATE_RSS, $p['ts']) ?></pubDate>
    <description><?= e(excerpt($p, 300)) ?></description>
<?php foreach ($p['tags'] as $t): ?>    <category><?= e($t) ?></category>
<?php endforeach; ?>
    <content:encoded><![CDATA[<?= str_replace(']]>', ']]&gt;', $html) ?><p>Originally published at <a href="<?= e(post_url($p)) ?>">FiguredOutAI</a>.</p>]]></content:encoded>
  </item>
<?php endforeach; ?>
</channel>
</rss>
