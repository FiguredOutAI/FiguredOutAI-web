# FiguredOutAI-web

Source of [figuredoutai.com](https://figuredoutai.com): the FiguredOutAI AI inbox (WhatsApp, Instagram and Messenger) with its waitlist, plus the custom AI services site, blog and admin panel. Plain PHP, no build step, hosted on Hostinger.

## Layout
| Path | What it is |
| --- | --- |
| `public_html/` | The site itself; this folder is what goes on the server |
| `public_html/app/` | Shared PHP: layout, product sections (`product.php`), waitlist, helpers |
| `public_html/assets/` | CSS, JS (including the three.js hero scene), fonts and images |
| `public_html/lp/` | Campaign landing pages, served at `/lp/<name>` (see `LANDING-PAGES.md`) |
| `public_html/admin/` | Admin panel: posts, leads, waitlist, settings |
| `dev/router.php` | Local router that mirrors `.htaccess` for `php -S` |

## Run locally
```bash
git config core.hooksPath .githooks
php -S localhost:8080 -t public_html dev/router.php
```
Needs PHP 8.2+ with mbstring, openssl and curl.

## Not in this repo
`public_html/content/` and `public_html/uploads/` hold live server data (leads, waitlist sign-ups, admin login, API keys in settings). They stay on the server and are never committed. `app/config.php` holds no secrets; the signing secret is generated on the server into `content/secret.json`.

## Deploy
Push to `main`. A GitHub Action checks the build and publishes `public_html` to the `deploy` branch, which Hostinger deploys. One-time setup and the full checklist: [DEPLOY-GITHUB.md](DEPLOY-GITHUB.md).
