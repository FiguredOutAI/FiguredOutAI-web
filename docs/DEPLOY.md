# FiguredOutAI website: upload to Hostinger

Upload file: `figuredoutai-site.zip` (contents of `public_html/`, about 520 KB)

## Before you upload (5 minutes)

### Check `app/config.php`
- `base_url` → `https://figuredoutai.com` (no trailing slash)
- `contact_email` → where enquiries are emailed
- `mail_from` → a mailbox that really exists on your Hostinger domain (Hostinger blocks mail "from" addresses that don't exist)

If you change config.php after building the zip, either re-zip `public_html/` or edit the file in Hostinger's File Manager after upload.

## Upload steps

1. **hPanel → Websites → figuredoutai.com → Advanced → PHP Configuration** → choose **PHP 8.2** (8.1+ works).
2. **hPanel → Security → SSL** → make sure SSL is installed and active.
3. **hPanel → Files → File Manager** → open `public_html`.
   - Delete Hostinger's placeholder files there (usually `default.php` / `index.php` / `index.html`).
   - Click **Upload** → `figuredoutai-site.zip` → right-click it → **Extract** → extract into `public_html` (not into a subfolder).
   - Delete the zip afterwards.
4. Check the hidden files arrived: File Manager → **Settings → Show hidden files**. You should see `.htaccess` in `public_html`, `app/`, `content/` and `uploads/`.
5. Visit **https://figuredoutai.com**. You should see the site, and `http://` and `www.` versions should redirect to `https://figuredoutai.com`.
6. Visit **https://figuredoutai.com/admin/** straight away → **Create admin account** → choose a username and a password (10+ characters) → you're in.
   This screen only appears until an account exists, so do it right after uploading.
7. **Admin → Settings**: add social profile links. Then:
   - Google Search Console → add property `https://figuredoutai.com` → HTML tag method → paste only the `content="…"` value into Settings → Save → Verify → **Sitemaps** → submit `sitemap.xml`.
   - Bing Webmaster Tools → import from Search Console (or paste the msvalidate code in Settings).
   - Optional: Google Analytics 4 measurement ID (`G-…`).
8. **hPanel → Advanced → Cron Jobs** → Custom → every hour (`0 * * * *`) → command:
   `php /home/YOUR_HOSTINGER_USER/public_html/cron.php`
   (Admin → Settings shows the exact path). This announces scheduled posts to Bing/IndexNow when they go live. Posts go live on time even without the cron.
9. Send yourself a test enquiry from the contact form and confirm it arrives by email and shows in **Admin → Leads**.

## Quick live checks
- https://figuredoutai.com/blog/ shows 3 articles
- https://figuredoutai.com/sitemap.xml and /feed.xml load (they're for search engines; they're not linked anywhere on the site and are marked noindex)
- https://figuredoutai.com/app/config.php shows **Forbidden** (it must)
- https://figuredoutai.com/1b6a662ad711e519bfae8b3c78fb6384.txt shows the IndexNow key (it must, Bing checks it)

## Cookies, analytics and retargeting
The cookie banner switches on by itself once you save at least one tracking ID in **Admin → Settings**. Nothing tracks a visitor until they click "Accept all" or turn a category on.

| Setting | Where to get it | Category |
| --- | --- | --- |
| Google Analytics 4 ID (`G-…`) | analytics.google.com → Admin → Data streams → Web | Analytics |
| Microsoft Clarity ID | clarity.microsoft.com → New project → Settings → Overview | Analytics |
| Google Ads tag ID (`AW-…`) | ads.google.com → Tools → Data manager → Google tag | Marketing |
| Google Ads lead conversion label | Goals → Conversions → New → Website → manual → copy the part after `AW-…/` | Marketing |
| Meta Pixel ID | business.facebook.com → Events Manager → Data sources | Marketing |
| LinkedIn partner ID | LinkedIn Campaign Manager → Analyze → Insight Tag | Marketing |

Paste only the IDs. Don't paste the providers' full code snippets anywhere, because the site already contains the consent-aware loaders.

After adding Google tags, open Google Ads → Tools → **Consent settings** and confirm "Consent Mode" is detected. The site sends Consent Mode v2 signals.
To make GA4 and Ads use the same data, link them in GA4 → Admin → Product links → Google Ads.

Retargeting audiences to create once tags have data:
- Google Ads: Audience manager → "All visitors", "Blog readers" (URL contains /blog/), "Visited platform section but no enquiry".
- Meta: Audiences → Website → All visitors (180 days) and Lead event exclusions.
- LinkedIn: Matched audiences → Website → all visitors.

Proof of consent: Admin → Settings → "Download consent records (CSV)".
If you add a new ad platform later, raise `CONSENT_VERSION` in `app/lib.php` so every visitor is asked again.

## Admin login
- Log in at **/admin/** with your username and password.
- Change either one in **Admin → Settings → Login details** (your current password is required).
- **Forgot your login?** In Hostinger File Manager, delete `public_html/content/admin.json`, then open /admin/ and create a new account. Your posts, leads and settings are not affected.
- After 5 wrong attempts from the same connection, login is paused for 15 minutes.

## Daily use
- **Write a post**: Admin → Posts → "+ Plan post" on any day → write → **Schedule** (or **Publish now**). Aim for the green checks in the SEO panel. Type `[[` in the editor to link another post.
- **Leads**: Admin → Leads (status, CSV export).
- **Backlinks**: Admin → SEO & backlinks → claim the listed profiles, use the syndication kit for each new post, log links in the tracker, fix any "Orphan" posts.

## What's in the build
| Path | What it is |
| --- | --- |
| `index.php` | Home page |
| `blog/` | Blog list and article pages |
| `admin/` | Admin panel (password protected, noindex) |
| `app/` | Config and code (blocked from the web) |
| `content/posts/` | Blog posts as JSON (blocked from the web) |
| `uploads/` | Images you upload (scripts can't run here) |
| `assets/` | CSS, JS (incl. `consent.js` cookie banner), fonts, images, three.js (all self-hosted) |
| `contact.php`, `sitemap.php`, `feed.php`, `cron.php`, `consent.php` | Contact form, sitemap.xml, feed.xml, hourly job, consent log |
| `privacy.php`, `terms.php`, `404.php` | Legal pages and not-found page |
| `.htaccess` | HTTPS redirect, clean URLs, compression, caching, security headers |

## Backups
Everything you create lives in `content/` (posts, leads, settings) and `uploads/` (images).
Download those two folders now and then, or rely on Hostinger's daily backups.

## Updating the design later
CSS/JS changes: bump `ASSET_VER` in `app/layout.php` so visitors' browsers fetch the new files.
