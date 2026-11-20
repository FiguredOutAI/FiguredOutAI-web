<?php
require __DIR__ . '/app/lib.php';
require __DIR__ . '/app/layout.php';
page_open(['title' => 'Privacy & Cookie Policy | FiguredOutAI', 'description' => 'How FiguredOutAI collects, uses and protects personal information, and how we use cookies.', 'canonical' => url('privacy'), 'og_kicker' => 'Legal'], ['css' => ['css/blog.css']]);
$mail = e(cfg('contact_email'));
$ids = tracking_ids();
// Cookies are listed only for tools that are switched on in Admin → Settings.
$cookies = [['foai_consent', 'FiguredOutAI', 'Necessary', 'Remembers your cookie choices', '6 months']];
if (isset($ids['ga4'])) { $cookies[] = ['_ga, _ga_*', 'Google Analytics', 'Analytics', 'Distinguishes visitors and sessions to measure site usage', '2 years']; }
if (isset($ids['clarity'])) { $cookies[] = ['_clck, _clsk, CLID', 'Microsoft Clarity', 'Analytics', 'Heatmaps and anonymised session recordings to improve the site', 'Up to 1 year']; }
if (isset($ids['ads'])) { $cookies[] = ['_gcl_au, _gcl_aw', 'Google Ads', 'Marketing', 'Measures ad conversions and builds remarketing audiences', '90 days']; }
if (isset($ids['meta'])) { $cookies[] = ['_fbp, _fbc', 'Meta (Facebook, Instagram)', 'Marketing', 'Measures ads and shows relevant ads to past visitors', '90 days']; }
if (isset($ids['li'])) { $cookies[] = ['li_sugr, bcookie, lidc, UserMatchHistory', 'LinkedIn', 'Marketing', 'Measures LinkedIn ads and enables retargeting', 'Up to 1 year']; }
?>
<main id="main" class="blog-main">
  <article class="article">
    <header class="art-head wrap"><nav class="crumbs mono"><a href="/">Home</a><span>/</span><span>Privacy</span></nav><h1>Privacy & Cookie Policy</h1><div class="art-meta mono">Last updated <?= date('j M Y', filemtime(__FILE__)) ?></div></header>
    <div class="art-grid wrap" style="grid-template-columns:minmax(0,720px)"><div class="prose">
      <p>This policy explains how FiguredoutAI LLP ("FiguredOutAI", "we") handles personal information collected through this website, and how we use cookies.</p>
      <h2 id="collect">What we collect</h2>
      <ul>
        <li><strong>Enquiries:</strong> the name, email, company and message you send through our contact form.</li>
        <li><strong>Waitlist sign-ups:</strong> see <a href="#waitlist">the waitlist section</a> below.</li>
        <li><strong>Usage data (with consent):</strong> pages visited, device and browser type, approximate location (country/city), referring site and how you interact with pages.</li>
        <li><strong>Advertising data (with consent):</strong> cookie and device identifiers that let ad platforms measure our campaigns and show our ads to people who visited this site.</li>
        <li><strong>Server logs:</strong> IP address and browser information kept by our host for security.</li>
        <li><strong>Consent records:</strong> a random ID, the time and the choices you made in our cookie banner. No name or IP address.</li>
      </ul>
      <h2 id="use">How we use it, and our legal basis</h2>
      <ul>
        <li>Replying to enquiries and discussing a potential engagement: <em>your request / legitimate interest</em>.</li>
        <li>Understanding how the site is used so we can improve it: <em>your consent</em>.</li>
        <li>Measuring ads and showing our ads to past visitors (retargeting): <em>your consent</em>.</li>
        <li>Keeping the site secure: <em>legitimate interest</em>.</li>
      </ul>
      <p>We do not sell personal information.</p>

      <h2 id="waitlist">Waitlist</h2>
      <p>When you join the FiguredOutAI waitlist we collect your email address, your name, and, if you choose to add them, your business name, WhatsApp number and the messaging channels you use. If you sign up with Google, Google shares your name, email address and profile picture with us; we never see your Google password. We also record how you found us (campaign tags, the page you landed on and the referring site) and the time you gave consent.</p>
      <p>We use this to tell you about your access, onboard you, send launch updates by email or WhatsApp, and understand which channels to support first. The basis is <em>your consent</em>, which you give by ticking the box on the form. You can withdraw it at any time by replying "unsubscribe" to any email or writing to <a href="mailto:<?= $mail ?>"><?= $mail ?></a>, and we will delete your waitlist record. We keep waitlist records until launch onboarding is done, or until you ask us to delete them. Welcome and update emails are sent through our email provider, Resend. Every email has a one-click unsubscribe link. If you only give an email address and don't finish signing up, we delete it after 30 days.</p>

      <h2 id="marketing">Marketing emails and ads (optional)</h2>
      <p>The waitlist form has a second, optional box that is never ticked for you. If you tick it, we also send you product news, tips and offers, and we may use your email address, phone number and first name to show you relevant ads and to measure them. To do that we:</p>
      <ul>
        <li>keep your details in our email provider, Resend, in a "marketing" list;</li>
        <li>send Meta (Facebook, Instagram) a sign-up event through its Conversions API, with your email, phone and first name scrambled into a one-way code (SHA-256 hash) on our server first, plus your IP address, browser and Meta cookie identifiers if you allowed marketing cookies;</li>
        <li>may upload the same scrambled details to Meta Custom Audiences or Google Customer Match, to show you ads or to leave you out of ads for the waitlist you already joined.</li>
      </ul>
      <p>The basis is your consent. Withdraw it any time with the unsubscribe link in any email, by replying "unsubscribe", or by writing to <a href="mailto:<?= $mail ?>"><?= $mail ?></a>. We then stop all marketing and stop sharing your details with ad platforms. If you don't tick the box, none of this happens, and you still get your access updates.</p>

      <h2 id="cookies">Cookies</h2>
      <p>Cookies are small files stored by your browser. We sort them into three groups:</p>
      <ul>
        <li><strong>Strictly necessary:</strong> needed for the site to work and to remember your cookie choice. Always on.</li>
        <li><strong>Analytics:</strong> help us understand visits and improve the site. Only with your consent.</li>
        <li><strong>Marketing:</strong> let advertising partners measure our campaigns and show you relevant ads on other sites. Only with your consent.</li>
      </ul>
      <p>Nothing in the analytics or marketing groups loads until you choose "Accept all" or switch that group on. You can change or withdraw consent at any time. Withdrawing deletes those cookies from this site:</p>
      <p><button type="button" class="btn btn-g btn-sm" data-cookie-settings>Open cookie settings</button></p>
      <div class="table-wrap"><table>
        <thead><tr><th>Cookie</th><th>Provider</th><th>Purpose</th><th>Category</th><th>Expires</th></tr></thead>
        <tbody><?php foreach ($cookies as [$n, $p, $cat, $why, $exp]): ?><tr><td><code><?= e($n) ?></code></td><td><?= e($p) ?></td><td><?= e($why) ?></td><td><?= e($cat) ?></td><td><?= e($exp) ?></td></tr><?php endforeach; ?></tbody>
      </table></div>
      <p>Ad platforms may combine this data with information they already hold about you under their own policies. See <a href="https://policies.google.com/technologies/partner-sites">how Google uses data</a>, <a href="https://www.facebook.com/privacy/policy/">Meta's privacy policy</a> and <a href="https://www.linkedin.com/legal/privacy-policy">LinkedIn's privacy policy</a>. You can also manage ad personalisation in your <a href="https://adssettings.google.com/">Google ad settings</a>. We honour the Global Privacy Control signal where your browser sends it.</p>

      <h2 id="sharing">Sharing and international transfers</h2>
      <p>We share data only with service providers that help us run the site and our marketing (hosting, email, analytics and advertising platforms), under appropriate safeguards, or where the law requires it. Some of these providers process data outside your country, including in the United States.</p>
      <h2 id="retention">Retention</h2>
      <p>Enquiries are kept as long as needed to respond and keep business records. Analytics data is kept for up to 14 months. Consent records are kept for as long as we rely on that consent.</p>
      <h2 id="rights">Your rights</h2>
      <p>You can ask to access, correct or delete your personal information, or object to its use, by emailing <a href="mailto:<?= $mail ?>"><?= $mail ?></a>. Depending on where you live, laws such as India's DPDP Act 2023, the EU/UK GDPR or US state privacy laws may give you further rights, including the right to complain to your data protection authority.</p>
      <h2 id="contact">Contact</h2>
      <p>FiguredoutAI LLP, LLPIN ACZ-0822, India. Email <a href="mailto:<?= $mail ?>"><?= $mail ?></a>.</p>
    </div></div>
  </article>
</main>
<?php page_close();
