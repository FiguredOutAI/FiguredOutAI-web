# FiguredOutAI-web

Source of [figuredoutai.com](https://figuredoutai.com): the FiguredOutAI AI inbox (WhatsApp, Instagram and Messenger) with its waitlist, plus the custom AI services site, blog and admin panel. Plain PHP, no build step, hosted on Hostinger.

**The top level of this repo is the website.** Hostinger's GitHub deployment publishes the `main` branch straight into `public_html`. Whatever is on `main` is what's live.

## Layout
| Path | What it is |
| --- | --- |
| `index.php`, `waitlist.php`, … | Public pages |
| `app/` | Shared PHP: layout, product sections (`product.php`), waitlist, marketing, helpers (blocked from the web) |
| `assets/` | CSS, JS (including the three.js hero scene), fonts and images |
| `lp/` | Campaign landing pages, served at `/lp/<name>` (see `docs/LANDING-PAGES.md`) |
| `admin/` | Admin panel: posts, leads, waitlist, settings |
| `docs/`, `dev/` | Guides and the local dev router (blocked from the web by `.htaccess`) |

## Run locally
```bash
git config core.hooksPath .githooks
php -S localhost:8080 -t . dev/router.php
```
Needs PHP 8.2+ with mbstring, openssl and curl.

## Not in this repo
`content/` and `uploads/` hold live server data (waitlist sign-ups, consent log, leads, admin login, API keys in settings). They exist only on the server, are listed in `.gitignore`, and are never committed. `app/config.php` holds no secrets; the signing secret is generated on the server into `content/secret.json`.

## Shipping a change
1. Work on a branch and open a pull request. CI lints the PHP, blocks data and secrets, checks that private paths aren't served, and runs the waitlist sign-up flow.
2. Merge to `main` when CI is green.
3. hPanel → Advanced → GIT → **Deploy** (or automatically, if auto-deploy is on).

Full guide and checks: [docs/DEPLOY-GITHUB.md](docs/DEPLOY-GITHUB.md).
