# Fix Google OAuth - Quick Guide

## ⚠️ IMPORTANT: Update Google Cloud Console

Your Google OAuth redirect URI is currently set incorrectly. You need to update it in Google Cloud Console.

## 🔧 Steps to Fix

### Step 1: Go to Google Cloud Console

1. Visit: https://console.cloud.google.com/apis/credentials
2. Sign in with your Google account
3. Select your project (or the project where you created the OAuth client)

### Step 2: Find Your OAuth Client

1. Look for **OAuth 2.0 Client IDs** section
2. Click on your client (the one with your Client ID)
3. Or click **Edit** (pencil icon)

### Step 3: Update Authorized Redirect URIs

**Current (Wrong):**
```
http://localhost:8000/auth/google/callback
```

**Change to (Correct):**
```
http://localhost:8000/oauth/nemsu/callback
```

**How to do it:**
1. Find "Authorized redirect URIs" section
2. **Remove** the old URI: `http://localhost:8000/auth/google/callback`
3. **Add** the new URI: `http://localhost:8000/oauth/nemsu/callback`
4. Click **SAVE** at the bottom

### Step 4: Verify Your .env File

Make sure your `.env` file has:

```env
GOOGLE_CLIENT_ID=820990150744-eaecf93nss2s40gnvc7hjubk5be0f1ho.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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
GOOGLE_REDIRECT_URI=http://localhost:8000/oauth/nemsu/callback
NEMSU_DOMAIN=nemsu.edu.ph
```

### Step 5: Clear Config Cache

In your terminal:
```bash
php artisan config:clear
```

### Step 6: Test Google Sign-In

1. Visit: http://localhost:8000
2. Click "Continue with Google"
3. Select your NEMSU account (@nemsu.edu.ph)
4. Authorize the app
5. Should redirect back and log you in!

## 📋 Quick Checklist

- [ ] Opened Google Cloud Console
- [ ] Found OAuth 2.0 Client
- [ ] Removed old redirect URI
- [ ] Added new redirect URI: `http://localhost:8000/oauth/nemsu/callback`
- [ ] Saved changes
- [ ] Verified .env file
- [ ] Cleared config cache
- [ ] Tested sign-in

## 🎯 What Each URI Does

**OLD (Wrong):**
- `http://localhost:8000/auth/google/callback`
- This doesn't exist in your app
- Will cause "redirect_uri_mismatch" error

**NEW (Correct):**
- `http://localhost:8000/oauth/nemsu/callback`
- This is the actual route in your app
- Matches your Laravel routes

## 🚨 Common Errors & Solutions

### Error: "redirect_uri_mismatch"

**Cause:** Google redirect URI doesn't match your app's URI

**Solution:**
1. Check Google Console has: `http://localhost:8000/oauth/nemsu/callback`
2. Check .env has: `GOOGLE_REDIRECT_URI=http://localhost:8000/oauth/nemsu/callback`
3. Run: `php artisan config:clear`

### Error: "invalid_client"

**Cause:** Wrong Client ID or Client Secret

**Solution:**
1. Copy credentials from Google Console again
2. Make sure no extra spaces in .env
3. No quotes around the values in .env
4. Run: `php artisan config:clear`

### Error: "Only NEMSU email addresses are allowed"

**Cause:** You're using a non-NEMSU email

**Solution:**
- Use an email that ends with @nemsu.edu.ph
- Example: student@nemsu.edu.ph, faculty@nemsu.edu.ph

## ✅ Success!

Once configured correctly, you should:
1. Click "Continue with Google"
2. See Google's account selection screen
3. Select your @nemsu.edu.ph account
4. Grant permissions
5. Redirect back to profile setup (first time) or dashboard

## 📱 For Production

When deploying to production, add another redirect URI:
```
https://your-domain.com/oauth/nemsu/callback
```

And update .env in production:
```env
GOOGLE_REDIRECT_URI=https://your-domain.com/oauth/nemsu/callback
```

---

**Need more help?** Check `GOOGLE_OAUTH_SETUP.md` for detailed setup guide.
