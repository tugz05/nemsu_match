# Google OAuth Setup for NEMSU Match

Complete guide to setting up Google Sign-In with NEMSU Workspace domain restriction.

## 📋 Prerequisites

- NEMSU uses Google Workspace for student emails (@nemsu.edu.ph)
- You have admin access to Google Cloud Console
- Laravel application is already set up

## 🚀 Step-by-Step Setup

### Step 1: Install Laravel Socialite

```bash
composer require laravel/socialite socialiteproviders/google
```

### Step 2: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **Select a Project** → **New Project**
3. Enter project details:
   - **Project Name**: NEMSU Match
   - **Organization**: (Your organization)
4. Click **Create**

### Step 3: Enable Google+ API

1. In your project, go to **APIs & Services** → **Library**
2. Search for "Google+ API"
3. Click **Enable**

### Step 4: Configure OAuth Consent Screen

1. Go to **APIs & Services** → **OAuth consent screen**
2. Select **External** (or Internal if NEMSU has Google Workspace)
3. Fill in the application details:

```
App name: NEMSU Match
User support email: your-email@nemsu.edu.ph
App logo: (Optional - upload your logo)
Application home page: https://your-domain.com
Application privacy policy: https://your-domain.com/privacy
Application terms of service: https://your-domain.com/terms
Authorized domains: nemsu.edu.ph, your-domain.com
Developer contact email: your-email@nemsu.edu.ph
```

4. Click **Save and Continue**

5. **Scopes**: Add the following scopes:
   - `userinfo.email`
   - `userinfo.profile`
   - `openid`

6. Click **Save and Continue**

7. **Test users** (for development):
   - Add test NEMSU email addresses
   - Example: `test.student@nemsu.edu.ph`

8. Click **Save and Continue**

### Step 5: Create OAuth 2.0 Credentials

1. Go to **APIs & Services** → **Credentials**
2. Click **Create Credentials** → **OAuth client ID**
3. Select **Application type**: Web application
4. Enter details:

```
Name: NEMSU Match Web Client
```

5. **Authorized JavaScript origins**:
```
http://localhost:8000 (for development)
https://your-domain.com (for production)
```

6. **Authorized redirect URIs**:
```
http://localhost:8000/oauth/nemsu/callback (for development)
https://your-domain.com/oauth/nemsu/callback (for production)
```

7. Click **Create**

8. **Copy your credentials**:
   - Client ID: `xxxxxx.apps.googleusercontent.com`
   - Client Secret: `GOCSPX-xxxxxxxxxxxxxxxxxxxxx`

### Step 6: Configure Environment Variables

Update your `.env` file:

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/oauth/nemsu/callback

NEMSU_DOMAIN=nemsu.edu.ph
```

**Production `.env`:**
```env
GOOGLE_CLIENT_ID=your-production-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your-production-secret
GOOGLE_REDIRECT_URI=https://your-domain.com/oauth/nemsu/callback

NEMSU_DOMAIN=nemsu.edu.ph
```

### Step 7: Update OAuth Controller

Uncomment the production code in `NEMSUOAuthController.php`:

```php
// In redirect() method:
return Socialite::driver('google')
    ->with([
        'hd' => 'nemsu.edu.ph', // Restrict to NEMSU domain only
        'prompt' => 'select_account', // Force account selection
    ])
    ->scopes(['email', 'profile'])
    ->redirect();

// In callback() method:
$googleUser = Socialite::driver('google')->user();

// Validate NEMSU email domain
if (!Str::endsWith($googleUser->getEmail(), '@nemsu.edu.ph')) {
    return redirect()->route('nemsu.login')->withErrors([
        'email' => 'Only NEMSU email addresses (@nemsu.edu.ph) are allowed.',
    ]);
}

// Find or create user
$user = User::firstOrCreate(
    ['email' => $googleUser->getEmail()],
    [
        'name' => $googleUser->getName(),
        'nemsu_id' => $googleUser->getId(),
        'email_verified_at' => now(),
        'password' => Hash::make(Str::random(32)),
    ]
);
```

### Step 8: Test the Integration

1. Start your Laravel server:
```bash
php artisan serve
```

2. Visit: `http://localhost:8000/nemsu/login`

3. Click **Continue with Google**

4. Select or enter NEMSU account:
   - Must end with `@nemsu.edu.ph`
   - Non-NEMSU emails will be rejected

5. Grant permissions

6. Should redirect to profile setup (first time) or dashboard

## 🔒 Security Features

### Domain Restriction (`hd` parameter)

The `hd` (hosted domain) parameter restricts sign-in to NEMSU workspace accounts:

```php
->with(['hd' => 'nemsu.edu.ph'])
```

**What it does:**
- Only shows accounts from `@nemsu.edu.ph` domain
- Users can't sign in with personal Gmail accounts
- Enforces NEMSU workspace authentication

### Additional Validation

Server-side email validation ensures security:

```php
if (!Str::endsWith($googleUser->getEmail(), '@nemsu.edu.ph')) {
    return redirect()->route('nemsu.login')->withErrors([
        'email' => 'Only NEMSU email addresses are allowed.',
    ]);
}
```

### Account Selection

Force users to select account every time:

```php
->with(['prompt' => 'select_account'])
```

Benefits:
- Prevents accidental wrong account usage
- Better for shared devices
- Clearer user experience

## 🧪 Testing

### Test Users (Development)

Add these to your OAuth consent screen test users:
```
student1@nemsu.edu.ph
student2@nemsu.edu.ph
faculty@nemsu.edu.ph
```

### Test Scenarios

1. **Valid NEMSU Email**:
   - Email: `test@nemsu.edu.ph`
   - Expected: ✅ Successful login

2. **Personal Gmail**:
   - Email: `personal@gmail.com`
   - Expected: ❌ Error message

3. **Other Domain**:
   - Email: `user@otherschool.edu.ph`
   - Expected: ❌ Error message

4. **First-time User**:
   - Expected: ✅ Create account → Redirect to profile setup

5. **Returning User (Complete Profile)**:
   - Expected: ✅ Login → Redirect to dashboard

6. **Returning User (Incomplete Profile)**:
   - Expected: ✅ Login → Redirect to profile setup

## 🚨 Troubleshooting

### Error: "redirect_uri_mismatch"

**Cause**: Redirect URI doesn't match Google Console configuration

**Fix**:
1. Check exact URI in Google Console
2. Ensure protocol matches (http vs https)
3. No trailing slash
4. Port number matches (if using development)

Correct format:
```
http://localhost:8000/oauth/nemsu/callback
```

### Error: "Access blocked: This app's request is invalid"

**Cause**: OAuth consent screen not properly configured

**Fix**:
1. Complete OAuth consent screen setup
2. Add all required scopes
3. Add test users (development mode)
4. Verify authorized domains

### Error: "Only NEMSU email addresses are allowed"

**Cause**: User tried to sign in with non-NEMSU email

**Expected behavior**: This is working correctly!

**User action**: Use NEMSU Google Workspace account

### Users Can't See NEMSU Accounts

**Cause**: `hd` parameter not working as expected

**Fix**: Users should:
1. Add NEMSU account to their browser
2. Sign in to NEMSU Google Workspace first
3. Then use NEMSU Match

**Alternative**: Remove `hd` parameter and rely on server-side validation

## 📊 Monitoring & Analytics

### Log Important Events

Add logging to track OAuth flow:

```php
// Log successful authentications
\Log::info('User authenticated via Google', [
    'email' => $user->email,
    'name' => $user->name,
]);

// Log failed attempts
\Log::warning('OAuth authentication failed', [
    'reason' => 'Invalid domain',
    'email' => $email,
]);
```

### Track Metrics

Monitor these metrics:
- Total OAuth attempts
- Successful logins
- Failed logins (by reason)
- New user registrations
- Returning user logins

## 🔐 Production Checklist

Before deploying to production:

- [ ] Google Cloud project created
- [ ] OAuth consent screen configured
- [ ] Production credentials obtained
- [ ] Production redirect URI added
- [ ] `.env` updated with production credentials
- [ ] Laravel Socialite installed
- [ ] Production code uncommented
- [ ] Development mock code removed
- [ ] HTTPS enabled on production domain
- [ ] Domain ownership verified in Google Console
- [ ] Test with multiple NEMSU accounts
- [ ] Error handling tested
- [ ] Logging configured
- [ ] Privacy policy published
- [ ] Terms of service published

## 📚 Resources

### Documentation
- [Laravel Socialite](https://laravel.com/docs/socialite)
- [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
- [Google Workspace Admin](https://admin.google.com/)

### Google Cloud Console
- [API Console](https://console.cloud.google.com/apis/)
- [OAuth Consent](https://console.cloud.google.com/apis/credentials/consent)
- [Credentials](https://console.cloud.google.com/apis/credentials)

### Support
- [Google OAuth Support](https://support.google.com/cloud/)
- [Laravel Socialite GitHub](https://github.com/laravel/socialite)

## 🎯 Best Practices

### Security
1. **Never commit credentials** to version control
2. **Use environment variables** for all sensitive data
3. **Validate email domain** on both client and server
4. **Log authentication events** for security monitoring
5. **Use HTTPS** in production
6. **Rotate secrets** periodically

### User Experience
1. **Clear error messages** for authentication failures
2. **Show NEMSU branding** to build trust
3. **Fast redirect** after successful auth
4. **Remember user preference** when appropriate
5. **Provide help links** for common issues

### Performance
1. **Cache OAuth tokens** appropriately
2. **Minimize API calls** to Google
3. **Implement rate limiting** to prevent abuse
4. **Monitor response times**

## 💡 Advanced Configuration

### Restrict to Specific NEMSU Groups

If you want to restrict to specific user groups:

```php
// Get user's domain and groups from Google
$googleUser = Socialite::driver('google')->stateless()->user();

// Check if user is in allowed group
$allowedGroups = ['students@nemsu.edu.ph', 'faculty@nemsu.edu.ph'];
// Implement group checking logic
```

### Custom Scopes

Request additional information:

```php
->scopes(['email', 'profile', 'openid'])
```

### Offline Access

For background operations:

```php
->with(['access_type' => 'offline'])
```

---

## ✅ Quick Verification

After setup, verify everything works:

```bash
# 1. Check environment variables
php artisan tinker
>>> config('services.google.client_id')
>>> config('services.nemsu.domain')

# 2. Test routes
php artisan route:list | grep oauth

# 3. Check Socialite installation
composer show laravel/socialite

# 4. Test authentication flow
Visit: http://localhost:8000/nemsu/login
```

---

**Setup Complete! 🎉**

Your NEMSU Match app now has secure Google Workspace authentication with domain restriction.

**Questions?** Check the troubleshooting section or contact your technical team.

---

**Document Version**: 1.0  
**Last Updated**: February 3, 2026  
**Author**: NEMSU Match Development Team
