# Maintenance Mode & Pre-Registration Mode

## Overview

The NEMSU Match application includes two special modes that can be toggled by superadmins to control user access and prepare for launch:

1. **Maintenance Mode** - Temporarily blocks all users while system maintenance is performed
2. **Pre-Registration Mode** - Shows a coming soon page for guests before official launch

## Features

### 🔧 Maintenance Mode

When enabled, all regular users will see a maintenance page while admins can still access the system.

**Use Cases:**
- System updates and upgrades
- Database maintenance
- Server infrastructure changes
- Emergency fixes
- Performance optimization

**Access Control:**
- ✅ Superadmins: Full access (bypass maintenance)
- ✅ Admins/Editors: Full access (bypass maintenance)
- ❌ Regular Users: See maintenance page
- ❌ Guests: See maintenance page

### 📝 Pre-Registration Mode

When enabled, guests will see a pre-registration page where they can sign up for early access notifications.

**Use Cases:**
- Before official launch
- Building user interest
- Collecting early adopter emails
- Platform testing phase
- Gradual rollout

**Access Control:**
- ✅ Authenticated Users: Full access (bypass pre-reg)
- ✅ Admins: Full access
- ❌ Guests: See pre-registration page

## How to Enable/Disable

### Via Superadmin Portal

1. **Login as Superadmin**
   ```
   http://your-domain.com/admin/login
   Email: superadmin@nemsu.edu.ph
   Password: Your-Password
   ```

2. **Navigate to Settings**
   ```
   /superadmin/settings
   ```

3. **Toggle the Modes**
   - **Maintenance Mode**: Find under "General Settings" → Toggle switch
   - **Pre-Registration Mode**: Find under "Users Settings" → Toggle switch
   - **Allow Registration**: Find under "Users Settings" → Toggle switch

4. **Changes Apply Immediately**
   - No need to restart the server
   - Users will see the new state on their next request

### Via Tinker (Manual)

```bash
php artisan tinker
```

```php
// Enable Maintenance Mode
AppSetting::set('maintenance_mode', true);

// Disable Maintenance Mode
AppSetting::set('maintenance_mode', false);

// Enable Pre-Registration Mode
AppSetting::set('pre_registration_mode', true);

// Disable Pre-Registration Mode
AppSetting::set('pre_registration_mode', false);

// Disable Registration
AppSetting::set('allow_registration', false);
```

## Pages Created

### 1. Maintenance Page (`MaintenancePage.vue`)

**Location:** `resources/js/pages/MaintenancePage.vue`

**Features:**
- Professional maintenance message
- Animated wrench icon
- Expected duration information
- List of what's being updated
- Refresh button to check status
- Contact information for urgent matters
- Fully responsive design

**Design:**
- Blue and cyan gradient theme
- Animated background elements
- Modern card design
- Clear, friendly messaging

### 2. Pre-Registration Page (`PreRegistration.vue`)

**Location:** `resources/js/pages/PreRegistration.vue`

**Features:**
- Two-column layout (info + form)
- Pre-registration form (name, email, NEMSU ID)
- Feature highlights
- **Live counter showing number of pre-registered users** ✨
- Success confirmation
- Email notification promise
- Social proof badges
- Responsive design

**Design:**
- Split screen layout
- Left: Blue gradient with features
  - Counter card showing registered users
  - TrendingUp indicator
- Right: White form card
  - Counter badge near form header
  - Pulsing green dot indicator
- Success state with animation

**Counter Features:**
- Shows only regular users (excludes admins/superadmins)
- Formatted with thousand separators
- Displays in two locations for emphasis
- Updates automatically on page load

## Middleware

### 1. CheckMaintenanceMode

**Location:** `app/Http/Middleware/CheckMaintenanceMode.php`

**Logic:**
```
1. Skip admin routes (/admin/*, /superadmin/*)
2. Check if maintenance_mode = true
3. If enabled:
   - Allow superadmins and admins to bypass
   - Show MaintenancePage to everyone else
4. If disabled: Continue normally
```

**Priority:** Runs early in middleware stack

### 2. CheckPreRegistrationMode

**Location:** `app/Http/Middleware/CheckPreRegistrationMode.php`

**Logic:**
```
1. Skip admin routes and authenticated users
2. Check if pre_registration_mode = true OR allow_registration = false
3. If enabled:
   - Show PreRegistration page to guests
   - Allow authenticated users to continue
4. If disabled: Continue normally
```

**Priority:** Runs after maintenance check

## Middleware Registration

**File:** `bootstrap/app.php`

```php
$middleware->web(append: [
    HandleAppearance::class,
    HandleInertiaRequests::class,
    AddLinkHeadersForPreloadedAssets::class,
    UpdateLastSeen::class,
    CheckMaintenanceMode::class,        // ← Added
    CheckPreRegistrationMode::class,    // ← Added
]);
```

## Testing

### Test Maintenance Mode

**Step 1: Enable Maintenance Mode**
```bash
php artisan tinker
AppSetting::set('maintenance_mode', true);
```

**Step 2: Test Access**
- Visit homepage as guest → Should see maintenance page
- Login as regular user → Should see maintenance page
- Login as admin → Should bypass and access app
- Login as superadmin → Should bypass and access app

**Step 3: Test Refresh Button**
- Click "Check Status" button
- Page should reload and check mode again

**Step 4: Disable Maintenance Mode**
```bash
php artisan tinker
AppSetting::set('maintenance_mode', false);
```

**Step 5: Verify Normal Access**
- All users should now access the app normally

### Test Pre-Registration Mode

**Step 1: Enable Pre-Registration Mode**
```bash
php artisan tinker
AppSetting::set('pre_registration_mode', true);
```

**Step 2: Test as Guest**
- Visit homepage → Should see pre-registration page
- Fill out form
- Submit → Should see success message

**Step 3: Test as Authenticated User**
- Login with any user account
- Should bypass pre-registration and access app normally

**Step 4: Disable Pre-Registration Mode**
```bash
php artisan tinker
AppSetting::set('pre_registration_mode', false);
```

## Pre-Registered Users Counter

### How It Works

The Pre-Registration page displays a live counter showing how many regular users have already registered on the platform. This creates **social proof** and encourages more people to sign up.

**Counter Features:**
- **Only counts regular users** (excludes admins and superadmins)
- **Two display locations:**
  1. Large counter card on the left side (blue gradient section)
  2. Small badge above the form (right side)
- **Formatted numbers** with thousand separators (e.g., 1,234)
- **Real-time** - updates on every page load
- **Pulsing indicator** - green dot shows active registrations

**Technical Details:**
```php
// In CheckPreRegistrationMode middleware
$preRegisteredCount = \App\Models\User::where('is_admin', false)
    ->where('is_superadmin', false)
    ->count();
```

**Visual Elements:**
- **Left Side Counter:**
  - Large number display (2xl font)
  - "Students Pre-Registered" label
  - TrendingUp icon with "Join them!" call-to-action
  - Glassmorphism background (white/10 backdrop-blur)

- **Form Badge:**
  - Compact counter badge
  - "X students already registered" text
  - Animated green pulse dot
  - Blue gradient background

**Why It's Effective:**
✅ Creates urgency and FOMO (Fear of Missing Out)
✅ Shows platform popularity
✅ Builds trust through social proof
✅ Encourages sign-ups
✅ Demonstrates active community

## Configuration

### Database Settings

The modes are stored in the `app_settings` table:

```sql
SELECT * FROM app_settings 
WHERE key IN ('maintenance_mode', 'pre_registration_mode', 'allow_registration');
```

### Default Values

| Setting | Default | Type | Group |
|---------|---------|------|-------|
| maintenance_mode | false | boolean | general |
| pre_registration_mode | false | boolean | users |
| allow_registration | true | boolean | users |

### Cache

Settings are cached for 1 hour:
- Cache key: `app_setting_{key}`
- Clear cache: `AppSetting::clearCache()`
- Auto-cleared when settings are updated

## Routes Excluded from Checks

The following routes bypass both maintenance and pre-registration checks:

**Admin Routes:**
- `/admin/*` - Admin login and dashboard
- `/superadmin/*` - Superadmin portal

**Why?** Admins need access to disable modes and manage the system.

## Use Case Scenarios

### Scenario 1: System Maintenance

```php
// Before maintenance
AppSetting::set('maintenance_mode', true);

// Perform maintenance tasks
php artisan migrate
php artisan optimize
// ... other maintenance

// After maintenance
AppSetting::set('maintenance_mode', false);
```

### Scenario 2: Pre-Launch

```php
// Enable pre-registration
AppSetting::set('pre_registration_mode', true);
AppSetting::set('allow_registration', false);

// Collect early adopters...
// Build interest...

// Launch day
AppSetting::set('pre_registration_mode', false);
AppSetting::set('allow_registration', true);
```

### Scenario 3: Gradual Rollout

```php
// Phase 1: Pre-registration
AppSetting::set('pre_registration_mode', true);

// Phase 2: Closed beta (manually approve users)
AppSetting::set('pre_registration_mode', false);
AppSetting::set('allow_registration', false);

// Phase 3: Open to all
AppSetting::set('allow_registration', true);
```

### Scenario 4: Emergency Shutdown

```php
// Immediate shutdown for critical issues
AppSetting::set('maintenance_mode', true);

// Fix the issue...

// Bring back online
AppSetting::set('maintenance_mode', false);
```

## Customization

### Modify Maintenance Page

Edit `resources/js/pages/MaintenancePage.vue`:

```vue
<!-- Change message -->
<p class="text-lg md:text-xl text-gray-600 mb-2">
    Your custom message here
</p>

<!-- Add custom features list -->
<ul class="space-y-3">
    <li>Your feature 1</li>
    <li>Your feature 2</li>
</ul>
```

### Modify Pre-Registration Page

Edit `resources/js/pages/PreRegistration.vue`:

```vue
<!-- Change header -->
<h1 class="text-4xl md:text-5xl font-bold mb-4">
    Your Custom Title
</h1>

<!-- Add custom features -->
<div class="flex items-start gap-3">
    <CheckCircle class="w-5 h-5 text-cyan-200" />
    <p>Your custom feature</p>
</div>
```

### Add Pre-Registration Backend

Create endpoint to save pre-registrations:

```php
// routes/web.php
Route::post('/api/pre-register', [PreRegistrationController::class, 'store']);

// PreRegistrationController.php
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:pre_registrations,email',
        'nemsu_id' => 'nullable|string',
    ]);

    PreRegistration::create($request->all());

    // Send notification email...

    return back()->with('success', 'Registered successfully!');
}
```

## Troubleshooting

### Can't Disable Maintenance Mode

**Problem:** Stuck in maintenance mode, can't access admin panel

**Solution:**
```bash
# Via tinker
php artisan tinker
AppSetting::set('maintenance_mode', false);

# Or via database
mysql> UPDATE app_settings SET value='false' WHERE key='maintenance_mode';
```

### Pre-Registration Not Showing

**Problem:** Users not seeing pre-registration page

**Check:**
1. Is the setting enabled?
   ```php
   AppSetting::get('pre_registration_mode')
   ```
2. Clear cache:
   ```bash
   php artisan cache:clear
   ```
3. Check middleware is registered in `bootstrap/app.php`

### Admin Seeing Maintenance Page

**Problem:** Admin users seeing maintenance page

**Check:**
1. Verify `is_admin` or `is_superadmin` flag:
   ```sql
   SELECT email, is_admin, is_superadmin FROM users WHERE id = X;
   ```
2. Make sure admin routes are excluded in middleware

## Security Notes

1. **Admin Access:** Admins can always bypass both modes to manage the system
2. **Setting Protection:** Only superadmins can change these settings
3. **Cache:** Settings are cached but cleared on update
4. **No Bypass:** Regular users cannot bypass these restrictions

## Best Practices

1. **Notify Users:** Announce maintenance windows in advance
2. **Schedule Wisely:** Perform maintenance during low-traffic hours
3. **Test First:** Test on staging before enabling in production
4. **Monitor:** Keep an eye on the system during maintenance
5. **Communication:** Provide alternative contact methods
6. **Quick Access:** Bookmark admin login for quick mode changes

## Files Modified

```
✓ app/Http/Middleware/CheckMaintenanceMode.php (created)
✓ app/Http/Middleware/CheckPreRegistrationMode.php (created)
✓ resources/js/pages/MaintenancePage.vue (created)
✓ resources/js/pages/PreRegistration.vue (created)
✓ bootstrap/app.php (updated)
✓ MAINTENANCE_PREREGISTRATION_MODES.md (this file)
```

## Summary

✅ **Maintenance Mode** - Blocks all users except admins
✅ **Pre-Registration Mode** - Shows coming soon page to guests
✅ **Toggle via Superadmin Settings**
✅ **Beautiful, responsive pages**
✅ **Admin bypass enabled**
✅ **Immediate effect (no restart needed)**
✅ **Cached for performance**
✅ **Fully customizable**

---

**Quick Toggle Commands:**

```bash
# Enable Maintenance Mode
php artisan tinker
AppSetting::set('maintenance_mode', true);

# Disable Maintenance Mode
AppSetting::set('maintenance_mode', false);

# Enable Pre-Registration
AppSetting::set('pre_registration_mode', true);

# Disable Pre-Registration
AppSetting::set('pre_registration_mode', false);
```

Or use the Superadmin Portal at `/superadmin/settings`
