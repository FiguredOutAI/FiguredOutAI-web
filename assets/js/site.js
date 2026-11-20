(() => {
const $ = s => document.querySelector(s), $$ = s => [...document.querySelectorAll(s)];
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
const hover = matchMedia('(hover:hover) and (pointer:fine)').matches;

/* ---------- loader + headline ---------- */
const h1 = $('#h1');
function ready() { $('#loader')?.classList.add('done'); h1?.classList.add('in'); document.body.classList.add('ready'); }
// Show content as soon as the HTML is ready; the 3D scene fades in on its own once three.js has loaded.
setTimeout(ready, 220); // after the split below has wrapped the words

if (h1) (function split(el) {
  const walk = node => [...node.childNodes].forEach(n => {
    if (n.nodeType === 3) {
      const frag = document.createDocumentFragment();
      n.textContent.split(/(\s+)/).forEach(w => {
        if (!w) return;
        if (/^\s+$/.test(w)) { frag.appendChild(document.createTextNode(' ')); return; }
        const o = document.createElement('span'); o.className = 'w';
        const i = document.createElement('span'); i.textContent = w; o.appendChild(i); frag.appendChild(o);
      });
      n.replaceWith(frag);
    } else walk(n);
  });
  walk(el);
  el.querySelectorAll('.w>span').forEach((s, i) => s.style.transitionDelay = (i * 0.06) + 's');
})(h1);

/* ---------- header + mobile menu ---------- */
const hdr = $('#hdr'), burger = $('#burger');
if (document.body.classList.contains('is-home')) addEventListener('scroll', () => hdr.classList.toggle('scrolled', scrollY > 30), { passive: true });
function setMenu(open) { hdr.classList.toggle('open', open); document.body.classList.toggle('menu-open', open); burger.setAttribute('aria-expanded', open); burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu'); }
burger?.addEventListener('click', () => setMenu(!hdr.classList.contains('open')));
$$('#menu a').forEach(a => a.addEventListener('click', () => setMenu(false)));
addEventListener('keydown', e => { if (e.key === 'Escape') setMenu(false); });
addEventListener('resize', () => { if (innerWidth > 820) setMenu(false); });

/* ---------- reveal on scroll ---------- */
$$('.bento,.prin,.steps,.ind-grid,.post-grid,.stack-list,.guarantees,.team-grid,.pgrid').forEach(g => [...g.children].forEach((c, i) => c.style.setProperty('--i', i % 4)));
if (reduce || !('IntersectionObserver' in window)) $$('.reveal').forEach(el => el.classList.add('in'));
else { const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { threshold: .12, rootMargin: '0px 0px -40px 0px' });
  $$('.reveal').forEach(el => io.observe(el)); }

/* ---------- pointer effects (desktop only) ---------- */
const cg = $('#cglow');
if (hover && cg && !reduce) {
  let gx = innerWidth / 2, gy = innerHeight / 2, cx = gx, cy = gy;
  addEventListener('pointermove', e => { gx = e.clientX; gy = e.clientY; });
  (function loop() { cx += (gx - cx) * .12; cy += (gy - cy) * .12; cg.style.transform = `translate(${cx - 260}px,${cy - 260}px)`; requestAnimationFrame(loop); })();
} else cg?.remove();

if (hover && !reduce) {
  $$('.magnetic').forEach(b => {
    b.addEventListener('pointermove', e => { const r = b.getBoundingClientRect(); b.style.transform = `translate(${(e.clientX - r.left - r.width / 2) * .18}px,${(e.clientY - r.top - r.height / 2) * .28}px)`; });
    b.addEventListener('pointerleave', () => b.style.transform = '');
  });
  $$('.tilt').forEach(c => {
    c.addEventListener('pointermove', e => {
      const r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
      c.style.setProperty('--mx', x * 100 + '%'); c.style.setProperty('--my', y * 100 + '%');
      c.style.transform = `rotateX(${(.5 - y) * 7}deg) rotateY(${(x - .5) * 9}deg) translateZ(0)`;
    });
    c.addEventListener('pointerleave', () => c.style.transform = '');
  });
  const ms = $('#mockStage'), mk = $('#mock');
  if (ms && mk) {
    ms.addEventListener('pointermove', e => { const r = ms.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5; mk.style.transform = `rotateY(${-14 + x * 14}deg) rotateX(${8 - y * 10}deg)`; });
    ms.addEventListener('pointerleave', () => mk.style.transform = '');
  }
}

/* ---------- marquee ---------- */
const tr = $('#track');
if (tr) {
  const stack = ['Anthropic Claude', 'OpenAI', 'Google Gemini', 'Llama', 'Mistral', 'LangGraph', 'Python', 'TypeScript', 'Next.js', 'Supabase', 'PostgreSQL', 'AWS', 'Google Cloud', 'Azure', 'Vercel', 'WhatsApp Business', 'Twilio', 'HubSpot', 'Slack', 'n8n'];
  tr.innerHTML = [...stack, ...stack].map(s => `<span>${s}</span>`).join('');
}

/* ---------- manifesto scroll-lit words ---------- */
const man = $('#manifesto');
if (man) {
  const walk = node => [...node.childNodes].forEach(n => {
    if (n.nodeType === 3) { const f = document.createDocumentFragment(); n.textContent.split(/(\s+)/).forEach(w => { if (!w) return; if (/^\s+$/.test(w)) { f.appendChild(document.createTextNode(' ')); return; } const s = document.createElement('span'); s.className = 'mw'; s.textContent = w; f.appendChild(s); }); n.replaceWith(f); }
    else walk(n);
  });
  walk(man);
  const mws = [...man.querySelectorAll('.mw')];
  const light = () => {
    const r = man.getBoundingClientRect(), vh = innerHeight;
    const p = Math.min(1, Math.max(0, (vh * .85 - r.top) / (r.height + vh * .35)));
    const n = reduce ? mws.length : Math.floor(p * mws.length * 1.05);
    mws.forEach((w, i) => w.classList.toggle('lit', i < n));
  };
  addEventListener('scroll', light, { passive: true }); light();
}

/* ---------- 3D layers ---------- */
const li = $('#layersInner'), lyWrap = $('#layers');
if (li && lyWrap) {
  const layers = $$('.layer');
  const layerScroll = () => {
    const r = lyWrap.getBoundingClientRect(), vh = innerHeight;
    const p = Math.min(1, Math.max(0, (vh - r.top) / (vh + r.height * .4)));
    const gap = (innerWidth < 640 ? 30 : 52) * p + 10;
    layers.forEach(l => l.style.transform = `translateZ(${(+l.dataset.i) * gap}px)`);
    li.style.transform = `rotateX(${58 - p * 6}deg) rotateZ(${-40 + p * 14}deg)`;
  };
  addEventListener('scroll', layerScroll, { passive: true }); layerScroll();
  const pick = s => { $$('.sl').forEach(x => x.classList.remove('on')); s.classList.add('on'); layers.forEach(l => l.classList.toggle('hl', l.dataset.i === s.dataset.l && !l.classList.contains('top'))); };
  $$('.sl').forEach(s => {
    s.addEventListener('mouseenter', () => pick(s));
    s.addEventListener('click', () => pick(s));
    s.addEventListener('mouseleave', () => { s.classList.remove('on'); layers.forEach(l => l.classList.remove('hl')); });
  });
}

/* ---------- counters ---------- */
const cio = new IntersectionObserver(es => es.forEach(e => {
  if (!e.isIntersecting) return;
  const el = e.target, to = +el.dataset.to, t0 = performance.now();
  (function f(t) { const k = Math.min(1, (t - t0) / 1400), v = 1 - Math.pow(1 - k, 3); el.textContent = Math.round(to * v); if (k < 1) requestAnimationFrame(f); })(t0);
  cio.unobserve(el);
}), { threshold: .6 });
$$('.count').forEach(c => cio.observe(c));

/* ---------- time zones ---------- */
const tzEl = $('#tz');
if (tzEl) {
  const zones = [['SF', 'America/Los_Angeles'], ['NYC', 'America/New_York'], ['LON', 'Europe/London'], ['DXB', 'Asia/Dubai'], ['IND', 'Asia/Kolkata'], ['SGP', 'Asia/Singapore']];
  const tz = () => tzEl.innerHTML = zones.map(([n, z]) => `<span>${n}<b>${new Date().toLocaleTimeString('en-GB', { timeZone: z, hour: '2-digit', minute: '2-digit' })}</b></span>`).join('');
  tz(); setInterval(tz, 30000);
}

/* ---------- live inbox (demo data) ----------
   A chat lands, the AI replies, then its intent score resolves. Rows below slide down (FLIP). */
const feed = $('#feed'), cnt = $('#cnt');
if (feed) {
  const icon = c => $(`.mock-side .ch-${c}`)?.outerHTML || '';
  const items = [
    ['Aarav M.', 'whatsapp', 'wants 40 units for an event', 'hi', 92, 3], ['Sofia R.', 'instagram', '“price?” (third time)', 'md', 58, 2],
    ['Liam K.', 'messenger', 'delivery to Pune?', 'md', 66, 4], ['Noor A.', 'whatsapp', 'ready to pay, needs invoice', 'hi', 95, 2],
    ['Kenji T.', 'instagram', 'collab request', 'md', 61, 5], ['Emma W.', 'messenger', 'just browsing', 'lo', 23, 3],
    ['Rohan S.', 'whatsapp', 'order not delivered', 'hi', 88, 2], ['Chloe D.', 'instagram', '“nice pic”', 'lo', 12, 4],
  ];
  const label = { hi: 'Hot', md: 'Warm', lo: 'Cold' };
  let k = 0, total = 128;
  const make = ([n, ch, msg, lv, sc, sec], live) => {
    const row = document.createElement('div'); row.className = 'row' + (live ? ' is-new' : ' replied');
    row.innerHTML = `<div class="av">${n[0]}${n.split(' ')[1][0]}</div><div class="t"><b>${n}${icon(ch)}</b><span>${msg}</span><i class="by-ai">Replied by AI · ${sec} sec</i></div>`
      + `<span class="score ${live ? 'wait' : lv}">${live ? 'Reading' : label[lv] + ' · ' + sc}</span>`;
    return row;
  };
  const resolve = (row, it) => {
    setTimeout(() => row.classList.add('replied'), 700);
    setTimeout(() => { const s = row.querySelector('.score'); s.className = 'score pop ' + it[3]; s.textContent = label[it[3]] + ' · ' + it[4]; }, 1300);
  };
  const push = () => {
    const it = items[k++ % items.length];
    const rows = [...feed.children], before = rows.map(r => r.getBoundingClientRect().top);
    const row = make(it, true);
    feed.prepend(row);
    rows.forEach((r, i) => { const d = before[i] - r.getBoundingClientRect().top; r.style.transition = 'none'; r.style.transform = `translateY(${d}px)`; });
    requestAnimationFrame(() => requestAnimationFrame(() => { rows.forEach(r => { r.style.transition = ''; r.style.transform = ''; }); row.classList.toggle('is-new', false); }));
    while (feed.children.length > 7) feed.lastElementChild.remove();
    cnt.textContent = ++total;
    resolve(row, it);
  };
  for (let i = 0; i < 5; i++) feed.appendChild(make(items[(i + 3) % items.length], false));
  let timer = null;
  if (!reduce) new IntersectionObserver(([e]) => { clearInterval(timer); if (e.isIntersecting) timer = setInterval(push, 3000); }).observe(feed);
}

/* ---------- testimonials  ---------- */
const t1 = $('#t1'), t2 = $('#t2');
if (t1 && t2) {
  const T = [
    ['Daniel H.', 'COO, logistics company · London', 'They automated the reporting our ops team did by hand every Monday. What took a day now lands in Slack before anyone logs in.', '#22E3F2'],
    ['Priya N.', 'Founder, D2C brand · Bengaluru', 'Our support inbox went from chaos to calm. The AI handles the routine questions and hands us the ones that need a human, with full context.', '#FF7A2F'],
    ['Omar F.', 'Managing Director, real estate · Dubai', 'What stood out was the process. Every phase was signed off before the next began, and we owned everything at the end.', '#22E3F2'],
    ['Sarah L.', 'Head of Growth, SaaS · Austin', 'They shipped an AI feature into our product in weeks, with evals and monitoring from day one. It felt like adding a senior AI team.', '#FF7A2F'],
    ['Wei C.', 'Operations Lead, clinic group · Singapore', 'Patient enquiries are answered instantly and routed correctly. The decision log means we can see exactly why the AI did what it did.', '#22E3F2'],
    ['James O.', 'Agency Owner · Sydney', 'We white-label their sales platform for our clients. It looks like ours, works reliably and lets us sell AI without building it ourselves.', '#FF7A2F'],
    ['Ananya K.', 'Recruitment Director · Mumbai', 'Screening that took our team three days now happens the same afternoon. The shortlists are consistent and explainable.', '#22E3F2'],
    ['Lucas B.', 'CTO, fintech startup · Berlin', 'Model-agnostic was the deciding factor. When a better model shipped, they switched it in a day without touching our product.', '#FF7A2F'],
  ];
  const q = ([n, r, t, c]) => `<figure class="quote"><div class="stars" aria-label="5 out of 5">★★★★★</div><p>“${t}”</p><figcaption class="who"><div class="av" style="background:${c}">${n[0]}</div><div><b>${n}</b><span>${r}</span></div></figcaption></figure>`;
  const a = T.slice(0, 4), b = T.slice(4);
  t1.innerHTML = [...a, ...a, ...a].map(q).join('');
  t2.innerHTML = [...b, ...b, ...b].map(q).join('');
}

/* ---------- contact form (AJAX with normal POST fallback) ---------- */
const form = $('#form');
if (form) form.addEventListener('submit', async e => {
  e.preventDefault();
  const st = $('#f-status'), btn = form.querySelector('button[type=submit]');
  if (!form.checkValidity()) { st.textContent = 'Please add your name, a valid email and a message.'; st.className = 'err-msg'; form.reportValidity(); return; }
  btn.disabled = true; st.className = ''; st.textContent = 'Sending…';
  try {
    const r = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } });
    const j = await r.json();
    if (!j.ok) throw new Error(j.error || 'Something went wrong.');
    if (window.foaiTrack) window.foaiTrack('lead');
    form.innerHTML = '<div class="ok-msg">Thank you. Your enquiry is in, and we reply within one business day.</div>';
  } catch (err) {
    st.textContent = err.message + ' You can also email us directly.'; st.className = 'err-msg'; btn.disabled = false;
  }
});

/* ---------- buttons that preselect the enquiry type ---------- */
$$('[data-interest]').forEach(a => a.addEventListener('click', () => { const sel = $('#f-need'); if (sel) sel.value = a.dataset.interest; }));

/* ---------- waitlist ---------- */
const wls = $$('[data-wl]');
if (wls.length) {
  // Campaign source for this visit (UTM tags, landing page, referrer). Sent only with a sign-up.
  // Kept for the whole visit, but a new link with UTM tags (e.g. another ad click) replaces it.
  let src = {};
  try { src = JSON.parse(sessionStorage.getItem('foai_src') || 'null') || {}; } catch (_) {}
  const q = new URLSearchParams(location.search);
  if (!src.landing || [...q.keys()].some(k => k.startsWith('utm_'))) {
    src = {};
    ['source', 'medium', 'campaign', 'content', 'term'].forEach(k => { const v = q.get('utm_' + k); if (v) src['utm_' + k] = v; });
    src.landing = location.pathname + location.search;
    src.referrer = document.referrer && !document.referrer.startsWith(location.origin) ? document.referrer : '';
    try { sessionStorage.setItem('foai_src', JSON.stringify(src)); } catch (_) {}
  }
  const api = async data => {
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => Array.isArray(v) ? v.forEach(x => fd.append(k + '[]', x)) : fd.append(k, v ?? ''));
    const r = await fetch('/waitlist-api.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
    let j = {}; try { j = await r.json(); } catch (_) {}
    if (!j.ok) throw new Error(j.error || 'Something broke on our side. Try again in a minute?');
    return j;
  };
  const msg = (el, t, err) => { el.textContent = t; el.classList.toggle('err', !!err); };
  const done = (w, already) => {
    w.querySelector('.wl-s1').hidden = true; w.querySelector('.wl-s2').hidden = true;
    const d = w.querySelector('.wl-done'); d.hidden = false;
    if (already) {
      d.querySelector('.wl-done-h').textContent = "You're already on the list.";
      d.querySelector('.wl-done-p').textContent = "Commitment. We respect it. We'll email you when it's your turn.";
    }
  };
  const toStep2 = (w, j) => {
    w.dataset.ref = j.ref;
    const s1 = w.querySelector('.wl-s1'), s2 = w.querySelector('.wl-s2');
    s1.hidden = true; s2.hidden = false;
    if (j.name) s2.elements.name.value = j.name;
    (j.name ? s2.elements.company : s2.elements.name).focus({ preventScroll: true });
  };
  let lastWl = wls[0];
  wls.forEach(w => {
    w.addEventListener('pointerdown', () => lastWl = w);
    const s1 = w.querySelector('.wl-s1'), s2 = w.querySelector('.wl-s2');
    s1.addEventListener('submit', async e => {
      e.preventDefault();
      const m = s1.querySelector('.wl-msg'), b = s1.querySelector('button[type=submit]');
      if (!s1.elements.email.value || !s1.elements.email.checkValidity()) { msg(m, 'That email looks made up. Try again?', true); s1.elements.email.focus(); return; }
      b.disabled = true; msg(m, 'One sec…');
      try {
        const j = await api({ step: 'start', email: s1.elements.email.value, website: s1.elements.website.value, t: s1.elements.t.value, page: location.pathname, ...src });
        msg(m, ''); j.already ? done(w, true) : toStep2(w, j);
      } catch (err) { msg(m, err.message, true); }
      b.disabled = false;
    });
    s2.addEventListener('submit', async e => {
      e.preventDefault();
      const m = s2.querySelector('.wl-msg'), b = s2.querySelector('button[type=submit]'), f = s2.elements;
      if (!f.name.value.trim()) { msg(m, 'What should we call you?', true); f.name.focus(); return; }
      if (!f.consent.checked) { msg(m, "Tick the box so we're allowed to tell you when you're in.", true); return; }
      b.disabled = true; msg(m, 'Locking it in…');
      try {
        const j = await api({ step: 'finish', ref: w.dataset.ref, name: f.name.value, company: f.company.value, phone: f.phone.value,
          channels: [...s2.querySelectorAll('input[name="channels[]"]:checked')].map(x => x.value), consent: '1' });
        if (j.count != null) $$('[data-wl-count]').forEach(c => c.textContent = Number(j.count).toLocaleString('en-US'));
        if (window.foaiTrack) window.foaiTrack('signup');
        wls.forEach(x => done(x));
      } catch (err) { msg(m, err.message, true); b.disabled = false; }
    });
    w.querySelector('[data-share]')?.addEventListener('click', async ev => {
      const link = location.origin + '/waitlist?utm_source=referral&utm_medium=invite';
      try { await navigator.clipboard.writeText(link); ev.target.textContent = 'Link copied. Go be a good friend.'; } catch (_) { ev.target.textContent = link; }
    });
  });
  // Continue with Google (only when a Client ID is saved in Admin → Settings)
  if (window.__WL_GOOGLE && $('[data-google]')) {
    window.foaiGoogle = async resp => {
      const w = lastWl, m = w.querySelector('.wl-s1 .wl-msg');
      msg(m, 'Checking with Google…');
      try {
        const j = await api({ step: 'start', credential: resp.credential, page: location.pathname, ...src });
        msg(m, ''); j.already ? done(w, true) : toStep2(w, j);
      } catch (err) { msg(m, err.message, true); }
    };
    const gs = document.createElement('script');
    gs.src = 'https://accounts.google.com/gsi/client'; gs.async = true; gs.defer = true;
    gs.onload = () => {
      google.accounts.id.initialize({ client_id: window.__WL_GOOGLE, callback: window.foaiGoogle, ux_mode: 'popup', auto_select: false, cancel_on_tap_outside: true });
      $$('[data-google]').forEach(el => google.accounts.id.renderButton(el, { theme: 'filled_black', size: 'large', shape: 'pill', text: 'continue_with', logo_alignment: 'left', width: Math.min(360, el.clientWidth || 320) }));
    };
    document.head.appendChild(gs);
  }
}

/* ---------- blog: copy link + reading progress ---------- */
$$('[data-copy]').forEach(b => b.addEventListener('click', async () => { try { await navigator.clipboard.writeText(b.dataset.copy); b.textContent = 'Link copied'; } catch (_) {} }));
const prog = $('#readprog');
if (prog) { const art = $('.article'); addEventListener('scroll', () => { const r = art.getBoundingClientRect(); prog.style.transform = `scaleX(${Math.min(1, Math.max(0, -r.top / (r.height - innerHeight)))})`; }, { passive: true }); }
})();
