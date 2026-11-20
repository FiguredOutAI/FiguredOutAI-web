/**
 * FiguredOutAI cookie consent.
 * - Nothing that sets cookies or tracks is loaded until the visitor opts in.
 * - Categories: necessary (always on), analytics (GA4, Microsoft Clarity),
 *   marketing (Google Ads, Meta Pixel, LinkedIn Insight).
 * - Google Consent Mode v2 defaults are set to "denied" in the page <head>.
 * - Choice is stored for 180 days in the first-party cookie "foai_consent".
 */
(() => {
  const CFG = window.__TRACK || { ids: {}, v: 1 };
  const IDS = CFG.ids || {};
  const COOKIE = 'foai_consent';
  const MAX_AGE = 180 * 24 * 3600;
  const hasAnalytics = !!(IDS.ga4 || IDS.clarity);
  const hasMarketing = !!(IDS.ads || IDS.meta || IDS.li);
  const loaded = {};
  let state = read();

  /* ---------- storage ---------- */
  function read() {
    const m = document.cookie.match(/(?:^|;\s*)foai_consent=([^;]+)/);
    if (!m) return null;
    try {
      const c = JSON.parse(decodeURIComponent(m[1]));
      return c && c.v === CFG.v ? c : null; // re-ask when the consent version changes
    } catch (_) { return null; }
  }
  function write(c) {
    const secure = location.protocol === 'https:' ? ';Secure' : '';
    document.cookie = `${COOKIE}=${encodeURIComponent(JSON.stringify(c))};Max-Age=${MAX_AGE};Path=/;SameSite=Lax${secure}`;
  }
  function log(c, method) {
    const body = JSON.stringify({ id: c.id, a: c.a ? 1 : 0, m: c.m ? 1 : 0, v: c.v, method });
    try { navigator.sendBeacon('/consent.php', new Blob([body], { type: 'application/json' })); } catch (_) {}
  }
  const rid = () => (crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2) + Date.now().toString(36));

  /* ---------- applying consent ---------- */
  function script(src, attrs = {}) {
    const s = document.createElement('script'); s.async = true; s.src = src;
    Object.entries(attrs).forEach(([k, v]) => s.setAttribute(k, v));
    document.head.appendChild(s);
  }
  function gtagLoad(id) { if (loaded.gtag) return; loaded.gtag = 1; script('https://www.googletagmanager.com/gtag/js?id=' + id); gtag('js', new Date()); }

  function apply(c) {
    gtag('consent', 'update', {
      analytics_storage: c.a ? 'granted' : 'denied',
      ad_storage: c.m ? 'granted' : 'denied',
      ad_user_data: c.m ? 'granted' : 'denied',
      ad_personalization: c.m ? 'granted' : 'denied',
    });
    // Analytics
    if (c.a && IDS.ga4 && !loaded.ga4) { loaded.ga4 = 1; gtagLoad(IDS.ga4); gtag('config', IDS.ga4); }
    if (c.a && IDS.clarity && !loaded.clarity) {
      loaded.clarity = 1;
      (function (c, l, a, r, i) { c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); }; const t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i; const y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y); })(window, document, 'clarity', 'script', IDS.clarity);
      window.clarity('consent');
    }
    // Marketing / retargeting
    if (c.m && IDS.ads && !loaded.ads) { loaded.ads = 1; gtagLoad(IDS.ads); gtag('config', IDS.ads); }
    if (c.m && IDS.meta && !loaded.meta) {
      loaded.meta = 1;
      (function (f, b, e, v) { if (f.fbq) return; const n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = true; n.version = '2.0'; n.queue = []; script(v); })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
      window.fbq('init', IDS.meta); window.fbq('track', 'PageView');
    }
    if (c.m && IDS.li && !loaded.li) {
      loaded.li = 1;
      window._linkedin_partner_id = IDS.li;
      window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
      window._linkedin_data_partner_ids.push(IDS.li);
      window.lintrk = window.lintrk || function (a, b) { window.lintrk.q.push([a, b]); }; window.lintrk.q = window.lintrk.q || [];
      script('https://snap.licdn.com/li.lms-analytics/insight.min.js');
    }
  }

  /** Remove tracking cookies when someone withdraws consent. */
  function purge(c) {
    const kill = [];
    if (!c.a) kill.push(/^_ga/, /^_gid$/, /^_gat/, /^_clck$/, /^_clsk$/, /^CLID$/, /^MUID$/);
    if (!c.m) kill.push(/^_gcl_/, /^_fbp$/, /^_fbc$/, /^li_/, /^lidc$/, /^bcookie$/, /^UserMatchHistory$/, /^AnalyticsSyncHistory$/);
    const host = location.hostname.replace(/^www\./, '');
    document.cookie.split(';').map(s => s.split('=')[0].trim()).forEach(name => {
      if (!kill.some(r => r.test(name))) return;
      ['', host, '.' + host].forEach(d => { document.cookie = `${name}=;Max-Age=0;Path=/${d ? ';Domain=' + d : ''}`; });
    });
  }

  function save(a, m, method) {
    const prev = state;
    state = { v: CFG.v, a: !!a, m: !!m, t: Date.now(), id: (prev && prev.id) || rid() };
    write(state); log(state, method); hide();
    const revoked = prev && ((prev.a && !state.a) || (prev.m && !state.m));
    if (revoked) { purge(state); location.reload(); return; } // scripts already running can only be stopped by a reload
    apply(state);
  }

  /* ---------- UI ---------- */
  const esc = s => String(s).replace(/[&<>"]/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));
  let banner, modal, lastFocus;
  function buildBanner() {
    banner = document.createElement('div');
    banner.className = 'cc-banner'; banner.setAttribute('role', 'region'); banner.setAttribute('aria-label', 'Cookie consent');
    banner.innerHTML = `
      <div class="cc-text"><b>Cookies, with your permission.</b>
        <p>We use essential cookies to run this site. With your consent we also use analytics to understand visits and marketing cookies to show you relevant ads elsewhere. <a href="/privacy#cookies">Cookie policy</a></p></div>
      <div class="cc-acts">
        <button type="button" class="cc-btn" data-cc="reject">Reject all</button>
        <button type="button" class="cc-btn" data-cc="custom">Customise</button>
        <button type="button" class="cc-btn cc-p" data-cc="accept">Accept all</button>
      </div>`;
    document.body.appendChild(banner);
    banner.addEventListener('click', e => {
      const b = e.target.closest('[data-cc]'); if (!b) return;
      if (b.dataset.cc === 'accept') save(true, true, 'banner_accept');
      if (b.dataset.cc === 'reject') save(false, false, 'banner_reject');
      if (b.dataset.cc === 'custom') openModal();
    });
    requestAnimationFrame(() => banner.classList.add('in'));
  }
  function row(key, title, desc, on, locked) {
    return `<div class="cc-row"><div><b>${esc(title)}</b><p>${esc(desc)}</p></div>
      <label class="cc-sw"><input type="checkbox" data-cat="${key}" ${on ? 'checked' : ''} ${locked ? 'checked disabled' : ''} aria-label="${esc(title)}"><i></i></label></div>`;
  }
  function openModal() {
    lastFocus = document.activeElement;
    if (!modal) {
      modal = document.createElement('div');
      modal.className = 'cc-modal'; modal.setAttribute('role', 'dialog'); modal.setAttribute('aria-modal', 'true'); modal.setAttribute('aria-labelledby', 'cc-title');
      document.body.appendChild(modal);
      modal.addEventListener('click', e => {
        if (e.target === modal || e.target.closest('[data-cc=close]')) closeModal();
        const b = e.target.closest('[data-cc]'); if (!b) return;
        const a = modal.querySelector('[data-cat=a]'), m = modal.querySelector('[data-cat=m]');
        if (b.dataset.cc === 'save') save(a ? a.checked : false, m ? m.checked : false, 'settings_save');
        if (b.dataset.cc === 'accept') save(true, true, 'settings_accept');
        if (b.dataset.cc === 'reject') save(false, false, 'settings_reject');
      });
      modal.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    }
    // Respect Global Privacy Control: suggest marketing off by default.
    const gpc = navigator.globalPrivacyControl === true;
    const a = state ? state.a : false, m = state ? state.m : false;
    modal.innerHTML = `<div class="cc-panel">
      <div class="cc-head"><h2 id="cc-title">Cookie settings</h2><button type="button" class="cc-x" data-cc="close" aria-label="Close">×</button></div>
      <p class="cc-intro">Choose what we may use. You can change this any time from "Cookie settings" in the footer.</p>
      ${row('n', 'Strictly necessary', 'Security, load balancing and remembering this choice. Always on.', true, true)}
      ${row('a', 'Analytics', 'Helps us understand which pages are visited and how the site performs' + (IDS.ga4 || IDS.clarity ? ' (' + [IDS.ga4 && 'Google Analytics', IDS.clarity && 'Microsoft Clarity'].filter(Boolean).join(', ') + ')' : '') + '. Data is aggregated.', a, false)}
      ${row('m', 'Marketing', 'Lets us measure ad campaigns and show you relevant ads on other sites' + (hasMarketing ? ' (' + [IDS.ads && 'Google Ads', IDS.meta && 'Meta', IDS.li && 'LinkedIn'].filter(Boolean).join(', ') + ')' : '') + '.' + (gpc && !state ? ' Your browser sent a Global Privacy Control signal, so this starts off.' : ''), m, false)}
      <div class="cc-acts">
        <button type="button" class="cc-btn" data-cc="reject">Reject all</button>
        <button type="button" class="cc-btn" data-cc="save">Save choices</button>
        <button type="button" class="cc-btn cc-p" data-cc="accept">Accept all</button>
      </div></div>`;
    modal.classList.add('open');
    document.body.classList.add('cc-lock');
    setTimeout(() => modal.querySelector('[data-cat=a]').focus(), 50);
  }
  function closeModal() { if (!modal) return; modal.classList.remove('open'); document.body.classList.remove('cc-lock'); lastFocus && lastFocus.focus && lastFocus.focus(); }
  function hide() { closeModal(); if (banner) { banner.classList.remove('in'); setTimeout(() => banner && banner.remove(), 400); banner = null; } }

  /* ---------- public helpers ---------- */
  // Conversion event for a submitted enquiry. Only fires on platforms the visitor consented to.
  window.foaiTrack = function (event, eid) {
    if (!state) return;
    if (event === 'signup') {
      if (state.a && IDS.ga4) gtag('event', 'sign_up', { method: 'waitlist' });
      if (state.m && IDS.ads && IDS.adsLead) gtag('event', 'conversion', { send_to: IDS.ads + '/' + IDS.adsLead });
      if (state.m && IDS.meta && window.fbq) window.fbq('track', 'CompleteRegistration', {}, eid ? { eventID: eid } : {});
      return;
    }
    if (event !== 'lead') return;
    if (state.a && IDS.ga4) gtag('event', 'generate_lead', { form: 'contact' });
    if (state.m && IDS.ads && IDS.adsLead) gtag('event', 'conversion', { send_to: IDS.ads + '/' + IDS.adsLead });
    if (state.m && IDS.meta && window.fbq) window.fbq('track', 'Lead');
  };
  window.foaiConsent = { open: openModal, get: () => state };

  /* ---------- start ---------- */
  function init() {
    document.querySelectorAll('[data-cookie-settings]').forEach(b => b.addEventListener('click', openModal));
    if (location.hash === '#cookie-settings') openModal();
    if (state) { apply(state); return; }
    // No tags configured yet: nothing optional to consent to, so don't show a banner.
    if (!hasAnalytics && !hasMarketing) return;
    // Wait for the page to settle so the banner never delays first paint.
    setTimeout(buildBanner, 900);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
