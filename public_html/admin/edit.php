<?php
require __DIR__ . '/_inc.php';
require_admin();

$orig = isset($_GET['slug']) ? get_post((string)$_GET['slug']) : null;
$isNew = !$orig;
$p = $orig ?: [
    'title' => '', 'slug' => '', 'description' => '', 'focus_keyword' => '', 'content' => '', 'tags' => [],
    'author' => cfg('default_author'), 'cover' => '', 'cover_alt' => '', 'status' => 'draft',
    'publish_at' => (isset($_GET['date']) && preg_match('~^\d{4}-\d{2}-\d{2}$~', $_GET['date']) ? $_GET['date'] . 'T10:00' : date('Y-m-d\TH:i')),
    'canonical' => '', 'noindex' => false, 'created' => null, 'updated' => null,
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $in = fn($k) => trim((string)($_POST[$k] ?? ''));
    $p['title'] = mb_substr($in('title'), 0, 200);
    $p['slug'] = $in('slug') !== '' ? slugify($in('slug')) : slugify($p['title']);
    $p['description'] = mb_substr($in('description'), 0, 300);
    $p['focus_keyword'] = mb_substr($in('focus_keyword'), 0, 100);
    $p['content'] = str_replace("\r\n", "\n", (string)($_POST['content'] ?? ''));
    $p['tags'] = array_values(array_unique(array_filter(array_map(fn($t) => mb_substr(trim($t), 0, 40), explode(',', $in('tags'))))));
    $p['author'] = $in('author') ?: cfg('default_author');
    $p['cover_alt'] = mb_substr($in('cover_alt'), 0, 200);
    $p['canonical'] = filter_var($in('canonical'), FILTER_VALIDATE_URL) ? $in('canonical') : '';
    $p['noindex'] = !empty($_POST['noindex']);
    $action = $_POST['action'] ?? 'save';
    $p['status'] = in_array($action, ['publish', 'schedule'], true) ? 'published' : ($action === 'unpublish' ? 'draft' : ($_POST['status'] ?? $p['status']));
    $when = $in('publish_at');
    if ($action === 'publish') $when = date('Y-m-d\TH:i');
    $ts = strtotime($when) ?: time();
    $p['publish_at'] = date('c', $ts);

    if (!empty($_POST['remove_cover'])) $p['cover'] = '';
    if ($in('cover_url') !== '' && preg_match('~^(https://|/uploads/)~', $in('cover_url'))) $p['cover'] = $in('cover_url');
    if (!empty($_FILES['cover_file']['name'])) {
        try { $p['cover'] = handle_upload($_FILES['cover_file']); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }

    if ($p['title'] === '') $errors[] = 'Title is required.';
    if (trim($p['content']) === '') $errors[] = 'Content is empty.';
    if (!valid_slug($p['slug'])) $errors[] = 'URL slug can only use lowercase letters, numbers and dashes.';
    $clash = get_post($p['slug']);
    if ($clash && (!$orig || $orig['slug'] !== $p['slug'])) $errors[] = 'Another post already uses this URL.';

    if (!$errors) {
        $p['created'] = $p['created'] ?? date('c');
        $wasLive = $orig && is_live($orig);
        if ($orig) $p['updated'] = date('c');
        if ($orig && $orig['slug'] !== $p['slug']) {
            delete_post($orig['slug']);
            if ($wasLive) { $r = json_read(CONTENT . '/redirects.json', []); $r[$orig['slug']] = $p['slug']; json_write(CONTENT . '/redirects.json', $r); }
        }
        save_post($p);
        $p['ts'] = $ts;
        $msg = 'Saved.';
        if (is_live($p)) {
            $pinged = indexnow_ping([post_url($p), url('blog/'), url('sitemap.xml')]);
            $msg = 'Published and live.' . ($pinged ? ' Search engines notified via IndexNow.' : '');
        } elseif ($p['status'] === 'published') $msg = 'Scheduled for ' . date('D j M Y, H:i', $ts) . '.';
        else $msg = 'Draft saved.';
        flash($msg);
        redirect('/admin/edit.php?slug=' . rawurlencode($p['slug']));
    }
}

$ts = strtotime($p['publish_at']) ?: time();
$state = !$isNew && $p['status'] === 'published' ? ($ts <= time() ? 'live' : 'scheduled') : 'draft';
admin_open($isNew ? 'New post' : 'Edit post', $isNew ? 'edit.php' : '');
?>
<form method="post" enctype="multipart/form-data" class="editor" id="editor">
  <?= csrf_field() ?>
  <input type="hidden" name="status" value="<?= e($p['status']) ?>">
  <div class="a-head">
    <h1><?= $isNew ? 'New post' : 'Edit post' ?> <span class="pill <?= $state ?>"><?= ucfirst($state) ?></span></h1>
    <div class="a-head-acts">
      <?php if (!$isNew): ?><a class="a-btn" href="/blog/<?= e($p['slug']) ?>" target="_blank" rel="noopener"><?= $state === 'live' ? 'View live ↗' : 'Preview ↗' ?></a><?php endif; ?>
      <button class="a-btn" name="action" value="save">Save</button>
      <?php if ($state === 'draft'): ?>
        <button class="a-btn" name="action" value="schedule" id="btnSchedule">Schedule</button>
        <button class="a-btn p" name="action" value="publish">Publish now</button>
      <?php else: ?>
        <button class="a-btn" name="action" value="unpublish">Unpublish</button>
        <button class="a-btn p" name="action" value="schedule">Update</button>
      <?php endif; ?>
    </div>
  </div>
  <?php foreach ($errors as $er): ?><div class="a-flash err"><?= e($er) ?></div><?php endforeach; ?>

  <div class="ed-grid">
    <div class="ed-main">
      <input class="ed-title" name="title" id="title" value="<?= e($p['title']) ?>" placeholder="Post title" required maxlength="200">
      <div class="ed-slug">figuredoutai.com/blog/<input name="slug" id="slug" value="<?= e($p['slug']) ?>" placeholder="auto-from-title" data-touched="<?= $isNew ? '0' : '1' ?>"></div>

      <div class="ed-box">
        <div class="ed-tools" id="tools">
          <button type="button" data-md="h2" title="Heading">H2</button><button type="button" data-md="h3" title="Sub-heading">H3</button>
          <button type="button" data-md="b" title="Bold"><b>B</b></button><button type="button" data-md="i" title="Italic"><i>I</i></button>
          <button type="button" data-md="link" title="Link">Link</button><button type="button" data-md="ul" title="List">• List</button>
          <button type="button" data-md="ol" title="Numbered list">1. List</button><button type="button" data-md="quote" title="Quote">“ ”</button>
          <button type="button" data-md="code" title="Code">&lt;/&gt;</button><button type="button" data-md="table" title="Table">Table</button>
          <label class="ed-img" title="Insert image">Image<input type="file" accept="image/*" id="inlineImg" hidden></label>
          <span class="sp"></span>
          <button type="button" id="tabWrite" class="on">Write</button><button type="button" id="tabPreview">Preview</button>
        </div>
        <textarea name="content" id="content" class="ed-content" placeholder="Write in Markdown. ## for headings, **bold**, [link](https://…)"><?= e($p['content']) ?></textarea>
        <div class="ed-preview prose" id="preview" hidden></div>
        <div class="ed-foot"><span id="wc">0 words</span><span id="rt">0 min read</span><span class="a-muted">Autosaves a local backup every few seconds</span></div>
      </div>
    </div>

    <aside class="ed-side">
      <section class="a-card">
        <h3>Publishing</h3>
        <label>Publish date & time<input type="datetime-local" name="publish_at" id="publish_at" value="<?= e(date('Y-m-d\TH:i', $ts)) ?>"></label>
        <p class="a-muted small">Set a future time and press Schedule. The post goes live on its own at that time.</p>
        <label>Author<input name="author" value="<?= e($p['author']) ?>"></label>
        <label>Tags <small>(comma separated)</small><input name="tags" value="<?= e(implode(', ', $p['tags'])) ?>" placeholder="AI agents, Automation" list="taglist"></label>
        <datalist id="taglist"><?php foreach (all_tags() as $t): ?><option value="<?= e($t['name']) ?>"><?php endforeach; ?></datalist>
      </section>

      <section class="a-card">
        <h3>Cover image</h3>
        <?php if ($p['cover']): ?><img class="ed-cover" src="<?= e($p['cover']) ?>" alt=""><label class="chk"><input type="checkbox" name="remove_cover" value="1"> Remove cover</label><?php endif; ?>
        <label>Upload (JPG, PNG, WebP · converted to WebP)<input type="file" name="cover_file" accept="image/jpeg,image/png,image/webp"></label>
        <label>…or image URL<input name="cover_url" placeholder="https://…" value=""></label>
        <label>Alt text<input name="cover_alt" id="cover_alt" value="<?= e($p['cover_alt']) ?>" placeholder="Describe the image for accessibility & SEO"></label>
      </section>

      <section class="a-card">
        <h3>SEO</h3>
        <label>Focus keyword<input name="focus_keyword" id="kw" value="<?= e($p['focus_keyword']) ?>" placeholder="e.g. AI workflow automation"></label>
        <label>Meta description <small id="dc">0/160</small><textarea name="description" id="desc" rows="3" maxlength="300" placeholder="150–160 characters that sell the click"><?= e($p['description']) ?></textarea></label>
        <div class="serp" aria-label="Google preview">
          <div class="serp-u">figuredoutai.com › blog › <span id="serpSlug"></span></div>
          <div class="serp-t" id="serpT"></div><div class="serp-d" id="serpD"></div>
        </div>
        <ul class="checks" id="checks"></ul>
        <div class="score-bar"><i id="scoreBar"></i></div><div class="small a-muted" id="scoreTxt"></div>
        <details class="adv"><summary>Advanced</summary>
          <label>Canonical URL <small>(only if first published elsewhere)</small><input name="canonical" value="<?= e($p['canonical']) ?>" placeholder="https://…"></label>
          <label class="chk"><input type="checkbox" name="noindex" value="1" <?= $p['noindex'] ? 'checked' : '' ?>> Hide from search engines (noindex)</label>
        </details>
      </section>
      <?php if (!$isNew): ?>
      <section class="a-card">
        <h3>Promote & earn links</h3>
        <p class="a-muted small">Republish on LinkedIn, Medium or Dev.to with a link back. Each copy sends readers and link equity to your domain.</p>
        <a class="a-btn full" href="/admin/seo.php?syndicate=<?= e($p['slug']) ?>#syndicate">Open syndication kit</a>
      </section>
      <?php endif; ?>
    </aside>
  </div>
</form>
<script>window.__INTERNAL = <?= json_encode(array_map(fn($x) => ['t' => $x['title'], 's' => $x['slug']], array_values(array_filter(live_posts(), fn($x) => $x['slug'] !== $p['slug']))), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>; window.__CSRF = <?= json_encode(csrf_token()) ?>;</script>
<?php admin_close(['admin.js']);
