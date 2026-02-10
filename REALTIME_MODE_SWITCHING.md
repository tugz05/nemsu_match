# Real-Time Mode Switching

## Overview

The application now supports **real-time automatic navigation** to maintenance and pre-registration pages when superadmins toggle these modes. Users are immediately redirected without needing to refresh or navigate manually.

## How It Works

### 1. Superadmin Toggles a Mode

When a superadmin toggles Maintenance Mode or Pre-Registration Mode in `/superadmin/settings`:

```
Superadmin toggles switch
         ↓
Controller updates database
         ↓
Event broadcast to all users
         ↓
Users automatically navigate to mode page
```

### 2. Broadcasting System

**Events Created:**
- `MaintenanceModeChanged` - Broadcast when maintenance mode changes
- `PreRegistrationModeChanged` - Broadcast when pre-registration mode changes

**Channel:**
- `app-status` - Public channel accessible to all users (authenticated and guests)

**Broadcast Location:**
- `app/Events/MaintenanceModeChanged.php`
- `app/Events/PreRegistrationModeChanged.php`

### 3. Frontend Listener

**Location:** `resources/js/app.ts`

**Functionality:**
- Subscribes to `app-status` channel on app initialization
- Listens for mode change events
- Checks user privileges (admin/superadmin can bypass)
- Automatically reloads page to trigger middleware
- Middleware redirects to appropriate page

## User Experience Flow

### Scenario 1: Maintenance Mode Enabled

**Regular User (on browse page):**
```
1. User is browsing profiles
2. Superadmin enables maintenance mode
3. Broadcast event sent
4. User's page automatically reloads
5. Middleware detects maintenance mode
6. User sees maintenance page
7. Message: "Under Maintenance - We'll be back soon!"
```

**Admin User (on browse page):**
```
1. Admin is using the app
2. Superadmin enables maintenance mode
3. Broadcast event received
4. Admin is detected as admin user
5. No action taken (admin can bypass)
6. Admin continues using app normally
```

### Scenario 2: Maintenance Mode Disabled

**User (on maintenance page):**
```
1. User is viewing maintenance page
2. Superadmin disables maintenance mode
3. Broadcast event sent
4. Page automatically reloads
5. Middleware allows normal access
6. User sees normal app (last visited page or home)
```

### Scenario 3: Pre-Registration Mode Enabled

**Guest User (viewing homepage):**
```
1. Guest is viewing the site
2. Superadmin enables pre-registration mode
3. Broadcast event sent
4. Page automatically reloads
5. Middleware detects guest user + pre-reg mode
6. Guest sees pre-registration page
7. Form to register interest shown
```

**Authenticated User:**
```
1. Logged-in user is browsing
2. Superadmin enables pre-registration mode
3. Broadcast event received
4. User is detected as authenticated
5. No action taken (authenticated users bypass)
6. User continues using app normally
```

## Technical Implementation

### Backend

**1. Events (`app/Events/`):**
```php
class MaintenanceModeChanged implements ShouldBroadcast
{
    public bool $enabled;

    public function broadcastOn(): Channel
    {
        return new Channel('app-status');
    }

    public function broadcastAs(): string
    {
        return 'MaintenanceModeChanged';
    }

    public function broadcastWith(): array
    {
        return ['maintenance_mode' => $this->enabled];
    }
}
```

**2. Controller (`SettingsController.php`):**
```php
// After updating setting
if ($appSetting->key === 'maintenance_mode') {
    broadcast(new MaintenanceModeChanged($value === 'true'));
}

if ($appSetting->key === 'pre_registration_mode') {
    broadcast(new PreRegistrationModeChanged($value === 'true'));
}
```

**3. Channel (`routes/channels.php`):**
```php
// Public channel - accessible to everyone
Broadcast::channel('app-status', function (): bool {
    return true;
});
```

### Frontend

**Global Listener (`app.ts`):**
```typescript
// Subscribe to app-status channel
const statusChannel = window.Echo.channel('app-status');

// Listen for events
statusChannel.listen('.MaintenanceModeChanged', (e) => {
    if (!isAdminUser) {
        router.reload(); // Triggers middleware redirect
    }
});

statusChannel.listen('.PreRegistrationModeChanged', (e) => {
    if (!isAuthenticatedUser) {
        router.reload(); // Triggers middleware redirect
    }
});
```

## Access Control Logic

### Maintenance Mode

| User Type | Event Received | Action |
|-----------|----------------|--------|
| Guest | Yes | ✅ Reload → Maintenance Page |
| Regular User | Yes | ✅ Reload → Maintenance Page |
| Admin | Yes | ❌ No action (bypass) |
| Superadmin | Yes | ❌ No action (bypass) |

### Pre-Registration Mode

| User Type | Event Received | Action |
|-----------|----------------|--------|
| Guest | Yes | ✅ Reload → Pre-Reg Page |
| Authenticated User | Yes | ❌ No action (bypass) |
| Admin | Yes | ❌ No action (bypass) |
| Superadmin | Yes | ❌ No action (bypass) |

## Advantages

### ✅ Instant Response
- No manual refresh needed
- Users automatically redirected
- Real-time mode enforcement

### ✅ Seamless UX
- Smooth transition
- Clear messaging
- No confusion

### ✅ Admin Control
- Immediate effect across all users
- Can enable/disable instantly
- Perfect for emergencies

### ✅ Secure
- Admins always bypass
- Proper role checking
- No unauthorized access

## Testing the Real-Time Feature

### Test 1: Enable Maintenance Mode (Real-Time)

**Setup:**
1. Open two browser windows:
   - Window 1: Login as regular user, browse the app
   - Window 2: Login as superadmin, go to `/superadmin/settings`

**Test:**
1. In Window 1: User is on `/browse` page
2. In Window 2: Toggle "Maintenance Mode" ON
3. **Expected Result**: Window 1 automatically reloads and shows maintenance page
4. In Window 2: Toggle "Maintenance Mode" OFF
5. **Expected Result**: Window 1 automatically reloads and shows browse page

### Test 2: Enable Pre-Registration Mode (Real-Time)

**Setup:**
1. Open two browser windows:
   - Window 1: Guest (not logged in), viewing homepage
   - Window 2: Login as superadmin, go to `/superadmin/settings`

**Test:**
1. In Window 1: Guest is on `/` page
2. In Window 2: Toggle "Pre-Registration Mode" ON
3. **Expected Result**: Window 1 automatically reloads and shows pre-registration page
4. In Window 2: Toggle "Pre-Registration Mode" OFF
5. **Expected Result**: Window 1 automatically reloads and shows normal homepage

### Test 3: Admin Bypass

**Setup:**
1. Login as admin
2. Browse the app normally

**Test:**
1. Have another superadmin enable maintenance mode
2. **Expected Result**: Admin continues browsing normally (no redirect)
3. Console shows: "User is admin, bypassing maintenance mode"

### Test 4: Console Logging

Open browser console and watch for:
```
✓ Subscribed to app-status channel
Maintenance mode changed: true
User is admin, bypassing maintenance mode
```

or

```
Maintenance mode changed: true
(page reloads automatically)
```

## Browser Console Messages

When modes change, you'll see console logs:

**Maintenance Mode Enabled:**
```
Maintenance mode changed: true
Reloading page...
```

**Maintenance Mode Disabled:**
```
Maintenance mode changed: false
Maintenance mode disabled, app restored
(page reloads)
```

**Admin Bypass:**
```
Maintenance mode changed: true
User is admin, bypassing maintenance mode
(no reload)
```

**Pre-Registration Mode Enabled:**
```
Pre-registration mode changed: true
Reloading page...
```

**User Authenticated Bypass:**
```
Pre-registration mode changed: true
User is authenticated, bypassing pre-registration mode
(no reload)
```

## Middleware Priority

The middleware runs in this order:
```
1. CheckMaintenanceMode (highest priority)
   - If enabled, show maintenance page
   - Admins bypass

2. CheckPreRegistrationMode
   - If enabled and user is guest, show pre-reg page
   - Authenticated users bypass
```

This ensures maintenance mode takes priority over pre-registration mode.

## Files Created/Modified

```
✓ app/Events/MaintenanceModeChanged.php (created)
✓ app/Events/PreRegistrationModeChanged.php (created)
✓ app/Http/Controllers/Superadmin/SettingsController.php (updated)
✓ routes/channels.php (updated)
✓ resources/js/app.ts (updated)
✓ resources/js/composables/useAppStatus.ts (created)
✓ resources/js/components/AppStatusListener.vue (created)
✓ REALTIME_MODE_SWITCHING.md (this file)
```

## Broadcasting Configuration

### Requirements

Make sure your `.env` has broadcasting configured:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=your-cluster
```

### Verify Echo is Working

Check `resources/js/echo.js` or `resources/js/echo.ts` is properly configured.

## Troubleshooting

### Users Not Redirected in Real-Time

**Problem:** Toggling mode in settings doesn't redirect users

**Check:**
1. Is broadcasting configured?
   ```bash
   php artisan config:cache
   ```
2. Check browser console for Echo errors
3. Verify Pusher credentials in `.env`
4. Check if Echo is connected:
   ```javascript
   console.log(window.Echo);
   ```

### "Echo is not defined" Error

**Problem:** `window.Echo` is undefined

**Solution:**
1. Check `resources/js/echo.ts` exists
2. Verify it's imported in `app.ts`
3. Install Laravel Echo:
   ```bash
   npm install --save laravel-echo pusher-js
   ```

### Event Not Broadcasting

**Problem:** Event is created but not broadcast

**Check:**
1. Queue worker running:
   ```bash
   php artisan queue:work
   ```
2. Check broadcasting driver:
   ```bash
   php artisan config:show broadcasting.default
   ```

### Page Reloads But Shows Wrong Page

**Problem:** Page reloads but doesn't show maintenance/pre-reg page

**Check:**
1. Verify middleware is registered in `bootstrap/app.php`
2. Clear config cache:
   ```bash
   php artisan config:clear
   ```
3. Check database for setting value:
   ```sql
   SELECT * FROM app_settings WHERE key IN ('maintenance_mode', 'pre_registration_mode');
   ```

## Advanced Features

### Custom Reload Behavior

If you want custom behavior instead of reload, modify `app.ts`:

```typescript
statusChannel.listen('.MaintenanceModeChanged', (e) => {
    if (e.maintenance_mode) {
        // Custom navigation instead of reload
        router.visit('/maintenance', {
            replace: true,
        });
    }
});
```

### Show Notification Before Redirect

```typescript
statusChannel.listen('.MaintenanceModeChanged', (e) => {
    if (e.maintenance_mode) {
        alert('System is entering maintenance mode. You will be redirected.');
        setTimeout(() => {
            router.reload();
        }, 2000);
    }
});
```

### Countdown Timer

```typescript
let countdown = 10;
const countdownInterval = setInterval(() => {
    console.log(`Redirecting in ${countdown}s...`);
    countdown--;
    if (countdown === 0) {
        clearInterval(countdownInterval);
        router.reload();
    }
}, 1000);
```

## Summary

✅ **Real-time broadcasting** when modes change
✅ **Automatic page reload** for affected users
✅ **Admin bypass** properly implemented
✅ **Console logging** for debugging
✅ **Global listener** in app.ts
✅ **Immediate effect** across all connected users
✅ **Proper middleware** handling
✅ **Role-based filtering** (admins exempt)

---

**When a superadmin toggles maintenance or pre-registration mode, ALL connected users are automatically navigated to the appropriate page in real-time!** 🚀

## Testing

**Quick Test:**
1. Open two browsers
2. Browser 1: Regular user browsing app
3. Browser 2: Superadmin in settings
4. Toggle maintenance mode in Browser 2
5. Watch Browser 1 automatically redirect to maintenance page!
