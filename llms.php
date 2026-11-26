<?php
/**
 * /llms.txt: a plain-text brief for AI assistants and answer engines (llmstxt.org format).
 * Generated on request so the product facts, FAQ and article list never go stale.
 */
require __DIR__ . '/app/lib.php';
require __DIR__ . '/app/product.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$posts = live_posts();
$mail = cfg('contact_email');
$links = company_links();
$out = [];
$out[] = '# FiguredOutAI';
$out[] = '';
$out[] = '> FiguredOutAI is an AI inbox for WhatsApp, Instagram and Facebook Messenger. It replies to customer messages in the business\'s own tone, scores every chat for buying intent (Hot, Warm or Cold) and routes each one to the right person, with every AI decision logged and a human able to take over at any time. It is in early access with a waitlist. The same team (FiguredoutAI LLP, India) also builds custom AI agents, automation and software for businesses worldwide.';
$out[] = '';
$out[] = '## Product facts';
$out[] = '- Name: FiguredOutAI (also written FiguredoutAI, Figured Out AI)';
$out[] = '- What it is: an AI inbox for businesses that sell and support customers in DMs';
$out[] = '- Channels: WhatsApp, Instagram, Facebook Messenger';
$out[] = '- Core features: AI replies in the brand\'s tone; intent scoring (Hot, Warm, Cold); routing to sales, support or the owner; human handoff; a log of every AI decision; white-label for agencies';
$out[] = '- Built for: D2C and e-commerce brands, real estate brokers, clinics, salons, coaching institutes, agencies';
$out[] = '- Status: early access; join at ' . url('waitlist');
$out[] = '- Pricing: announced before launch';
$out[] = '';
$out[] = '## Company facts';
$out[] = '- Legal entity: FiguredoutAI LLP, LLPIN ACZ-0822, registered under the LLP Act 2008, India';
$out[] = '- Founders: Tanishk Singhal (Co-founder & CEO), Yashvardhan Awasthi (Co-founder & CTO)';
$out[] = '- Headquarters: India; remote-first';
$out[] = '- Website: ' . url('/');
$out[] = '- Contact: ' . $mail;
foreach ($links as $k => $u) $out[] = '- ' . (['linkedin' => 'LinkedIn', 'x' => 'X', 'youtube' => 'YouTube'][$k] ?? ucfirst($k)) . ': ' . $u;
$out[] = '';
$out[] = '## FAQ';
foreach (PRODUCT_FAQ as [$q, $a]) { $out[] = '### ' . $q; $out[] = $a; $out[] = ''; }
$out[] = '## Key pages';
$out[] = '- [Home: the AI inbox](' . url('/') . ')';
$out[] = '- [Join the waitlist](' . url('waitlist') . ')';
$out[] = '- [About and founders](' . url('about') . ')';
$out[] = '- [Blog](' . url('blog/') . ')';
$out[] = '- [Privacy policy](' . url('privacy') . ')';
if ($posts) {
    $out[] = '';
    $out[] = '## Articles';
    foreach ($posts as $p) $out[] = '- [' . $p['title'] . '](' . post_url($p) . '): ' . excerpt($p, 200);
}
$out[] = '';
$out[] = '## Custom AI services';
$out[] = 'Custom AI agents and multi-agent systems, workflow automation, custom software, AI infrastructure and LLMOps, data and RAG systems, conversational and voice AI. Engagements run in four approved phases; clients own the code and logic. Contact: ' . url('/#contact');
echo implode("\n", $out) . "\n";
