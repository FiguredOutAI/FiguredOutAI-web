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
cp public_html/app/config.example.php public_html/app/config.php
php -S localhost:8080 -t public_html dev/router.php
```

## Not in this repo
`public_html/content/` and `public_html/uploads/` hold live server data (leads, waitlist sign-ups, admin login, API keys in settings). They stay on the server and are never committed. `app/config.php` is also local only.

## Deploy
Hostinger's File Manager renames folders instead of merging them on extract. To deploy, delete the code files on the server (keep `content` and `uploads`), then upload a zip of `public_html` without those two folders and extract it. Details are in `DEPLOY.md` and `WAITLIST-LAUNCH.md`.
