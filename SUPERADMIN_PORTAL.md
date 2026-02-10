# Superadmin Portal - NEMSU Match

## Overview

The Superadmin Portal is a comprehensive administrative dashboard for managing the NEMSU Match dating application. It provides full control over users, administrators, and system settings.

## Features

### 1. Dashboard
- **Statistics Overview**: View total users, active users, matches, conversations, and more
- **User Growth Charts**: Visual representation of user growth over the last 30 days
- **Gender Distribution**: Pie chart showing male, female, and other gender distribution
- **Recent Users**: Quick view of the latest registered users

### 2. Users Management (`/superadmin/users`)
- **User Listing**: Paginated list of all users with filtering
- **Search**: Search by name, email, or NEMSU ID
- **Filters**:
  - Gender (Male, Female, Non-Binary)
  - Status (Verified, Unverified, Profile Completed, Profile Incomplete, Admins)
  - Sort by (Date Joined, Last Seen, Name)
  - Sort order (Ascending, Descending)
- **User Actions**: View user profiles

### 3. Admins & Editors Management (`/superadmin/admins`)
- **Role Management**: Assign and manage admin roles
- **Three Role Types**:
  - **Superadmin**: Full system access (can manage all settings, admins, and users)
  - **Admin**: Full management access (can manage users and content)
  - **Editor**: Limited permissions (can moderate content)
- **User Search**: Search for users to assign admin roles
- **Role Actions**:
  - Assign new roles
  - Edit existing roles
  - Toggle active/inactive status
  - Remove roles

### 4. Settings Management (`/superadmin/settings`)

#### General Settings
- **Maintenance Mode**: Toggle to prevent users from accessing the app
- Other system-wide settings

#### Users Settings
- **Pre-Registration Mode**: Allow users to sign up before official launch
- **Allow Registration**: Toggle user registration on/off

#### Features Settings
- **Enable Chat**: Toggle chat functionality
- **Enable Video Call**: Toggle video call feature
- **Max Daily Swipes**: Set maximum swipes per user per day (default: 50)
- **Max Daily Matches**: Set maximum matches per user per day (default: 20)

## Database Structure

### New Tables

#### `admin_roles`
Stores admin role assignments
- `id`: Primary key
- `user_id`: Foreign key to users table
- `role`: Enum (superadmin, admin, editor)
- `permissions`: JSON (for granular permissions)
- `is_active`: Boolean
- `assigned_at`: Timestamp
- `assigned_by`: Foreign key to users table
- `timestamps`

#### `app_settings`
Stores application configuration settings
- `id`: Primary key
- `key`: Unique setting key
- `value`: Setting value (stored as text)
- `type`: Setting type (string, boolean, integer, json)
- `group`: Setting group (general, users, features)
- `description`: Setting description
- `timestamps`

### User Table Modifications
- Added `is_superadmin` column (boolean, default: false)

## Access Control

### Middleware
- **`superadmin`**: Ensures only superadmin users can access the portal
- Location: `app/Http/Middleware/EnsureSuperadmin.php`
- Registered in `bootstrap/app.php`

### Routes
All superadmin routes are protected with:
```php
Route::middleware(['auth', 'verified', 'superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        // Superadmin routes
    });
```

## How to Create the First Superadmin

Since there's no UI to create the first superadmin, you need to do it manually via database or tinker:

### Method 1: Using Tinker
```bash
php artisan tinker
```

```php
$user = User::where('email', 'your-email@example.com')->first();
$user->is_superadmin = true;
$user->is_admin = true;
$user->save();
```

### Method 2: Direct Database Update
```sql
UPDATE users 
SET is_superadmin = 1, is_admin = 1 
WHERE email = 'your-email@example.com';
```

### Method 3: Create Admin Role Record
```php
use App\Models\Superadmin\AdminRole;

$user = User::where('email', 'your-email@example.com')->first();
$user->is_superadmin = true;
$user->is_admin = true;
$user->save();

AdminRole::create([
    'user_id' => $user->id,
    'role' => 'superadmin',
    'is_active' => true,
    'assigned_by' => $user->id,
]);
```

## API Endpoints

### Dashboard
- `GET /superadmin` - View dashboard

### Admins Management
- `GET /superadmin/admins` - List all admin roles
- `POST /superadmin/admins` - Assign new admin role
- `PUT /superadmin/admins/{adminRole}` - Update admin role
- `DELETE /superadmin/admins/{adminRole}` - Remove admin role
- `GET /superadmin/admins/search-users?q={query}` - Search users

### Users Management
- `GET /superadmin/users` - List users (with filters & pagination)
- `GET /superadmin/users/{user}` - View user details
- `PUT /superadmin/users/{user}` - Update user
- `POST /superadmin/users/{user}/toggle-status` - Toggle user status
- `DELETE /superadmin/users/{user}` - Delete user

### Settings Management
- `GET /superadmin/settings` - View all settings
- `POST /superadmin/settings` - Create new setting
- `PUT /superadmin/settings/{appSetting}` - Update setting
- `DELETE /superadmin/settings/{appSetting}` - Delete setting
- `POST /superadmin/settings/bulk-update` - Update multiple settings

## Using Settings in Your Application

### Get a Setting Value
```php
use App\Models\Superadmin\AppSetting;

// Get maintenance mode
$maintenanceMode = AppSetting::get('maintenance_mode', false);

// Get max daily swipes
$maxSwipes = AppSetting::get('max_daily_swipes', 50);
```

### Set a Setting Value
```php
use App\Models\Superadmin\AppSetting;

AppSetting::set('maintenance_mode', true);
```

### In Middleware (Example)
```php
use App\Models\Superadmin\AppSetting;

public function handle(Request $request, Closure $next): Response
{
    if (AppSetting::get('maintenance_mode', false)) {
        return response()->view('maintenance', [], 503);
    }
    
    return $next($request);
}
```

## File Structure

```
dating-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Superadmin/
│   │   │       ├── DashboardController.php
│   │   │       ├── AdminController.php
│   │   │       ├── UserController.php
│   │   │       └── SettingsController.php
│   │   └── Middleware/
│   │       └── EnsureSuperadmin.php
│   └── Models/
│       └── Superadmin/
│           ├── AdminRole.php
│           └── AppSetting.php
├── database/
│   └── migrations/
│       ├── 2026_02_10_115218_add_is_superadmin_to_users_table.php
│       ├── 2026_02_10_115235_create_app_settings_table.php
│       └── 2026_02_10_115311_create_admin_roles_table.php
└── resources/
    └── js/
        └── pages/
            └── Superadmin/
                ├── Layout.vue
                ├── Dashboard.vue
                ├── Admins.vue
                ├── Users.vue
                └── Settings.vue
```

## Security Considerations

1. **Access Control**: Only users with `is_superadmin = true` can access the portal
2. **CSRF Protection**: All POST/PUT/DELETE requests require CSRF token
3. **Role Hierarchy**: Superadmins cannot remove their own superadmin role
4. **Critical Settings**: Some settings (like maintenance_mode) cannot be deleted
5. **Audit Trail**: Admin role assignments track who assigned the role and when

## Navigation

Access the superadmin portal at: `https://your-domain.com/superadmin`

From the main app, superadmins will see a "Superadmin" link in their navigation menu.

## Future Enhancements

Consider adding:
1. Activity logs for all admin actions
2. User suspension/ban functionality
3. Bulk user operations
4. Advanced analytics and reporting
5. Email notification settings
6. Content moderation dashboard
7. Report management system
8. Backup and restore functionality

## Support

For issues or questions about the Superadmin Portal, contact the development team.
