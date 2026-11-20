(() => {
const $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];

/* ---------- copy buttons (SEO page) ---------- */
$$('.copy').forEach(b => b.addEventListener('click', async () => {
  const f = b.previousElementSibling;
  try { await navigator.clipboard.writeText(f.value); } catch (_) { f.select(); document.execCommand('copy'); }
  const t = b.textContent; b.textContent = 'Copied'; setTimeout(() => b.textContent = t, 1400);
}));

const ed = $('#editor');
if (!ed) return;

const title = $('#title'), slug = $('#slug'), content = $('#content'), desc = $('#desc'), kw = $('#kw'), alt = $('#cover_alt');
const slugify = s => s.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);

/* ---------- slug ---------- */
slug.addEventListener('input', () => slug.dataset.touched = '1');
title.addEventListener('input', () => { if (slug.dataset.touched !== '1') slug.value = slugify(title.value); update(); });

/* ---------- toolbar ---------- */
function wrap(before, after = before, placeholder = 'text') {
  const s = content.selectionStart, e = content.selectionEnd, sel = content.value.slice(s, e) || placeholder;
  content.setRangeText(before + sel + after, s, e, 'select');
  content.selectionStart = s + before.length; content.selectionEnd = s + before.length + sel.length;
  content.focus(); update();
}
function linePrefix(prefix) {
  const s = content.selectionStart, e = content.selectionEnd, v = content.value;
  const ls = v.lastIndexOf('\n', s - 1) + 1;
  const block = v.slice(ls, e).split('\n').map((l, i) => (typeof prefix === 'function' ? prefix(i) : prefix) + l).join('\n');
  content.setRangeText(block, ls, e, 'end'); content.focus(); update();
}
function insert(text) { content.setRangeText(text, content.selectionStart, content.selectionEnd, 'end'); content.focus(); update(); }
$('#tools').addEventListener('click', e => {
  const b = e.target.closest('[data-md]'); if (!b) return;
  ({
    h2: () => linePrefix('## '), h3: () => linePrefix('### '), b: () => wrap('**'), i: () => wrap('*'),
    link: () => { const u = prompt('Link URL (use /blog/slug for internal links)', 'https://'); if (u) wrap('[', '](' + u + ')', 'link text'); },
    ul: () => linePrefix('- '), ol: () => linePrefix(i => (i + 1) + '. '), quote: () => linePrefix('> '),
    code: () => wrap('\n```\n', '\n```\n', 'code'),
    table: () => insert('\n| Column | Column |\n| --- | --- |\n| Value | Value |\n'),
  })[b.dataset.md]();
});
content.addEventListener('keydown', e => {
  if (!(e.ctrlKey || e.metaKey)) return;
  if (e.key === 'b') { e.preventDefault(); wrap('**'); }
  if (e.key === 'i') { e.preventDefault(); wrap('*'); }
  if (e.key === 's') { e.preventDefault(); dirty = false; ed.querySelector('[name=action][value=save]').click(); }
});

/* ---------- inline image upload ---------- */
$('#inlineImg').addEventListener('change', async ev => {
  const f = ev.target.files[0]; if (!f) return;
  const fd = new FormData(); fd.append('file', f); fd.append('csrf', window.__CSRF);
  insert('![Uploading…]()');
  try {
    const r = await fetch('/admin/api.php?do=upload', { method: 'POST', body: fd }); const j = await r.json();
    if (!j.url) throw new Error(j.error || 'Upload failed');
    content.value = content.value.replace('![Uploading…]()', `![Describe this image](${j.url})`);
  } catch (err) { content.value = content.value.replace('![Uploading…]()', ''); alert(err.message); }
  ev.target.value = ''; update();
});

/* ---------- preview ---------- */
const pv = $('#preview'), tw = $('#tabWrite'), tp = $('#tabPreview');
tp.addEventListener('click', async () => {
  tp.classList.add('on'); tw.classList.remove('on');
  const fd = new FormData(); fd.append('content', content.value); fd.append('csrf', window.__CSRF);
  pv.innerHTML = '<p style="color:#8B95A3">Rendering…</p>'; pv.hidden = false; content.hidden = true;
  try { const r = await fetch('/admin/api.php?do=preview', { method: 'POST', body: fd }); pv.innerHTML = (await r.json()).html || ''; }
  catch (_) { pv.textContent = 'Preview failed.'; }
});
tw.addEventListener('click', () => { tw.classList.add('on'); tp.classList.remove('on'); pv.hidden = true; content.hidden = false; content.focus(); });

/* ---------- live SEO checks ---------- */
function update() {
  const md = content.value, txt = md.replace(/[#>*_`|\[\]()!-]/g, ' ');
  const words = (txt.match(/\b[\w'’]+\b/g) || []).length;
  $('#wc').textContent = words + ' words'; $('#rt').textContent = Math.max(1, Math.ceil(words / 220)) + ' min read';
  const d = desc.value.trim(); $('#dc').textContent = d.length + '/160';
  $('#serpSlug').textContent = slug.value || slugify(title.value);
  const t = (title.value || 'Post title') + ' | FiguredOutAI';
  $('#serpT').textContent = t.length > 62 ? t.slice(0, 60) + '…' : t;
  const dd = d || md.replace(/[#>*_`]/g, '').trim().slice(0, 155);
  $('#serpD').textContent = dd.length > 158 ? dd.slice(0, 155) + '…' : dd;

  const k = kw.value.trim().toLowerCase();
  const first = (md.split(/\n\s*\n/).find(p => p.trim() && !p.trim().startsWith('#')) || '').toLowerCase();
  const h2 = (md.match(/^##\s/gm) || []).length;
  const internal = (md.match(/\]\((https?:\/\/(www\.)?figuredoutai\.com)?\/(blog\/)?[^)]*\)/g) || []).length;
  const external = (md.match(/\]\(https?:\/\/(?!(www\.)?figuredoutai\.com)[^)]+\)/g) || []).length;
  const kwCount = k ? (md.toLowerCase().split(k).length - 1) : 0;
  const density = words && k ? (kwCount * k.split(/\s+/).length / words * 100) : 0;
  const checks = [
    [!!k, 'Focus keyword set'],
    [k && title.value.toLowerCase().includes(k), 'Keyword in title'],
    [title.value.length >= 30 && title.value.length <= 65, `Title length 30–65 (now ${title.value.length})`],
    [d.length >= 120 && d.length <= 160, `Meta description 120–160 (now ${d.length})`],
    [k && d.toLowerCase().includes(k), 'Keyword in meta description'],
    [k && (slug.value || '').includes(slugify(k)), 'Keyword in URL'],
    [k && first.includes(k), 'Keyword in first paragraph'],
    [k && density >= 0.5 && density <= 2.5, `Keyword density 0.5–2.5% (now ${density.toFixed(1)}%)`],
    [words >= 800, `800+ words (now ${words})`],
    [h2 >= 2, `2+ section headings (now ${h2})`],
    [internal >= 2, `2+ internal links (now ${internal})`, true],
    [external >= 1, `1+ link to a trusted source (now ${external})`],
    [!document.querySelector('.ed-cover') && !document.querySelector('[name=cover_file]').files.length ? false : alt.value.trim().length > 5, 'Cover image with alt text'],
  ];
  $('#checks').innerHTML = checks.map(([ok, label]) => `<li class="${ok ? 'ok' : 'warn'}">${label}</li>`).join('');
  const score = Math.round(checks.filter(c => c[0]).length / checks.length * 100);
  $('#scoreBar').style.width = score + '%';
  $('#scoreTxt').textContent = `SEO score ${score}/100 · ${score >= 80 ? 'ready to rank' : score >= 55 ? 'good, a few fixes left' : 'needs work'}`;
}
[content, desc, kw, slug, alt].forEach(el => el.addEventListener('input', update));
document.querySelector('[name=cover_file]').addEventListener('change', update);
update();

/* ---------- internal link helper: type [[ to search your posts ---------- */
content.addEventListener('input', () => {
  const v = content.value, p = content.selectionStart;
  if (v.slice(p - 2, p) !== '[[') return;
  const list = window.__INTERNAL || [];
  if (!list.length) return;
  const q = prompt('Link to which post? Type part of the title:\n\n' + list.slice(0, 15).map(x => '• ' + x.t).join('\n'));
  if (q === null) return;
  const hit = list.find(x => x.t.toLowerCase().includes(q.toLowerCase()));
  if (!hit) { alert('No post matched.'); return; }
  content.setRangeText(`[${hit.t}](/blog/${hit.s})`, p - 2, p, 'end'); update();
});

/* ---------- local backup + unsaved warning ---------- */
const key = 'foai-draft-' + (slug.value || 'new');
let dirty = false;
try {
  const saved = JSON.parse(localStorage.getItem(key) || 'null');
  if (saved && saved.content !== content.value && saved.at > Date.now() - 7 * 864e5 && confirm('A newer unsaved local copy of this post exists. Restore it?')) {
    title.value = saved.title; content.value = saved.content; desc.value = saved.desc; update();
  }
} catch (_) {}
ed.addEventListener('input', () => dirty = true);
setInterval(() => { if (!dirty) return; try { localStorage.setItem(key, JSON.stringify({ title: title.value, content: content.value, desc: desc.value, at: Date.now() })); } catch (_) {} }, 4000);
ed.addEventListener('submit', () => { dirty = false; try { localStorage.removeItem(key); } catch (_) {} });
addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
