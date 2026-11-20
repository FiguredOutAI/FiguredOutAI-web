# Waitlist launch: what to do

Package: `FiguredOutAI-v3-waitlist.zip` (the whole site, without `content/` and `uploads/`, so your live leads, settings and posts stay untouched).
Backup of the previous source: `backups/public_html-2026-11-15-before-waitlist.tgz`.

## 1. Deploy (Hostinger File Manager)
Hostinger renames folders instead of merging them when it extracts a zip, so never extract over existing code folders.
1. In `public_html`, delete every code file and folder **except `content` and `uploads`**.
2. Upload `FiguredOutAI-v3-waitlist.zip` into `public_html`, extract it there, then delete the zip.
3. Open https://figuredoutai.com. You should see "An AI inbox that handles itself."

## 2. Admin → Settings → Waitlist
- **Counter:** start with "Real sign-ups only" and set "Only show the counter once it reaches" to **100**. Until then the site says "Early access opens in batches."
- **Google Client ID:** paste it (see step 3). The "Continue with Google" button appears on its own.
- **Resend API key:** paste it here yourself (don't send it in chat). Sender name, for example: `Tanishk from FiguredOutAI`. Sender address: `hello@figuredoutai.com`.
- Click **Save & send me a test email** and check that it lands in your inbox, not in spam.

## 3. Google sign-in (about 10 minutes)
console.cloud.google.com → new project "FiguredOutAI"
- APIs & Services → OAuth consent screen → External. App name FiguredOutAI, support email hello@figuredoutai.com, logo, privacy URL https://figuredoutai.com/privacy, scopes: only email, profile and openid. Then **Publish app**.
- Credentials → Create credentials → OAuth client ID → Web application. Authorized JavaScript origins: `https://figuredoutai.com`.
- Copy the **Client ID** (ending in `.apps.googleusercontent.com`) into Admin → Settings. No client secret is needed.

## 4. Resend (about 5 minutes)
resend.com → Domains: check that figuredoutai.com shows **Verified**. The DKIM record is already in your DNS. If it isn't verified, add the records Resend lists.
API Keys → Create → permission **Sending access**, domain figuredoutai.com.

## 5. DNS (Hostinger → DNS zone)
Add a TXT record on `@`: `v=spf1 include:_spf.google.com ~all`
(Your domain has no SPF record today, so mail from hello@ is easy to mark as spam.)

## 6. After it's live
- Join the waitlist yourself on https://figuredoutai.com. Check that you get the welcome email, that the team alert reaches hello@, and that you appear in Admin → Waitlist.
- Join again with Google on https://figuredoutai.com/waitlist.
- Search Console → URL inspection → `https://figuredoutai.com/` → Request indexing (the homepage title and description changed).

## Campaign links
Use the landing page with UTM tags so Admin → Waitlist shows where people came from:
`https://figuredoutai.com/waitlist?utm_source=instagram&utm_medium=paid&utm_campaign=launch1`
`/waitlist` is the product-only version of the homepage, hidden from Google so it doesn't compete with the homepage.
Admin → Waitlist shows top sources and channels, and exports a CSV with everything, including UTM tags.

## Tracking
If GA4 / Google Ads / Meta IDs are saved in Settings, each completed sign-up fires `sign_up` (GA4), your Google Ads conversion label, and `CompleteRegistration` (Meta), only for visitors who accepted cookies.
