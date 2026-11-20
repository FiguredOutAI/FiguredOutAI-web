<?php
require __DIR__ . '/_inc.php';
require_admin();
$file = CONTENT . '/leads.json';
$leads = json_read($file, []);
$statuses = ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'won' => 'Won', 'closed' => 'Closed'];

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="figuredoutai-leads-' . date('Y-m-d') . '.csv"');
    $o = fopen('php://output', 'w');
    fputcsv($o, ['Date', 'Name', 'Email', 'Company', 'Interest', 'Status', 'Message']);
    // Prefix cells that start with formula characters so spreadsheets don't execute them.
    $safe = fn($v) => preg_match('~^[=+\-@\t\r]~', (string)$v) ? "'" . $v : $v;
    foreach ($leads as $l) fputcsv($o, array_map($safe, [$l['at'], $l['name'], $l['email'], $l['company'], $l['interest'], $l['status'], $l['message']]));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (string)($_POST['id'] ?? '');
    foreach ($leads as $i => $l) {
        if ($l['id'] !== $id) continue;
        if (($_POST['action'] ?? '') === 'delete') { array_splice($leads, $i, 1); flash('Lead deleted.'); }
        elseif (isset($statuses[$_POST['status'] ?? ''])) { $leads[$i]['status'] = $_POST['status']; flash('Lead updated.'); }
        break;
    }
    json_write($file, $leads);
    redirect('/admin/leads.php');
}
admin_open('Leads', 'leads.php');
?>
<div class="a-head"><h1>Leads</h1><?php if ($leads): ?><a class="a-btn" href="?export=csv">Export CSV</a><?php endif; ?></div>
<p class="a-muted">Every contact-form enquiry lands here and is emailed to <b><?= e(cfg('contact_email')) ?></b>.</p>
<?php if (!$leads): ?>
  <section class="a-card"><p class="a-muted">No enquiries yet. They'll show up here as soon as someone uses the form on the homepage.</p></section>
<?php else: foreach ($leads as $l): ?>
  <section class="a-card lead">
    <div class="lead-h">
      <div><b><?= e($l['name']) ?></b> · <a href="mailto:<?= e($l['email']) ?>?subject=<?= rawurlencode('Re: your enquiry to FiguredOutAI') ?>"><?= e($l['email']) ?></a><?php if ($l['company']): ?> · <?= e($l['company']) ?><?php endif; ?>
        <div class="a-muted small"><?= e(date('D j M Y, H:i', strtotime($l['at']))) ?> · <?= e($l['interest']) ?></div></div>
      <div class="lead-acts">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($l['id']) ?>">
          <select name="status" onchange="this.form.submit()"><?php foreach ($statuses as $k => $v): ?><option value="<?= $k ?>" <?= $l['status'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
        </form>
        <form method="post" onsubmit="return confirm('Delete this lead?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($l['id']) ?>"><button name="action" value="delete" class="danger">Delete</button></form>
      </div>
    </div>
    <p class="lead-msg"><?= nl2br(e($l['message'])) ?></p>
  </section>
<?php endforeach; endif; ?>
<?php admin_close();
