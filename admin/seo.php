<?php
require __DIR__ . '/_inc.php';
require_admin();
$bfile = CONTENT . '/backlinks.json';
$data = json_read($bfile, ['links' => [], 'done' => []]);
$data += ['links' => [], 'done' => []];

// Legitimate places an AI company can earn a profile link or a mention.
$sources = [
  'Foundations' => [
    ['Google Business Profile', 'https://business.google.com/', 'Local trust signal and a Maps listing.'],
    ['Bing Places', 'https://www.bingplaces.com/', 'Bing and Copilot local results.'],
    ['LinkedIn Company Page', 'https://www.linkedin.com/company/setup/new/', 'Add the website and post every article.'],
    ['Crunchbase', 'https://www.crunchbase.com/add-new', 'Company profile that journalists and investors check.'],
    ['GitHub organisation', 'https://github.com/organizations/plan', 'Link the site from the org profile and public repos.'],
    ['Startup India (DPIIT)', 'https://www.startupindia.gov.in/', 'Government startup listing for Indian entities.'],
  ],
  'Agency directories' => [
    ['Clutch', 'https://clutch.co/get-listed', 'The main B2B services directory. Reviews matter here.'],
    ['GoodFirms', 'https://www.goodfirms.co/', 'AI and software development category listings.'],
    ['DesignRush', 'https://www.designrush.com/agency/registration', 'Agency directory with AI categories.'],
    ['Sortlist', 'https://www.sortlist.com/', 'Agency marketplace, strong in Europe.'],
    ['TechBehemoths', 'https://techbehemoths.com/', 'Free IT company listing.'],
    ['The Manifest', 'https://themanifest.com/', 'Sister site of Clutch, pulls from the same profile.'],
  ],
  'Product launch (Unified AI Sales Platform)' => [
    ['Product Hunt', 'https://www.producthunt.com/posts/new', 'Launch day traffic plus a permanent product page.'],
    ['G2', 'https://sell.g2.com/', 'Software reviews in CRM and sales categories.'],
    ['Capterra / GetApp', 'https://www.capterra.com/vendors/sign-up', 'Gartner software directories.'],
    ['SaaSHub', 'https://www.saashub.com/', 'Free SaaS listing and alternatives pages.'],
    ['AlternativeTo', 'https://alternativeto.net/', 'Get listed as an alternative to bigger tools.'],
    ["There's An AI For That", 'https://theresanaiforthat.com/', 'The largest AI tools directory.'],
    ['Futurepedia', 'https://www.futurepedia.io/', 'AI tools directory.'],
  ],
  'Content & PR' => [
    ['Medium', 'https://medium.com/p/import', 'Import posts. Medium sets the canonical link to your site.'],
    ['Dev.to', 'https://dev.to/new', 'Technical posts, with canonical_url pointing home.'],
    ['Hashnode', 'https://hashnode.com/', 'Developer blog, supports canonical URLs.'],
    ['Qwoted', 'https://www.qwoted.com/', 'Journalist requests for expert quotes.'],
    ['Featured', 'https://featured.com/', 'Answer expert questions that get published with links.'],
    ['Help a B2B Writer', 'https://helpab2bwriter.com/', 'B2B journalists looking for sources.'],
    ['Inc42 / YourStory', 'https://yourstory.com/', 'Indian startup press. Pitch launches and milestones.'],
  ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        $src = trim((string)($_POST['source'] ?? ''));
        if (filter_var($src, FILTER_VALIDATE_URL)) {
            array_unshift($data['links'], ['id' => bin2hex(random_bytes(5)), 'source' => $src, 'target' => mb_substr(trim((string)$_POST['target']), 0, 300),
                'anchor' => mb_substr(trim((string)$_POST['anchor']), 0, 120), 'type' => mb_substr((string)$_POST['type'], 0, 30),
                'status' => in_array($_POST['status'] ?? '', ['planned', 'requested', 'live', 'lost'], true) ? $_POST['status'] : 'planned',
                'rel' => in_array($_POST['rel'] ?? '', ['dofollow', 'nofollow', 'unknown'], true) ? $_POST['rel'] : 'unknown',
                'date' => date('Y-m-d'), 'notes' => mb_substr(trim((string)$_POST['notes']), 0, 500)]);
            flash('Backlink added.');
        } else flash('Enter a valid source URL (the page that links to you).', 'err');
    } elseif ($a === 'status' || $a === 'delete') {
        foreach ($data['links'] as $i => $l) if ($l['id'] === ($_POST['id'] ?? '')) {
            if ($a === 'delete') array_splice($data['links'], $i, 1);
            elseif (in_array($_POST['status'] ?? '', ['planned', 'requested', 'live', 'lost'], true)) $data['links'][$i]['status'] = $_POST['status'];
            break;
        }
    } elseif ($a === 'toggle') {
        $k = substr((string)($_POST['key'] ?? ''), 0, 80);
        if (isset($data['done'][$k])) unset($data['done'][$k]); else $data['done'][$k] = date('Y-m-d');
    } elseif ($a === 'ping') {
        $urls = array_merge([url('/'), url('about'), url('blog/'), url('sitemap.xml'), url('llms.txt')], array_map('post_url', array_slice(live_posts(), 0, 9000)));
        flash(indexnow_ping($urls) ? 'Sent ' . count($urls) . ' URLs to IndexNow.' : 'IndexNow ping failed or skipped (it only works on the live domain). See the log below.', 'ok');
    }
    json_write($bfile, $data);
    redirect('/admin/seo.php' . ($a === 'toggle' ? '#sources' : ''));
}

$links = $data['links'];
$live = array_filter($links, fn($l) => $l['status'] === 'live');
$domains = count(array_unique(array_map(fn($l) => preg_replace('~^www\.~', '', (string)parse_url($l['source'], PHP_URL_HOST)), $live)));
$posts = live_posts();
// Internal links: which posts no other post links to (orphans)?
$inbound = array_fill_keys(array_column($posts, 'slug'), 0);
foreach ($posts as $p) if (preg_match_all('~\]\((?:https?://[^)]*?)?/blog/([a-z0-9-]+)/?\)~', $p['content'], $m)) foreach (array_unique($m[1]) as $s) if (isset($inbound[$s]) && $s !== $p['slug']) $inbound[$s]++;
$synd = isset($_GET['syndicate']) ? get_post((string)$_GET['syndicate']) : ($posts[0] ?? null);
$log = json_read(CONTENT . '/indexnow_log.json', []);
$totalSrc = array_sum(array_map('count', $sources));

admin_open('SEO & backlinks', 'seo.php');
?>
<div class="a-head"><h1>SEO & backlinks</h1></div>
<div class="a-stats">
  <div><b><?= count($live) ?></b><span>Live backlinks</span></div>
  <div><b><?= $domains ?></b><span>Referring domains</span></div>
  <div><b><?= count($data['done']) ?>/<?= $totalSrc ?></b><span>Listings completed</span></div>
  <div><b><?= count(array_filter($inbound, fn($n) => $n === 0)) ?></b><span>Orphan posts</span></div>
</div>

<section class="a-card note">
  <b>How links are earned here.</b> Search engines reward links from real, relevant sites. This page helps you build them in ways that hold up: directory and profile listings, republishing your articles with a link back, and expert quotes in the press. Avoid buying links, link farms and automated link networks. Google penalises them and they can remove a site from results.
</section>

<section class="a-card">
  <div class="a-card-h"><h2>1 · Get indexed</h2></div>
  <ol class="steps-list">
    <li>Add the site to <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a> and paste the verification code in <a href="/admin/settings.php">Settings</a>. Then submit <code><?= e(url('sitemap.xml')) ?></code>.</li>
    <li>Add the site to <a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">Bing Webmaster Tools</a> (you can import from Search Console). Bing also powers ChatGPT search and Copilot.</li>
    <li>IndexNow is on. Each time you publish, Bing, Yandex, Seznam and Naver are told straight away. Scheduled posts are announced by the hourly cron job (see Settings).</li>
  </ol>
  <form method="post" class="inline"><?= csrf_field() ?><button class="a-btn" name="action" value="ping">Ping all URLs via IndexNow now</button></form>
  <?php if ($log): ?><details class="adv"><summary>IndexNow log (last <?= count($log) ?>)</summary><ul class="log"><?php foreach (array_slice($log, 0, 10) as $l): ?><li><code><?= e(date('j M H:i', strtotime($l['at']))) ?></code> <?= e($l['status']) ?> · <?= count($l['urls']) ?> URLs</li><?php endforeach; ?></ul></details><?php endif; ?>
</section>

<section class="a-card" id="sources">
  <div class="a-card-h"><h2>2 · Listings and profiles to claim</h2><span class="a-muted">Tick each one once your profile is live with a link to figuredoutai.com.</span></div>
  <?php foreach ($sources as $group => $items): ?>
  <h3 class="grp"><?= e($group) ?></h3>
  <div class="src-grid">
    <?php foreach ($items as [$name, $href, $why]): $k = slugify($name); $done = isset($data['done'][$k]); ?>
    <div class="src <?= $done ? 'done' : '' ?>">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="key" value="<?= e($k) ?>"><button name="action" value="toggle" class="tick" aria-label="Mark <?= e($name) ?> done"><?= $done ? '✓' : '' ?></button></form>
      <div><a href="<?= e($href) ?>" target="_blank" rel="noopener"><b><?= e($name) ?></b> ↗</a><span><?= e($why) ?></span><?php if ($done): ?><small>Done <?= e($data['done'][$k]) ?></small><?php endif; ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</section>

<section class="a-card" id="syndicate">
  <div class="a-card-h"><h2>3 · Syndication kit</h2><span class="a-muted">Republish each article elsewhere with a canonical link back to the original.</span></div>
  <?php if (!$synd): ?><p class="a-muted">Publish a post first.</p><?php else: $pu = post_url($synd); $ex = excerpt($synd, 220); ?>
  <form method="get" class="inline"><label>Article <select name="syndicate" onchange="this.form.submit()"><?php foreach ($posts as $p): ?><option value="<?= e($p['slug']) ?>" <?= $p['slug'] === $synd['slug'] ? 'selected' : '' ?>><?= e($p['title']) ?></option><?php endforeach; ?></select></label><noscript><button class="a-btn">Load</button></noscript></form>
  <div class="kit">
    <div><h4>LinkedIn post</h4><textarea readonly rows="7"><?= e($synd['title'] . "\n\n" . $ex . "\n\nRead the full piece: " . $pu . "\n\n" . implode(' ', array_map(fn($t) => '#' . preg_replace('~[^A-Za-z0-9]~', '', $t), $synd['tags']))) ?></textarea><button type="button" class="a-btn copy">Copy</button></div>
    <div><h4>X / Twitter</h4><textarea readonly rows="7"><?= e(mb_strimwidth($synd['title'], 0, 200, '…') . "\n\n" . $pu) ?></textarea><button type="button" class="a-btn copy">Copy</button></div>
    <div><h4>Dev.to / Hashnode front matter</h4><textarea readonly rows="7"><?= e("---\ntitle: \"" . str_replace('"', '\"', $synd['title']) . "\"\ncanonical_url: " . $pu . "\ntags: " . implode(', ', array_slice(array_map(fn($t) => strtolower(preg_replace('~[^A-Za-z0-9]~', '', $t)), $synd['tags']), 0, 4)) . "\n---\n\n*Originally published at [FiguredOutAI](" . $pu . ").*\n\n" . $synd['content']) ?></textarea><button type="button" class="a-btn copy">Copy</button></div>
    <div><h4>Medium</h4><p class="a-muted small">Use Medium's import tool. It copies the article and sets the canonical link to your site automatically.</p><a class="a-btn" target="_blank" rel="noopener" href="https://medium.com/p/import">Open Medium import ↗</a><input readonly value="<?= e($pu) ?>" class="mt"><button type="button" class="a-btn copy">Copy URL</button></div>
  </div>
  <?php endif; ?>
</section>

<section class="a-card">
  <div class="a-card-h"><h2>4 · Backlink tracker</h2><span class="a-muted">Log every link you earn or ask for.</span></div>
  <form method="post" class="bl-form"><?= csrf_field() ?>
    <label>Linking page URL<input name="source" type="url" required placeholder="https://clutch.co/profile/figuredoutai"></label>
    <label>Links to<select name="target"><option value="<?= e(url('/')) ?>">Homepage</option><option value="<?= e(url('blog/')) ?>">Blog</option><?php foreach ($posts as $p): ?><option value="<?= e(post_url($p)) ?>"><?= e(mb_strimwidth($p['title'], 0, 60, '…')) ?></option><?php endforeach; ?></select></label>
    <label>Anchor text<input name="anchor" placeholder="FiguredOutAI"></label>
    <label>Type<select name="type"><option>Directory</option><option>Syndication</option><option>Guest post</option><option>PR / quote</option><option>Partner</option><option>Social profile</option><option>Other</option></select></label>
    <label>Status<select name="status"><option value="planned">Planned</option><option value="requested">Requested</option><option value="live">Live</option></select></label>
    <label>Link type<select name="rel"><option value="unknown">Unknown</option><option value="dofollow">Dofollow</option><option value="nofollow">Nofollow</option></select></label>
    <label class="wide">Notes<input name="notes" placeholder="Contact, deadline…"></label>
    <button class="a-btn p" name="action" value="add">Add backlink</button>
  </form>
  <?php if ($links): ?>
  <div class="a-table-wrap"><table class="a-table">
    <thead><tr><th>Source</th><th>Target</th><th>Type</th><th>Status</th><th>Added</th><th></th></tr></thead>
    <tbody><?php foreach ($links as $l): ?>
      <tr><td><a href="<?= e($l['source']) ?>" target="_blank" rel="noopener nofollow"><?= e(parse_url($l['source'], PHP_URL_HOST)) ?></a><small><?= e($l['anchor']) ?> · <?= e($l['rel'] ?? '') ?></small></td>
        <td class="small"><?= e(str_replace(cfg('base_url'), '', $l['target']) ?: '/') ?></td><td><?= e($l['type']) ?></td>
        <td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($l['id']) ?>"><input type="hidden" name="action" value="status"><select name="status" onchange="this.form.submit()"><?php foreach (['planned', 'requested', 'live', 'lost'] as $st): ?><option <?= $l['status'] === $st ? 'selected' : '' ?>><?= $st ?></option><?php endforeach; ?></select></form></td>
        <td class="nowrap"><?= e($l['date']) ?></td>
        <td><form method="post" onsubmit="return confirm('Remove?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($l['id']) ?>"><button name="action" value="delete" class="danger">Remove</button></form></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <?php endif; ?>
</section>

<section class="a-card">
  <div class="a-card-h"><h2>5 · Internal links</h2><span class="a-muted">Each post should be linked from at least one other post. Orphans are hard for Google to find.</span></div>
  <?php if (!$posts): ?><p class="a-muted">No live posts yet.</p><?php else: ?>
  <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Post</th><th>Links from other posts</th></tr></thead><tbody>
  <?php foreach ($posts as $p): $n = $inbound[$p['slug']]; ?><tr><td><a href="/admin/edit.php?slug=<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></td><td><span class="pill <?= $n ? 'live' : 'scheduled' ?>"><?= $n ?: 'Orphan' ?></span></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>
<?php admin_close(['admin.js']);
