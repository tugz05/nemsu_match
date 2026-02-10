# Admin Login System Documentation

## Overview

A dedicated login system for administrative users (Superadmin, Admin, and Editor) has been implemented, separate from the regular user login flow.

## Features

### 🔐 Dedicated Admin Login Portal
- **URL**: `/admin/login`
- Professional, security-focused UI design
- Dark gradient background with animated elements
- Clear role distinction (Superadmin, Admin, Editor)
- Security notices and access level indicators

### 🎨 UI Design
- Dark gradient background (slate-900 to blue-900)
- Animated background elements
- Shield icon badge with glow effect
- Professional white card with backdrop blur
- Role type cards showing access levels
- Error and success message displays
- Mobile responsive

### 🔑 Authentication Flow
1. User visits `/admin/login`
2. Clicks "Sign in with NEMSU Google"
3. Redirects to Google OAuth with NEMSU domain restriction
4. Validates user has admin/superadmin privileges
5. Redirects to appropriate dashboard based on role

## Routes

### Public Routes (Guest Only)
```php
GET  /admin/login                    # Show admin login page
GET  /admin/oauth/redirect           # Redirect to Google OAuth
GET  /admin/oauth/callback           # Handle OAuth callback
```

### Protected Routes (Admin Only)
```php
GET  /admin/dashboard                # Admin dashboard
POST /admin/logout                   # Admin logout
```

### Superadmin Routes
```php
GET  /superadmin                     # Superadmin dashboard
GET  /superadmin/users               # User management
GET  /superadmin/admins              # Admin management
GET  /superadmin/settings            # App settings
```

## Components

### Backend

#### Controllers

**AdminAuthController** (`app/Http/Controllers/Auth/AdminAuthController.php`)
- `showLogin()` - Display admin login page
- `redirectToGoogle()` - Redirect to Google OAuth
- `handleGoogleCallback()` - Process OAuth callback
- `logout()` - Handle admin logout

**Admin\DashboardController** (`app/Http/Controllers/Admin/DashboardController.php`)
- `index()` - Display admin dashboard with statistics

### Frontend

**AdminLogin.vue** (`resources/js/pages/auth/AdminLogin.vue`)
- Dedicated admin login page
- Google OAuth integration
- Error/success message handling
- Role type indicators
- Security notices

**Admin\Dashboard.vue** (`resources/js/pages/Admin/Dashboard.vue`)
- Admin dashboard with statistics
- Quick action links
- Recent users list
- Navigation to main app

## Authentication Logic

### Login Flow

```php
1. User clicks "Sign in with NEMSU Google"
   ↓
2. Redirect to Google OAuth (/admin/oauth/redirect)
   ↓
3. User authenticates with Google
   ↓
4. Callback handler receives auth code (/admin/oauth/callback)
   ↓
5. Validate NEMSU email domain (@nemsu.edu.ph)
   ↓
6. Check if user exists in database
   ↓
7. Verify user has admin/superadmin privileges
   ↓
8. Log user in and redirect to appropriate dashboard
```

### Role-Based Redirection

After successful authentication:
- **Superadmin** → `/superadmin` (Superadmin Portal)
- **Admin** → `/admin/dashboard` (Admin Dashboard)
- **Editor** → `/admin/dashboard` (Admin Dashboard)
- **Regular User** → Error: "This account does not have administrative privileges"

### Error Handling

The system handles various error scenarios:

1. **Authorization Failed**
   - When OAuth code is not received
   - Message: "Authorization failed. Please try again."

2. **Non-NEMSU Email**
   - When user doesn't use @nemsu.edu.ph
   - Message: "Only NEMSU email addresses (@nemsu.edu.ph) are allowed."

3. **Account Not Found**
   - When email doesn't exist in database
   - Message: "No admin account found with this email address. Please contact the superadmin."

4. **Insufficient Privileges**
   - When user is not admin/superadmin
   - Message: "This account does not have administrative privileges."

5. **OAuth Failure**
   - When OAuth process fails
   - Message: "Authentication failed. Please try again."

## Access Control

### Middleware Protection

All admin routes are protected by:
```php
Route::middleware(['auth', 'verified', 'admin'])
```

All superadmin routes are protected by:
```php
Route::middleware(['auth', 'verified', 'superadmin'])
```

### User Flags

The system uses the following user model flags:
- `is_admin` (boolean) - General admin access
- `is_superadmin` (boolean) - Superadmin access

## Usage

### Accessing Admin Login

1. **Direct URL**: Navigate to `/admin/login`
2. **From Main Site**: Link provided in footer/header (if implemented)

### Creating Admin Users

Admin users must be created by a superadmin:

1. Login as superadmin
2. Navigate to `/superadmin/admins`
3. Click "Assign Role"
4. Search for user
5. Select role (Superadmin, Admin, or Editor)
6. Assign

Or use the artisan command:
```bash
php artisan make:superadmin email@nemsu.edu.ph
```

### First Time Setup

1. Create first superadmin using artisan command
2. Login via `/admin/login`
3. Access superadmin portal
4. Create additional admin/editor accounts

## Security Features

✅ **Domain Restriction**: Only @nemsu.edu.ph emails allowed
✅ **Role Verification**: Validates admin privileges before login
✅ **CSRF Protection**: All requests include CSRF tokens
✅ **Session Management**: Proper session invalidation on logout
✅ **Middleware Protection**: All admin routes protected
✅ **OAuth State**: CSRF token used as OAuth state parameter
✅ **Error Logging**: All auth failures logged for monitoring

## UI Features

### Admin Login Page
- **Shield Badge**: Animated glow effect
- **Background**: Dark gradient with floating orbs
- **Role Cards**: Visual representation of access levels
- **Security Notice**: Locked icon with access information
- **Google Button**: Prominent OAuth button
- **Back Link**: Return to main site
- **Footer**: Security notice about logging

### Admin Dashboard
- **Header**: Logo, title, navigation
- **Stats Cards**: User, matches, messages counts
- **Recent Users**: Latest 5 registered users
- **Quick Actions**: Links to browse, chat, feed
- **Info Notice**: Admin access level information

## Differences from Regular Login

| Feature | Regular Login | Admin Login |
|---------|--------------|-------------|
| URL | `/` or `/nemsu/login` | `/admin/login` |
| Redirect After Auth | `/browse` | `/superadmin` or `/admin/dashboard` |
| UI Theme | Light, friendly, hearts | Dark, professional, shield |
| Access Check | Email domain only | Email + Admin privileges |
| Error Handling | Basic | Detailed with specific messages |
| Target Audience | All NEMSU students | Admins only |

## Troubleshooting

### "No admin account found"
- **Cause**: User email not in database
- **Solution**: Superadmin must create the admin account first

### "This account does not have administrative privileges"
- **Cause**: User exists but lacks admin/superadmin flags
- **Solution**: Superadmin must assign admin role to user

### "Authorization failed"
- **Cause**: OAuth process interrupted
- **Solution**: Try logging in again

### Can't access /admin/login
- **Cause**: Route not registered or app not restarted
- **Solution**: Clear route cache: `php artisan route:clear`

## Testing

### Test Admin Login Flow
1. Visit `/admin/login`
2. Verify page loads with shield icon
3. Click "Sign in with NEMSU Google"
4. Complete Google OAuth
5. Verify redirect to appropriate dashboard
6. Check error messages with invalid accounts

### Test Role-Based Access
1. Login as superadmin → Should see `/superadmin`
2. Login as admin → Should see `/admin/dashboard`
3. Login as regular user → Should see error message
4. Login with non-NEMSU email → Should see domain error

## File Structure

```
dating-app/
├── app/
│   └── Http/
│       └── Controllers/
│           ├── Auth/
│           │   └── AdminAuthController.php ✅
│           └── Admin/
│               └── DashboardController.php ✅
├── resources/
│   └── js/
│       └── pages/
│           ├── auth/
│           │   └── AdminLogin.vue ✅
│           └── Admin/
│               └── Dashboard.vue ✅
└── routes/
    └── web.php (updated) ✅
```

## Future Enhancements

Consider adding:
- [ ] Two-factor authentication for admin logins
- [ ] IP whitelist for admin access
- [ ] Login attempt rate limiting
- [ ] Activity logging for admin actions
- [ ] Email notifications for admin logins
- [ ] Session timeout warnings
- [ ] Password fallback (in addition to OAuth)
- [ ] Role-based dashboard customization

## Support

For issues with admin login:
1. Check error logs: `storage/logs/laravel.log`
2. Verify user has correct flags in database
3. Clear all caches: `php artisan optimize:clear`
4. Check OAuth credentials in `.env`

## Summary

The admin login system provides:
- ✅ Dedicated login portal at `/admin/login`
- ✅ Professional security-focused UI
- ✅ Google OAuth with NEMSU domain restriction
- ✅ Role-based access control and redirection
- ✅ Comprehensive error handling
- ✅ Separate admin dashboard
- ✅ Mobile responsive design
- ✅ Security logging and monitoring

Admins can now login through a dedicated, professional interface separate from regular users, with proper privilege checking and role-based dashboard access.
