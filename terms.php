<?php
require __DIR__ . '/app/lib.php';
require __DIR__ . '/app/layout.php';
page_open(['title' => 'Terms of Use | FiguredOutAI', 'description' => 'Terms of use for the FiguredOutAI website, run by FiguredoutAI LLP: content, intellectual property, acceptable use, liability and governing law.', 'canonical' => url('terms'), 'og_kicker' => 'Legal'], ['css' => ['css/blog.css']]);
$mail = e(cfg('contact_email'));
?>
<main id="main" class="blog-main">
  <article class="article">
    <header class="art-head wrap"><nav class="crumbs mono"><a href="/">Home</a><span>/</span><span>Terms</span></nav><h1>Terms of Use</h1><div class="art-meta mono">Last updated <?= date('j M Y', filemtime(__FILE__)) ?></div></header>
    <div class="art-grid wrap" style="grid-template-columns:minmax(0,720px)"><div class="prose">
      <p>By using this website you agree to these terms. They cover the website only. Client engagements are governed by a separate written agreement.</p>
      <h2>Content</h2>
      <p>Content on this site, including articles, is provided for general information. It is not professional advice for your specific situation.</p>
      <h2>Intellectual property</h2>
      <p>The FiguredOutAI name, logo, design and content belong to FiguredoutAI LLP unless stated otherwise. You may share links and short quotes with attribution.</p>
      <h2>Acceptable use</h2>
      <p>Do not attempt to disrupt the site, access restricted areas, or submit spam or malicious content through our forms.</p>
      <h2>Liability</h2>
      <p>The site is provided "as is". To the extent the law allows, we are not liable for losses arising from its use.</p>
      <h2>Governing law</h2>
      <p>These terms are governed by the laws of India.</p>
      <h2>Contact</h2>
      <p>Questions: <a href="mailto:<?= $mail ?>"><?= $mail ?></a>.</p>
    </div></div>
  </article>
</main>
<?php page_close();
