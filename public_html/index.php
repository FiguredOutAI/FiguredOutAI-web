<?php
require __DIR__ . '/app/lib.php';
require __DIR__ . '/app/layout.php';
require __DIR__ . '/app/product.php';
$services_faq = [
  ['Do you build custom AI too?', 'Yes. FiguredOutAI (also written FiguredoutAI or Figured Out AI) designs, builds and runs AI agents, workflow automation, custom software and AI infrastructure for businesses worldwide. Use the "Need something custom?" form below.'],
  ['What kind of businesses do you work with?', "Companies of any size, anywhere in the world, that have repetitive operations, high volumes of conversations or data, or a product that needs AI built in. If there's a process people do by hand every day, it's a candidate."],
  ['Do you build on OpenAI, Anthropic or open-source models?', "All of them. We're model-agnostic and choose per task based on quality, latency, cost and data-residency needs. Systems are designed so models can be swapped without a rebuild."],
  ['How is pricing structured?', 'A one-time setup fee for discovery, design and build, then an optional retainer for managed operations. Infrastructure is billed transparently. Payment is milestone-gated: you approve and pay per phase. The discovery audit is free.'],
  ['Who owns what you build?', "You do. The code, prompts, business logic and documentation are yours. There's no proprietary lock-in, and we can hand everything over to your team at any point."],
  ['How do you keep our data secure?', 'Least-privilege access, encryption in transit and at rest, and deployment in your cloud when required. AI actions are logged and scoped, and sensitive steps keep a human in the loop.'],
];
$faq = array_merge(PRODUCT_FAQ, $services_faq);
page_open([
  'title' => 'FiguredOutAI | The AI Inbox That Handles Itself',
  'description' => 'One AI inbox for WhatsApp, Instagram and Messenger that replies, spots buyers and routes every chat. Join the waitlist. We build custom AI too.',
  'canonical' => url('/'),
  'og_title' => 'An AI inbox that handles itself', 'og_kicker' => 'WhatsApp · Instagram · Messenger',
  'extra' => waitlist_head_js(),
  'jsonld' => [
    org_jsonld(),
    website_jsonld(),
    product_jsonld(),
    ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($q) => ['@type' => 'Question', 'name' => $q[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q[1]]], $faq)],
  ],
], ['home' => true]);
?>
<main id="main">
<?php product_hero(); product_channels(); product_features(); ?>

<!-- ================= SERVICES DIVIDER ================= -->
<section class="divider">
  <div class="wrap">
    <div class="eyebrow">Beyond the inbox</div>
    <h2>Oh, and we build <span class="soft">custom AI too</span></h2>
    <p>Same team, bigger builds. Agents, automations and software for companies that want AI running the boring parts. <a href="#contact" class="tlink">Tell us what to automate →</a></p>
  </div>
</section>

<!-- ================= MARQUEE ================= -->
<div class="marquee-sec">
  <div class="marquee-label"><span class="eyebrow">Models and tools we build with</span></div>
  <div class="marquee"><div class="track" id="track"></div></div>
</div>

<!-- ================= MANIFESTO ================= -->
<section class="manifesto">
  <div class="wrap">
    <div class="eyebrow">Our view</div>
    <p id="manifesto">Every company will have access to AI. Very few will have it <span class="soft">wired into how they actually operate.</span> That gap is where we work: turning models into dependable systems that run your business, around the clock.</p>
    <div class="sig mono">FiguredOutAI: automate everything that can be automated</div>
  </div>
</section>

<!-- ================= CAPABILITIES ================= -->
<section id="capabilities" class="solid panel-sec">
  <div class="wrap">
    <div class="sec-head center reveal">
      <div><div class="eyebrow">Capabilities</div><h2>One partner for <span class="soft">the entire AI stack</span></h2></div>
      <p>From a single automation to a fleet of cooperating agents, we scope, engineer and operate AI systems that hold up in production.</p>
    </div>

    <div class="bento">
      <article class="card tilt span4 feature reveal">
        <svg class="agent-viz" viewBox="0 0 320 260" aria-hidden="true">
          <line x1="160" y1="130" x2="60" y2="50"/><line x1="160" y1="130" x2="270" y2="50"/><line x1="160" y1="130" x2="50" y2="200"/><line x1="160" y1="130" x2="275" y2="210"/><line x1="160" y1="130" x2="165" y2="20"/>
          <line class="flow" x1="60" y1="50" x2="160" y2="130"/><line class="flow" x1="160" y1="130" x2="275" y2="210"/><line class="flow" x1="270" y1="50" x2="160" y2="130"/><line class="flow" x1="160" y1="130" x2="50" y2="200"/>
                    <circle class="core" cx="160" cy="130" r="26"/><text x="160" y="134" text-anchor="middle" class="core-t">Lead</text>
          <circle class="n" cx="60" cy="50" r="16"/><text x="60" y="80" text-anchor="middle">Research</text>
          <circle class="n" cx="270" cy="50" r="16"/><text x="270" y="80" text-anchor="middle">Planner</text>
          <circle class="n" cx="50" cy="200" r="16"/><text x="50" y="230" text-anchor="middle">Executor</text>
          <circle class="n" cx="275" cy="210" r="16"/><text x="275" y="240" text-anchor="middle">Reviewer</text>
          <circle class="n" cx="165" cy="20" r="11"/>
        </svg>
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="3"/><circle cx="4" cy="5" r="2"/><circle cx="20" cy="5" r="2"/><circle cx="4" cy="19" r="2"/><circle cx="20" cy="19" r="2"/><path d="M6 6l4 4M18 6l-4 4M6 18l4-4M18 18l-4-4"/></svg></div>
        <h3>Custom AI agents & multi-agent systems</h3>
        <p style="max-width:380px">Agents that research, plan, act and check each other's work, connected to your tools with guardrails, memory and human approval where it matters.</p>
        <div class="tags"><span>Orchestration</span><span>Tool use</span><span>Memory</span><span>Human-in-the-loop</span></div>
      </article>

      <article class="card tilt span2 reveal">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M13 2L4 14h7l-1 8 9-12h-7z"/></svg></div>
        <h3>Workflow automation</h3>
        <p>Remove the manual work between your systems. Every repeatable process, automated and monitored.</p>
        <div class="pipe"><span>Trigger</span><em>→</em><span>AI step</span><em>→</em><span>Action</span></div>
      </article>

      <article class="card tilt span2 reveal">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 6l-6 6 6 6M16 6l6 6-6 6M14 4l-4 16"/></svg></div>
        <h3>Custom software</h3>
        <p>Web apps, internal tools and platforms, built AI-first around how your business already works.</p>
        <div class="tags"><span>Web</span><span>Mobile</span><span>Internal tools</span></div>
      </article>

      <article class="card tilt span2 reveal">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 7h.01M7 17h.01"/></svg></div>
        <h3>AI infrastructure & LLMOps</h3>
        <p>Deployment, evaluation, observability and cost control, so your AI stays fast, accurate and affordable at scale.</p>
        <div class="term"><span class="ok">✓</span> eval suite · 412 cases passed<br><span class="ok">✓</span> p95 latency 1.2s<br><span class="w">!</span> cost guard: route → small model</div>
      </article>

      <article class="card tilt span2 reveal">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg></div>
        <h3>Data, RAG & knowledge systems</h3>
        <p>Turn documents, CRMs and databases into a knowledge layer your AI can search, cite and reason over.</p>
        <div class="tags"><span>Vector search</span><span>Integrations</span><span>Citations</span></div>
      </article>

      <article class="card tilt span2 reveal">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2a3 3 0 00-3 3v7a3 3 0 006 0V5a3 3 0 00-3-3z"/><path d="M19 10v2a7 7 0 01-14 0v-2M12 19v3"/></svg></div>
        <h3>Conversational & voice AI</h3>
        <p>AI that answers on WhatsApp, web chat, email and phone, understands intent and hands off to your team with full context.</p>
      </article>

      <article class="card tilt span2 reveal">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 15l3-3 2 2 3-4"/></svg></div>
        <h3>AI-produced content</h3>
        <p>Content engines for video, social and marketing: on-brand, at volume, with review built in.</p>
      </article>

      <article class="card tilt span2 reveal">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M17 11a3 3 0 100-6M21 20c0-2.6-1.6-4.8-4-5.6"/></svg></div>
        <h3>Workforce management software</h3>
        <p>Scheduling, task routing and performance visibility for distributed teams, with AI doing the coordination.</p>
      </article>
    </div>
  </div>
</section>

<!-- ================= STACK ================= -->
<section id="stack">
  <div class="wrap">
    <div class="sec-head reveal">
      <div><div class="eyebrow">Infrastructure</div><h2>The layers under <span class="soft">every system we ship</span></h2></div>
      <p>We don't bolt a chatbot on top. We build the full stack, so the system is reliable, observable and yours to keep.</p>
    </div>
    <div class="stack-grid">
      <div class="layers reveal" id="layers">
        <div class="layers-inner" id="layersInner">
          <div class="layer" data-i="0"><span>05 · Infra & observability</span></div>
          <div class="layer" data-i="1"><span>04 · Data & integrations</span></div>
          <div class="layer" data-i="2"><span>03 · Models & evaluation</span></div>
          <div class="layer" data-i="3"><span>02 · Agents & logic</span></div>
          <div class="layer top" data-i="4"><span>01 · Interfaces</span></div>
        </div>
      </div>
      <div class="stack-list" id="stackList">
        <div class="sl reveal" data-l="4"><span class="n">01</span><div><h4>Interfaces</h4><p>Chat, voice, dashboards, inboxes and apps. Wherever your customers and team already are.</p></div></div>
        <div class="sl reveal" data-l="3"><span class="n">02</span><div><h4>Agents & business logic</h4><p>Your rules, workflows and decisions, encoded and editable as configuration rather than code.</p></div></div>
        <div class="sl reveal" data-l="2"><span class="n">03</span><div><h4>Models & evaluation</h4><p>The right model for each task, with test suites that catch regressions before your customers do.</p></div></div>
        <div class="sl reveal" data-l="1"><span class="n">04</span><div><h4>Data & integrations</h4><p>CRMs, ERPs, databases and APIs, connected securely with retrieval over your own knowledge.</p></div></div>
        <div class="sl reveal" data-l="0"><span class="n">05</span><div><h4>Infrastructure & observability</h4><p>Cloud deployment, logging, tracing, cost controls and uptime monitoring, run by us or by your team.</p></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ================= PROCESS ================= -->
<section id="process" class="process">
  <div class="wrap">
    <div class="sec-head reveal">
      <div><div class="eyebrow">How we work</div><h2>From first call to production, <span class="soft">in four gated phases</span></h2></div>
      <p>You approve each phase before the next one starts. No black boxes, no surprise invoices.</p>
    </div>
    <div class="steps">
      <div class="step reveal"><div class="num">01</div><h4>Discover</h4><p>We map your operations, data and goals, then find where AI earns its keep. You get a clear roadmap, free of obligation.</p><div class="meta mono">Audit · Roadmap</div></div>
      <div class="step reveal"><div class="num">02</div><h4>Architect</h4><p>We design the system: agents, logic, models, integrations and success metrics. All documented, all signed off by you.</p><div class="meta mono">Design · Spec</div></div>
      <div class="step reveal"><div class="num">03</div><h4>Build & validate</h4><p>We engineer it, test it against real scenarios and run it in parallel with your current process before going live.</p><div class="meta mono">Build · Parallel run</div></div>
      <div class="step reveal"><div class="num">04</div><h4>Operate & scale</h4><p>We monitor, tune and extend the system as your business grows, or hand it over to your team with full docs.</p><div class="meta mono">Managed ops · Handover</div></div>
    </div>
    <div class="guarantees reveal">
      <div><strong class="c">24/7</strong><span>Systems that work while your team sleeps</span></div>
      <div><strong><span class="count" data-to="4">0</span></strong><span>Milestone-gated phases, each approved by you</span></div>
      <div><strong><span class="count" data-to="100">0</span>%</strong><span>Ownership of the code, logic and IP we build</span></div>
      <div><strong class="o">0</strong><span>Lock-in. Model-agnostic and portable by design</span></div>
    </div>
  </div>
</section>

<!-- ================= INDUSTRIES ================= -->
<section id="industries">
  <div class="wrap">
    <div class="sec-head reveal">
      <div><div class="eyebrow">Where we apply it</div><h2>Built for operators, <span class="soft">across industries</span></h2></div>
      <p>The patterns repeat: high volume, repetitive decisions and teams stretched thin. That's where AI pays back fastest.</p>
    </div>
    <div class="ind-grid reveal">
      <div class="ind"><span class="k">01</span><h4>Healthcare & clinics</h4><p>Patient intake, triage, scheduling and follow-ups.</p></div>
      <div class="ind"><span class="k">02</span><h4>Real estate</h4><p>Lead qualification, site-visit booking and portal sync.</p></div>
      <div class="ind"><span class="k">03</span><h4>E-commerce & D2C</h4><p>Support automation, catalogue ops and retention flows.</p></div>
      <div class="ind"><span class="k">04</span><h4>Agencies</h4><p>White-label AI products and delivery automation.</p></div>
      <div class="ind"><span class="k">05</span><h4>Hospitality & travel</h4><p>Bookings, itineraries and guest communication at scale.</p></div>
      <div class="ind"><span class="k">06</span><h4>Recruitment</h4><p>Candidate screening, shortlisting and outreach.</p></div>
      <div class="ind"><span class="k">07</span><h4>Field services</h4><p>Job routing, dispatch and technician workflows.</p></div>
      <div class="ind"><span class="k">08</span><h4>SaaS & startups</h4><p>AI features, copilots and internal tooling, shipped fast.</p></div>
    </div>
    <div class="globe-row reveal">
      <span class="mono">Remote-first · serving clients worldwide</span>
      <div class="tz mono" id="tz"></div>
    </div>
  </div>
</section>

<!-- ================= TESTIMONIALS ================= -->
<section id="voices" class="voices">
  <div class="wrap">
    <div class="sec-head center reveal">
      <div><div class="eyebrow">Voices</div><h2>What working with us <span class="soft">feels like</span></h2></div>
      <p>Operators who wanted AI that actually runs part of the business, not another demo.</p>
    </div>
  </div>
  <div class="t-rows reveal">
    <div class="t-track" id="t1"></div>
    <div class="t-track rev" id="t2"></div>
  </div>
</section>

<!-- ================= PRINCIPLES ================= -->
<section id="principles">
  <div class="wrap">
    <div class="sec-head center reveal">
      <div><div class="eyebrow">Why FiguredOutAI</div><h2>Principles <span class="soft">we don't negotiate on</span></h2></div>
    </div>
    <div class="prin">
      <article class="card tilt reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg></div><h3>You own everything</h3><p>Code, prompts, logic and data stay yours. Documented, portable and never held hostage.</p></article>
      <article class="card tilt reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 6h16M4 12h10M4 18h6"/></svg></div><h3>Every decision is logged</h3><p>Each AI action is recorded with its reasoning. Audit it, override it, improve it.</p></article>
      <article class="card tilt reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"/></svg></div><h3>Security by default</h3><p>Least-privilege access, encrypted data and clear boundaries on what AI can touch.</p></article>
      <article class="card tilt reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 12h4l3 8 4-16 3 8h4"/></svg></div><h3>Nothing switched on trust</h3><p>New systems run in parallel with your current process until the numbers prove them.</p></article>
      <article class="card tilt reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 010 18M12 3a14 14 0 000 18"/></svg></div><h3>Model-agnostic</h3><p>We pick the best model for each job and switch when something better ships.</p></article>
      <article class="card tilt reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></div><h3>Senior attention</h3><p>We take on a limited number of engagements at a time, so the people you meet are the people who build.</p></article>
    </div>
  </div>
</section>

<!-- ================= TEAM ================= -->
<section id="team" class="team">
  <div class="wrap team-grid">
    <div class="team-intro">
      <div class="eyebrow">Leadership</div>
      <h2>Builders, <span class="soft">not brokers</span></h2>
      <p>FiguredOutAI is founder-led. Every engagement is scoped and architected by the people who will answer for it in production. <a href="/about" class="tlink">More about FiguredOutAI →</a></p>
    </div>
    <div class="person reveal">
      <div class="ph">TS</div>
      <h4>Tanishk Singhal</h4>
      <div class="role mono">Co-founder & CEO</div>
      <p>Leads strategy, product and client partnerships, turning business problems into AI systems worth building.</p>
      <?= social_buttons(founder_links('tanishk'), 'Tanishk Singhal') ?>
    </div>
    <div class="person p2 reveal">
      <div class="ph">YA</div>
      <h4>Yashvardhan Awasthi</h4>
      <div class="role mono">Co-founder & CTO</div>
      <p>Eight years shipping production software. Owns architecture, engineering quality and the infrastructure under every build.</p>
      <?= social_buttons(founder_links('yash'), 'Yashvardhan Awasthi') ?>
    </div>
  </div>
  <div class="wrap">
    <div class="legal reveal"><span><b>FiguredoutAI LLP</b> · Registered under the LLP Act 2008, India · LLPIN ACZ-0822</span><span class="mono">Verifiable on the MCA portal</span></div>
  </div>
</section>

<!-- ================= BLOG ================= -->
<?php $latest = array_slice(live_posts(), 0, 3); if ($latest): ?>
<section id="insights">
  <div class="wrap">
    <div class="sec-head reveal">
      <div><div class="eyebrow">Insights</div><h2>Notes from <span class="soft">the build floor</span></h2></div>
      <a href="/blog/" class="btn btn-g magnetic">All articles <span class="arr">→</span></a>
    </div>
    <div class="post-grid">
      <?php foreach ($latest as $i => $p): ?>
      <a class="post-card reveal d<?= $i + 1 ?>" href="/blog/<?= e($p['slug']) ?>">
        <div class="pc-img"><?php if ($p['cover']): ?><img src="<?= e($p['cover']) ?>" alt="<?= e($p['cover_alt']) ?>" loading="lazy" decoding="async"><?php else: ?><span class="pc-ph" aria-hidden="true"></span><?php endif; ?></div>
        <div class="pc-body">
          <div class="pc-meta mono"><?= e($p['tags'][0] ?? 'Insight') ?> · <?= reading_time($p['content']) ?> min read</div>
          <h3><?= e($p['title']) ?></h3>
          <p><?= e(excerpt($p, 130)) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ================= FAQ ================= -->
<section id="faq">
  <div class="wrap faq-grid">
    <div>
      <div class="eyebrow">FAQ</div>
      <h2>Questions, <span class="soft">answered</span></h2>
    </div>
    <div class="faq reveal">
      <?php foreach ($faq as $i => $q): ?>
      <details<?= $i === 0 ? ' open' : '' ?>><summary><?= e($q[0]) ?><i></i></summary><div class="ans"><?= e($q[1]) ?></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php product_join(); ?>

<!-- ================= CUSTOM WORK ================= -->
<section id="contact" class="cta custom">
  <div class="wrap">
    <div class="cta-box reveal">
      <div class="eyebrow">Custom builds</div>
      <h2>Need something <span class="soft">custom?</span></h2>
      <p>Tell us what you want automated or built. We reply within one business day, with a plan, not a pitch.</p>
      <form class="form" id="form" method="post" action="/contact.php" novalidate>
        <div><label for="f-name">Name</label><input id="f-name" name="name" required autocomplete="name" placeholder="Your name"></div>
        <div><label for="f-email">Work email</label><input id="f-email" name="email" type="email" required autocomplete="email" placeholder="you@company.com"></div>
        <div><label for="f-co">Company</label><input id="f-co" name="company" autocomplete="organization" placeholder="Company & country"></div>
        <div><label for="f-need">I'm interested in</label>
          <select id="f-need" name="interest"><option>AI agents & multi-agent systems</option><option>Workflow automation</option><option>Custom software</option><option>AI infrastructure & LLMOps</option><option>White-label partnership</option><option>Something else</option></select></div>
        <div class="full"><label for="f-msg">What should we know?</label><textarea id="f-msg" name="message" required placeholder="The process, the problem, the goal…"></textarea></div>
        <div class="hp" aria-hidden="true"><label for="f-web">Website</label><input id="f-web" name="website" tabindex="-1" autocomplete="off"></div>
        <input type="hidden" name="t" value="<?= time() ?>">
        <div class="send"><small id="f-status" role="status">Real humans read these. Usually within a day.</small><button class="btn btn-p magnetic" type="submit">Send enquiry <span class="arr">→</span></button></div>
      </form>
    </div>
  </div>
</section>
</main>
<?php page_close(['home' => true]);
