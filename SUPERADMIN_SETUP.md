# Superadmin Portal - Quick Setup Guide

## ✅ Installation Complete!

The Superadmin Portal has been successfully installed with all migrations run.

## 🚀 Getting Started

### Step 1: Create Your First Superadmin

You need to designate at least one user as a superadmin to access the portal. Use one of these methods:

#### Option A: Using Artisan Command (Recommended)
```bash
php artisan make:superadmin your-email@example.com
```

Replace `your-email@example.com` with the email of the user you want to make a superadmin.

#### Option B: Using Tinker
```bash
php artisan tinker
```

Then run:
```php
$user = User::where('email', 'your-email@example.com')->first();
$user->is_superadmin = true;
$user->is_admin = true;
$user->save();

\App\Models\Superadmin\AdminRole::create([
    'user_id' => $user->id,
    'role' => 'superadmin',
    'is_active' => true,
    'assigned_by' => $user->id,
]);
```

### Step 2: Access the Superadmin Portal

**Option A: Via Dedicated Admin Login (Recommended)**
1. Navigate to `http://your-domain.com/admin/login`
2. Click "Sign in with NEMSU Google"
3. Authenticate with your superadmin account
4. You'll be automatically redirected to `/superadmin`

**Option B: Via Regular Login + Menu**
1. Log in to your NEMSU Match account with the superadmin email
2. Click on your profile menu in the sidebar
3. You should see a new "Superadmin Portal" option with a shield icon
4. Click it to access the portal

**Option C: Direct URL**
Navigate directly to: `http://your-domain.com/superadmin` (requires authentication)

## 📋 What's Included

### Pages Created
- ✅ **Dashboard** (`/superadmin`) - Statistics and overview
- ✅ **Users Management** (`/superadmin/users`) - View and manage all users
- ✅ **Admins & Editors** (`/superadmin/admins`) - Assign and manage admin roles
- ✅ **Settings** (`/superadmin/settings`) - Configure app settings

### Features
- ✅ **Maintenance Mode Toggle** - Prevent users from accessing the app
- ✅ **Pre-Registration Mode** - Allow early signups
- ✅ **Registration Control** - Enable/disable new registrations
- ✅ **Feature Toggles** - Enable/disable chat, video calls, etc.
- ✅ **User Limits** - Set max daily swipes and matches
- ✅ **Role Management** - Assign Superadmin, Admin, or Editor roles
- ✅ **User Search & Filtering** - Find users quickly
- ✅ **Real-time Statistics** - View growth charts and metrics

### Database Tables
- ✅ `admin_roles` - Stores admin role assignments
- ✅ `app_settings` - Stores all app configuration
- ✅ `users` - Added `is_superadmin` column

### API Endpoints
All protected with `auth`, `verified`, and `superadmin` middleware:
- `GET /superadmin` - Dashboard
- `GET /superadmin/users` - List users
- `GET /superadmin/admins` - Manage admins
- `GET /superadmin/settings` - App settings
- And more...

## 🔐 Security

- Only users with `is_superadmin = true` can access the portal
- All actions are CSRF-protected
- Role changes are audited (assigned_by, assigned_at)
- Superadmins cannot remove their own superadmin role
- Critical settings cannot be deleted

## 📊 Default Settings

The following settings are pre-configured (you can change them in the portal):

| Setting | Default | Description |
|---------|---------|-------------|
| Maintenance Mode | Disabled | Blocks all user access |
| Pre-Registration Mode | Disabled | Allows early signups |
| Allow Registration | Enabled | Users can register |
| Enable Chat | Enabled | Chat feature available |
| Enable Video Call | Disabled | Video call feature |
| Max Daily Swipes | 50 | Per user limit |
| Max Daily Matches | 20 | Per user limit |

## 🎨 UI Components

All Vue components are located in:
```
resources/js/pages/Superadmin/
├── Layout.vue       # Main layout with sidebar
├── Dashboard.vue    # Statistics dashboard
├── Users.vue        # User management
├── Admins.vue       # Admin role management
└── Settings.vue     # App settings configuration
```

## 🛠️ Customization

### Adding New Settings

1. Insert into `app_settings` table:
```php
use App\Models\Superadmin\AppSetting;

AppSetting::create([
    'key' => 'my_new_setting',
    'value' => 'default_value',
    'type' => 'boolean', // or string, integer, json
    'group' => 'features',
    'description' => 'Description of what this setting does',
]);
```

2. Use it in your code:
```php
$value = AppSetting::get('my_new_setting', 'default_value');
```

### Adding New Admin Permissions

Edit the `AdminRole` model and add custom permission checks:
```php
public function hasPermission(string $permission): bool
{
    $permissions = $this->permissions ?? [];
    return in_array($permission, $permissions);
}
```

## 📱 Mobile Responsive

The Superadmin Portal is fully responsive and works on:
- ✅ Desktop (1920px+)
- ✅ Laptop (1366px+)
- ✅ Tablet (768px+)
- ✅ Mobile (375px+)

## 🐛 Troubleshooting

### "403 Forbidden" when accessing /superadmin
- Make sure you've set `is_superadmin = true` for your user
- Clear your browser cache and cookies
- Log out and log back in

### Navigation link not showing
- Ensure the user object includes `is_superadmin` field
- Check that you're logged in with the correct account
- Refresh the page

### Settings not saving
- Check browser console for CSRF token errors
- Verify database connection
- Check file permissions on storage folder

## 📚 Documentation

Full documentation available in:
- `SUPERADMIN_PORTAL.md` - Complete feature documentation
- `SUPERADMIN_SETUP.md` - This file

## 🔄 Next Steps

1. ✅ Create your first superadmin user
2. ✅ Log in and access `/superadmin`
3. ✅ Configure your app settings
4. ✅ Assign admin/editor roles to other users
5. ✅ Monitor user activity and growth

## 💡 Tips

- Use the Dashboard to monitor app health
- Set up admin/editor roles for content moderation
- Enable maintenance mode before major updates
- Configure user limits to manage app load
- Regularly check user statistics

## 🆘 Need Help?

Check the full documentation in `SUPERADMIN_PORTAL.md` or contact the development team.

---

**Congratulations! Your Superadmin Portal is ready to use! 🎉**
