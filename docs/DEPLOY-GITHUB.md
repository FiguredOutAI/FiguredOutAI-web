# Deploying figuredoutai.com from GitHub

```
branch → pull request ──► CI (lint · no data/secrets · private paths blocked · waitlist flow)
                              │ merge only when green
                              ▼
                            main  ──► Hostinger GitHub deployment ──► public_html
                                      content/ and uploads/ (server data) are left alone
```

The top level of the repo is the website. Hostinger clones `main` and publishes it into `public_html`.
Visitor data (waitlist sign-ups, consent log, leads, admin login, settings with API keys) lives only in
`content/` and `uploads/` on the server. Both are in `.gitignore` and never in git.

## Current setup (Hostinger)
- hPanel → Websites → figuredoutai.com → **Advanced → GIT**: repository `FiguredOutAI/FiguredOutAI-web`,
  branch **`main`**, install path empty (= `public_html`).
- Only the account owner can open this page; it's disabled under collaborator access.
- Backup of the live data taken before the first deploy: `domains/figuredoutai.com/content-backup-2026-11-21/`.
  The hourly cron also snapshots it daily to `domains/figuredoutai.com/content-backups/` (14 days kept).

## Every change
1. `git switch -c my-change`, edit, commit, `git push -u origin my-change`, open a pull request.
2. Wait for **CI** to go green. If it's red, fix it before merging: merging is what goes live.
3. Merge into `main`.
4. hPanel → Advanced → GIT → **Deploy** (skip if auto-deploy is on).
5. Check the live site (table below).

## Check after every deploy
| Check | Expected |
| --- | --- |
| https://figuredoutai.com | "An AI inbox that handles itself." |
| https://figuredoutai.com/waitlist | waitlist page |
| https://figuredoutai.com/health.php | `"ok":true` |
| /content/settings.json, /app/config.php, /docs/DEPLOY.md, /README.md, /.git/config | 403 or 404, never 200 |
| /admin/ | asks for the existing login (never "Create admin account") |
| Join the waitlist with your own email | "You're in", welcome email arrives, Admin → Waitlist shows it; delete the test entry |

The Uptime workflow checks the first rows every 15 minutes. Set the repo variable `SITE_V4` = `true`
(Settings → Secrets and variables → Actions → Variables) so it also checks `/waitlist` and `/health.php`.

## Rolling back
Revert the bad commit on GitHub (or `git revert <sha>` and push), merge, then **Deploy** again.
For data, restore from `content-backups/<date>` or `content-backup-2026-11-21`.

## Rules that keep this safe
- Never commit `content/` or `uploads/`. The pre-commit hook (`git config core.hooksPath .githooks`) and CI both block it.
- Never put a key or token in `app/config.php`. Keys go in Admin → Settings (stored in `content/`).
- Never upload code through File Manager any more. All code changes go through GitHub.
- GitHub secret scanning and push protection are on; `main` can't be force-pushed or deleted.
