# Viewing the app on your phone via ngrok

When you open the ngrok URL on your phone (or in another browser), the page can stay blank and the console may show `Failed to load resource: net::ERR_BLOCKED_BY_CLIENT`. Here’s how to fix it.

---

## 1. Use built assets (required)

In **dev**, the page loads JavaScript from **Vite** at `http://localhost:5173`. On your phone, “localhost” is the phone itself, so those scripts never load and the app stays blank.

**Fix:** Serve the app using **built** assets so JS/CSS come from the same ngrok URL.

```bash
# In the project folder
npm run build
```

Then serve the app as usual (e.g. `php artisan serve` or Laragon) and open the **ngrok URL** in the browser. The built JS/CSS are served by Laravel from the same domain, so they load correctly.

---

## 2. Set APP_URL to your ngrok URL (recommended)

So redirects and asset URLs use the ngrok address. **Use `https://`** in APP_URL to avoid "Mixed Content" errors (HTTPS page loading HTTP scripts).

1. Start ngrok: `ngrok http 8000` (or the port you use).
2. Copy the HTTPS URL (e.g. `https://a038-103-180-201-246.ngrok-free.app`).
3. In `.env` set:
   ```env
   APP_URL=https://YOUR-NGROK-URL-HERE
   ```
   Example:
   ```env
   APP_URL=https://a038-103-180-201-246.ngrok-free.app
   ```
4. Clear config and restart the PHP server: run `php artisan config:clear`, then stop and run `php artisan serve` again (or restart Laragon).

When you’re done testing via ngrok, change `APP_URL` back to `http://localhost:8000`.

---

## 2b. Google OAuth (NEMSU login) when using ngrok

The app derives the **Google redirect URI** from `APP_URL` if `GOOGLE_REDIRECT_URI` is not set. So when you set `APP_URL=https://YOUR-NGROK-URL.ngrok-free.app`, the redirect URI becomes `https://YOUR-NGROK-URL.ngrok-free.app/oauth/nemsu/callback`.

**Add this exact URL in Google Cloud Console:** APIs & Services → Credentials → edit your OAuth 2.0 Client ID → **Authorized redirect URIs** → add that callback URL → Save. If your ngrok URL changes, add the new callback and update `APP_URL`; you can leave `GOOGLE_REDIRECT_URI` unset.

---

## 3. Fix “ERR_BLOCKED_BY_CLIENT” (blank / blocked scripts)

This usually means something in the browser is blocking the script requests (often an ad blocker or privacy extension).

- **On your phone:** Disable ad blocker / “block trackers” for the ngrok site, or open the ngrok URL in a **private/incognito** window (with extensions disabled).
- **On desktop:** Same: disable extensions for the ngrok URL or use an incognito window.

Then reload the ngrok URL.

---

## Quick checklist

1. Run `npm run build`.
2. Set `APP_URL` in `.env` to your ngrok HTTPS URL and restart the PHP server.
3. Start ngrok: `ngrok http 8000` (or your port).
4. On the phone, open the ngrok URL; if it’s still blank, disable ad blocker or use incognito and reload.

After this, the app should load on your phone over ngrok.
