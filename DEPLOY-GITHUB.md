# Deploying figuredoutai.com from GitHub

```
you push to main ──► GitHub Action: PHP lint · blocks data/secrets · smoke-tests pages
                           │ only if everything passes
                           ▼
                     branch `deploy` (= contents of public_html, nothing else)
                           │ Hostinger Git auto-deploy (webhook)
                           ▼
                     /public_html on Hostinger      content/ and uploads/ are never touched
```

Visitor data (waitlist, consent log, leads, admin login, settings with API keys) lives only in
`public_html/content/` and `public_html/uploads/` on the server. Neither folder is in git; both are listed in
`public_html/.gitignore`, so a git pull or clean on the server leaves them alone. The signing secret is
generated on the server into `content/secret.json`, so `app/config.php` holds no secret.

## One-time setup (about 20 minutes, a few minutes of downtime)

Who: **the Hostinger account owner** (Yash). Hostinger disables Git deployment while someone is logged in
through collaborator access, so this cannot be done from a collaborator login.

### 1. Check the build is green
GitHub → FiguredOutAI/FiguredOutAI-web → **Actions** → the latest "Check and publish" run is green, and a
branch called `deploy` exists.

### 2. Back up the live data
1. hPanel → Websites → figuredoutai.com → **Files → Backups** → generate a new backup (wait for it to finish).
2. **File Manager** → `public_html` → select `content` and `uploads` → **Download** (keep the zip on your PC).

### 3. Move the live data out of the way
Hostinger only clones into an empty folder.
1. File Manager → `public_html` → select **content** → **Move** → to `domains/figuredoutai.com/` (one level up)
   and rename it `content-live`. Do the same with **uploads** → `uploads-live`.
2. Select everything left in `public_html` → **Delete**. The site is down from here until step 5.

### 4. Connect GitHub
1. hPanel → Websites → figuredoutai.com → **Advanced → GIT** → **Connect with GitHub**.
2. On GitHub, install the Hostinger app for **Only select repositories → FiguredOutAI/FiguredOutAI-web**.
   Do not grant it all repositories. It only needs read access to code.
3. Back in hPanel: repository `FiguredOutAI/FiguredOutAI-web`, branch **`deploy`** (not `main`),
   install path empty (= `public_html`). Click **Create / Deploy**.
4. Turn on **Auto deployment**. If hPanel shows a webhook URL instead, copy it and add it in GitHub →
   repo → Settings → Webhooks → Add webhook (content type `application/json`, just the push event).

### 5. Put the live data back
File Manager → move `domains/figuredoutai.com/content-live` back into `public_html` as **content**, and
`uploads-live` back as **uploads**.

### 6. Verify (do every line)
| Check | Expected |
| --- | --- |
| https://figuredoutai.com | new homepage, "An AI inbox that handles itself." |
| https://figuredoutai.com/waitlist | waitlist page (not 404) |
| https://figuredoutai.com/content/settings.json | 403 Forbidden |
| https://figuredoutai.com/app/config.php | 403 Forbidden |
| https://figuredoutai.com/.git/config | 403 Forbidden |
| /admin/ | logs in with the existing password, old leads still there |
| Join the waitlist with your own email | "You're in", welcome email arrives (inbox, not spam), Admin → Waitlist shows it; then delete that test entry |

### 7. Switch on full uptime checks
GitHub → repo → Settings → Secrets and variables → Actions → **Variables** → New variable
`SITE_V4` = `true`. The Uptime workflow (every 15 min) then also checks `/waitlist` and `/health.php`.
A failure opens an issue labelled "outage" (GitHub emails you); it closes by itself on recovery.
Watch the repo (Watch → All activity) so those emails reach you.

### 8. Admin → Settings
- Waitlist counter: **Real sign-ups only**, show once it reaches **100**. Do not use the offset mode.
- Resend API key (paste it yourself), sender `Tanishk from FiguredOutAI <hello@figuredoutai.com>`, send the test email.
- Google Client ID, if you want "Continue with Google".

The existing hourly cron (`php .../public_html/cron.php`) keeps working. It now also copies all server data
to `domains/figuredoutai.com/content-backups/<date>` every day (14 days kept, outside the website), and
removes unfinished sign-ups older than 30 days.

## Every change after that
Edit → commit → `git push` to `main`. The Action checks it; if it passes, the site updates by itself in
about a minute. If the Action fails, nothing is deployed and the live site stays as it was.

## Rolling back
`git revert <bad commit>` and push. The Action republishes the previous version. For data, restore from
`content-backups/<date>` or the Hostinger backup.

## Rules that keep this safe
- Never commit anything inside `public_html/content/` or `public_html/uploads/`. The pre-commit hook and the
  Action both block it (enable the hook once per clone: `git config core.hooksPath .githooks`).
- Never put a key or token in `app/config.php`. Keys go in Admin → Settings (stored in `content/`).
- Never upload code zips through File Manager once Git deployment is on: Hostinger would see local
  changes and the next pull can fail. All code changes go through GitHub.
- GitHub secret scanning and push protection are on for this repo.
- Rulesets: `main` and `deploy` can't be force-pushed or deleted. Actions are pinned to commit SHAs and
  Dependabot proposes updates weekly. Pull requests get the same checks but never publish.
