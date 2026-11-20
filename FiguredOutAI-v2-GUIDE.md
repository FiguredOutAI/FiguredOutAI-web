# FiguredOutAI v2: re-upload and brand ranking guide

File to upload: **FiguredOutAI-v2.zip** (52 files, ~515 KB). It's the complete website.
It deliberately contains **no `content/` and no `uploads/` folder**, so your login, settings, leads,
consent records, posts and images on the server are never touched.

---

## Why the site crashed
Hostinger's File Manager doesn't merge folders when you extract a zip. When a folder already exists,
it **renames the old one** (that's where `admin.5636`, `app.6302` and `assets.7657` came from) and creates
a new folder holding only the files that were inside the zip. The update zip only had 2 files for `app/`,
so `app/config.php` and `app/markdown.php` disappeared and every page failed.

The fix is to **delete the code folders first**, then extract the full v2 zip into an empty spot.
v2 also has a safety net: if a core file is ever missing again, visitors see a "back in a few minutes"
page instead of a crash, and the site recreates its own folder protection.

---

## Upload steps (10 minutes)

1. **hPanel → Websites → figuredoutai.com → File manager → open `public_html`.**
2. **Select and delete everything EXCEPT these two folders:**
   - ✅ keep `content` (your login, settings, leads, posts)
   - ✅ keep `uploads` (images you uploaded)
   Delete all of these:
   - folders: `admin`, `admin.5636`, `app`, `app.6302`, `assets`, `assets.7657`, `blog`
   - all files: `.htaccess`, `index.php`, `about.php`, `404.php`, `consent.php`, `contact.php`, `cron.php`, `feed.php`, `privacy.php`, `terms.php`, `sitemap.php`, `robots.txt`, `site.webmanifest`, `favicon.ico`, `favicon-32.png`, `apple-touch-icon.png`, `1b6a662ad711e519bfae8b3c78fb6384.txt`, any old `.zip`
   (Turn on **Show hidden files** so you can see and delete `.htaccess`.)
3. **Upload `FiguredOutAI-v2.zip`** into `public_html`.
4. **Right-click → Extract → into `public_html`** (the current folder, not a new subfolder).
   You should now see `admin`, `app`, `assets`, `blog`, `content`, `uploads` and the files, with **no `.1234`-style folders**.
5. **Delete `FiguredOutAI-v2.zip`.**
6. **hPanel → Dashboard → Cache → Clear cache.**

### Check it worked
- https://figuredoutai.com loads, and the cookie banner appears in a private window
- https://figuredoutai.com/about shows the founders and the social buttons
- https://figuredoutai.com/admin/ shows the login. Log in with your **username + password** as before
- https://figuredoutai.com/llms.txt shows the company summary
- https://figuredoutai.com/app/config.php shows **Forbidden** (it must)

**Golden rule for future updates:** always upload the **full** zip after deleting the code folders. Never
extract a partial zip over existing folders in Hostinger's File Manager.

---

## What's new in v2 for brand and AI search
| Added | Why it helps |
| --- | --- |
| Redirects for 15 old Google URLs (`/services`, `/industries/*`, `/why-us`, `/contact`…) | Old Google results land on the right section instead of a 404, and keep their ranking history |
| `/about` page with founders, LLP number and every spelling of the name | Answers "who is FiguredOutAI / who founded it" for Google and AI assistants |
| Structured data: Organization with `alternateName` (FiguredoutAI, Figured Out AI, FiguredOut AI, FiguredoutAI LLP), founders, LLPIN, socials | Tells Google all the spellings are one company, which is how brand panels start |
| Social buttons + links in the structured data (company LinkedIn and Instagram, your LinkedIn, X and Instagram) | Connects the website, the company profiles and you as one entity |
| `llms.txt` company summary | A clean summary that AI tools (ChatGPT, Perplexity, Claude) can read and cite |
| robots.txt that explicitly welcomes Googlebot, Bingbot, ChatGPT (OAI-SearchBot, GPTBot), Perplexity, Claude, Apple and Gemini crawlers | Makes sure AI answer engines are allowed to read and quote the site |
| FAQ now starts with "What is FiguredOutAI?" | A direct, quotable answer for "what is figuredoutai" searches and AI questions |

---

## Best practices to rank fast for "FiguredOutAI" (do these in order)

### Week 1: foundations (biggest impact)
1. **Google Search Console** → URL Inspection → request indexing for: `/`, `/about`, `/blog/`.
2. **Bing Webmaster Tools** → URL Submission → same 3 URLs. (Bing feeds ChatGPT search and Copilot.)
   Then Admin → SEO & backlinks → **Ping all URLs via IndexNow**.
3. **Google Business Profile** (business.google.com): name **FiguredOutAI**, category "Software company"
   or "Business management consultant", website figuredoutai.com, logo, description. This is the
   strongest single signal for brand searches and can produce a company info panel next to your results.
4. **Make every profile link back to figuredoutai.com, with the identical name "FiguredOutAI":**
   - LinkedIn company page: website field + "About" text that starts with "FiguredOutAI (FiguredoutAI LLP) is…"
   - Your LinkedIn: Experience → FiguredOutAI linked to the company page; website field → figuredoutai.com
   - Yashvardhan's LinkedIn: same, then paste his profile URL in Admin → Settings → Founders
   - Instagram @figuredoutai and your Instagram/X bios: link → figuredoutai.com
5. **Use one description everywhere** (copy-paste exactly):
   > FiguredOutAI (FiguredoutAI LLP) is an AI solutions and AI-native infrastructure partner. We design, build and run AI agents, automation and custom software for businesses worldwide.

### Weeks 2–4: corroboration (what fixes the "did you mean figured out ai")
6. Create profiles with the same name, logo, description and website: **Crunchbase, Clutch, GoodFirms,
   Wellfound, Product Hunt (for the Sales Platform), GitHub organisation, YouTube channel, Medium.**
   Tick each one in Admin → SEO & backlinks.
7. **Publish 2–3 blog posts a week** and share each one on LinkedIn (company page + your profile)
   with the link. Use "FiguredOutAI" in the post naturally.
8. **Get 3–5 mentions on other sites**: a founder interview, a podcast, a guest post, an Indian
   startup directory (YourStory, Inc42, Startup India). Ask them to write the name exactly **FiguredOutAI**
   and link to figuredoutai.com.
9. **Tell people the exact name.** In emails, decks and calls, write "figuredoutai.com". Every time
   someone searches "figuredoutai" and clicks your site, Google learns it's a real brand, not a typo.

### For AI search (ChatGPT, Perplexity, Gemini, Copilot)
10. AI assistants mostly quote **Bing and Google results plus well-known profiles** (LinkedIn,
    Crunchbase, Wikipedia-style directories). Steps 2, 4 and 6 are what get you into their answers.
11. Keep the facts **identical everywhere**: name, founders, founding country, what you do. AI tools
    cross-check sources and prefer consistent facts.
12. After 3–4 weeks, test: ask ChatGPT, Perplexity and Gemini "What is FiguredOutAI?" and "Who founded
    FiguredOutAI?". If an answer is wrong or missing, add that fact to /about and a LinkedIn post.

### What to expect
- **1–2 weeks:** old URLs redirect in Google, /about indexed, "FiguredoutAI LLP" and "figuredoutai.com" searches show you first.
- **3–6 weeks** (after the profiles exist): "figuredoutai" stops being corrected to "figured out ai", and your site plus LinkedIn and Instagram appear together.
- **1–3 months:** a company info panel (Business Profile / Knowledge Panel) and consistent AI-assistant answers.

### Avoid
- Buying links or "SEO packages" promising backlinks: they can get the site penalised.
- Different names in different places (Figured Out AI on one, FiguredoutAI on another). Pick **FiguredOutAI** everywhere.
- Partial uploads over existing folders in Hostinger File Manager (see the golden rule above).
