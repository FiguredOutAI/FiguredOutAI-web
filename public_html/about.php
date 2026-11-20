<?php
require __DIR__ . '/app/lib.php';
require __DIR__ . '/app/layout.php';
$s = settings();
$linksT = founder_links('tanishk');
$linksY = founder_links('yash');
$person = fn($id, $name, $title, $links) => ['@type' => 'Person', '@id' => url('about#' . $id), 'name' => $name, 'jobTitle' => $title,
    'worksFor' => ['@id' => url('/#organization')]] + ($links ? ['sameAs' => array_values($links)] : []);
page_open([
    'title' => 'About FiguredOutAI | Founders Tanishk Singhal & Yashvardhan Awasthi',
    'description' => 'FiguredOutAI (FiguredoutAI LLP, also written Figured Out AI) is an AI solutions and AI-native infrastructure company founded by Tanishk Singhal and Yashvardhan Awasthi.',
    'canonical' => url('about'),
    'og_title' => 'Meet the founders: Tanishk Singhal & Yashvardhan Awasthi', 'og_kicker' => 'About FiguredOutAI',
    'jsonld' => [
        org_jsonld(),
        ['@context' => 'https://schema.org', '@type' => 'AboutPage', 'url' => url('about'), 'name' => 'About FiguredOutAI',
         'mainEntity' => ['@id' => url('/#organization')], 'isPartOf' => ['@id' => url('/#website')]],
        ['@context' => 'https://schema.org', '@graph' => [
            $person('tanishk-singhal', 'Tanishk Singhal', 'Co-founder & CEO, FiguredOutAI', $linksT),
            $person('yashvardhan-awasthi', 'Yashvardhan Awasthi', 'Co-founder & CTO, FiguredOutAI', $linksY)]],
        ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'About', 'item' => url('about')]]],
    ],
], ['css' => ['css/blog.css', 'css/about.css']]);
$mail = e(cfg('contact_email'));
?>
<main id="main" class="blog-main">
  <section class="blog-hero">
    <div class="wrap">
      <nav class="crumbs mono" aria-label="Breadcrumb"><a href="/">Home</a><span>/</span><span>About</span></nav>
      <h1>About <span class="soft">FiguredOutAI</span></h1>
      <p class="blog-lead">FiguredOutAI (also written FiguredoutAI or Figured Out AI) is an AI solutions provider and AI-native infrastructure partner for businesses worldwide. We design, build and run the AI systems that take repetitive work off a team's plate: agents, automations, custom software and the infrastructure underneath them.</p>
    </div>
  </section>

  <section class="about-body">
    <div class="wrap about-grid">
      <div class="prose">
        <h2 id="what-we-do">What we do</h2>
        <p>Most companies now have access to AI. Far fewer have it wired into how they actually operate. FiguredOutAI closes that gap. We work end to end: we find where AI pays back fastest, design the system, build it, run it alongside your current process until the numbers prove it, then operate and improve it or hand it over to your team.</p>
        <ul>
          <li><strong>AI agents and multi-agent systems</strong> that research, plan, act and check each other's work.</li>
          <li><strong>Workflow automation</strong> across the tools a business already uses.</li>
          <li><strong>Custom software</strong> built AI-first around how a business works.</li>
          <li><strong>AI infrastructure and LLMOps</strong>: deployment, evaluation, observability and cost control.</li>
          <li><strong>The FiguredOutAI Unified AI Sales Platform</strong>: one AI inbox for every sales channel, with intent scoring and routing, available to agencies under their own brand.</li>
        </ul>
        <p>Read more on our <a href="/#capabilities">capabilities</a>, <a href="/#process">how we work</a> and the <a href="/blog/">FiguredOutAI blog</a>.</p>

        <h2 id="founders">Founders</h2>
        <div class="founders">
          <article class="founder" id="tanishk-singhal">
            <div class="f-ph">TS</div>
            <div>
              <h3>Tanishk Singhal</h3>
              <div class="f-role mono">Co-founder & CEO</div>
              <p>Tanishk leads strategy, product and client partnerships at FiguredOutAI, turning business problems into AI systems worth building.</p>
              <?= social_buttons($linksT, 'Tanishk Singhal') ?>
            </div>
          </article>
          <article class="founder" id="yashvardhan-awasthi">
            <div class="f-ph o">YA</div>
            <div>
              <h3>Yashvardhan Awasthi</h3>
              <div class="f-role mono o">Co-founder & CTO</div>
              <p>Yashvardhan has eight years of experience shipping production software. He owns architecture, engineering quality and the infrastructure under every FiguredOutAI build.</p>
              <?= social_buttons($linksY, 'Yashvardhan Awasthi') ?>
            </div>
          </article>
        </div>

        <h2 id="how-we-work">How we work</h2>
        <p>Every engagement runs in four phases (discover, architect, build and validate, operate) and you approve each phase before the next begins. You own the code, prompts and business logic we build. We are model-agnostic and pick the right model for each task. And we take on a limited number of engagements at a time, so the people you meet are the people who build.</p>
      </div>

      <aside class="facts">
        <h2 class="mono">Company facts</h2>
        <dl>
          <dt>Name</dt><dd>FiguredOutAI</dd>
          <dt>Also written</dt><dd>FiguredoutAI, Figured Out AI</dd>
          <dt>Legal entity</dt><dd>FiguredoutAI LLP</dd>
          <dt>Registration</dt><dd>LLPIN ACZ-0822, registered under the LLP Act 2008, India. Verifiable on the MCA portal.</dd>
          <dt>Founders</dt><dd>Tanishk Singhal, Yashvardhan Awasthi</dd>
          <dt>Headquarters</dt><dd>India · remote-first, serving clients worldwide</dd>
          <dt>Website</dt><dd><a href="/">figuredoutai.com</a></dd>
          <dt>Email</dt><dd><a href="mailto:<?= $mail ?>"><?= $mail ?></a></dd>
          <?php if ($co = social_buttons(company_links(), 'FiguredOutAI')): ?><dt>Follow</dt><dd><?= $co ?></dd><?php endif; ?>
        </dl>
        <a class="btn btn-p" href="/#contact">Start a project</a>
      </aside>
    </div>
  </section>
</main>
<?php page_close();
