# Admin User Seeder Guide

## Overview

A database seeder has been created to automatically set up admin accounts for testing and development. This seeder creates three admin users with different role levels.

## Admin Accounts Created

| Email | Password | Role | Access Level |
|-------|----------|------|--------------|
| `superadmin@nemsu.edu.ph` | `Superadmin123!` | Superadmin | Full system access |
| `admin@nemsu.edu.ph` | `Admin123!` | Admin | User management |
| `editor@nemsu.edu.ph` | `Editor123!` | Editor | Content moderation |

## Running the Seeder

### Option 1: Run All Seeders (Including Admin Seeder)
```bash
php artisan db:seed
```

This will run the `DatabaseSeeder` which includes the `AdminUserSeeder`.

### Option 2: Run Only Admin Seeder
```bash
php artisan db:seed --class=AdminUserSeeder
```

This will only create/update the admin users without running other seeders.

### Option 3: Fresh Database with Seeders
```bash
php artisan migrate:fresh --seed
```

⚠️ **Warning**: This will drop all tables and recreate them with fresh data.

## What the Seeder Does

### 1. Creates Three Admin Users

**Superadmin Account:**
- Email: `superadmin@nemsu.edu.ph`
- Password: `Superadmin123!`
- Display Name: Super Admin
- Flags: `is_admin = true`, `is_superadmin = true`
- Access: Full system access via `/superadmin`

**Admin Account:**
- Email: `admin@nemsu.edu.ph`
- Password: `Admin123!`
- Display Name: Admin User
- Flags: `is_admin = true`, `is_superadmin = false`
- Access: Admin dashboard via `/admin/dashboard`

**Editor Account:**
- Email: `editor@nemsu.edu.ph`
- Password: `Editor123!`
- Display Name: Editor User
- Flags: `is_admin = true`, `is_superadmin = false`
- Access: Admin dashboard via `/admin/dashboard`

### 2. Creates AdminRole Records

For each user, the seeder creates an `AdminRole` record in the `admin_roles` table:
- Links user to their role (superadmin, admin, or editor)
- Sets `is_active = true`
- Records assignment timestamp
- Self-assigns the role (assigned_by = user_id)

### 3. Handles Existing Users

If a user with the same email already exists:
- Updates the password
- Updates admin flags
- Updates profile completion status
- Creates/updates the admin role

## User Details

Each admin user is created with:

```php
[
    'name' => 'Display Name',
    'display_name' => 'Short Name',
    'fullname' => 'Full Name',
    'email' => 'email@nemsu.edu.ph',
    'password' => Hash::make('Password'),
    'nemsu_id' => 'Unique ID',
    'email_verified_at' => now(),
    'is_admin' => true/false,
    'is_superadmin' => true/false,
    'profile_completed' => true,
    'gender' => 'prefer_not_to_say',
    'date_of_birth' => 25 years ago,
    'campus' => 'Main Campus',
    'academic_program' => 'Administration',
    'year_level' => 'Faculty',
]
```

## Testing the Login

After running the seeder:

### 1. Visit Admin Login
```
http://your-domain.com/admin/login
```

### 2. Login as Superadmin
- **Email**: `superadmin@nemsu.edu.ph`
- **Password**: `Superadmin123!`
- **Redirect**: `/superadmin`

### 3. Login as Admin
- **Email**: `admin@nemsu.edu.ph`
- **Password**: `Admin123!`
- **Redirect**: `/admin/dashboard`

### 4. Login as Editor
- **Email**: `editor@nemsu.edu.ph`
- **Password**: `Editor123!`
- **Redirect**: `/admin/dashboard`

## Seeder Output

When you run the seeder, you'll see output like:

```
Creating admin users...

Created user: superadmin@nemsu.edu.ph
Assigned role 'superadmin' to: superadmin@nemsu.edu.ph

Created user: admin@nemsu.edu.ph
Assigned role 'admin' to: admin@nemsu.edu.ph

Created user: editor@nemsu.edu.ph
Assigned role 'editor' to: editor@nemsu.edu.ph

Admin users created successfully!

┌─────────────────────────────┬──────────────────┬───────────┬──────────────────────┐
│ Email                       │ Password         │ Role      │ Access               │
├─────────────────────────────┼──────────────────┼───────────┼──────────────────────┤
│ superadmin@nemsu.edu.ph     │ Superadmin123!   │ Superadmin│ Full system access   │
│ admin@nemsu.edu.ph          │ Admin123!        │ Admin     │ User management      │
│ editor@nemsu.edu.ph         │ Editor123!       │ Editor    │ Content moderation   │
└─────────────────────────────┴──────────────────┴───────────┴──────────────────────┘

You can now login at: /admin/login
```

## Customizing the Seeder

To modify the admin accounts, edit `database/seeders/AdminUserSeeder.php`:

### Change Passwords
```php
[
    'email' => 'superadmin@nemsu.edu.ph',
    'password' => 'YourNewPassword123!',
    // ...
]
```

### Change Email Addresses
```php
[
    'email' => 'your-email@nemsu.edu.ph',
    // ...
]
```

### Add More Admin Users
```php
$admins = [
    // Existing users...
    [
        'name' => 'New Admin',
        'display_name' => 'New Admin',
        'fullname' => 'New Administrator',
        'email' => 'newadmin@nemsu.edu.ph',
        'password' => 'NewAdmin123!',
        'nemsu_id' => 'NA-2024-001',
        'role' => 'admin',
        'is_admin' => true,
        'is_superadmin' => false,
        'profile_completed' => true,
    ],
];
```

## Database Tables Affected

The seeder modifies these tables:

1. **users**
   - Creates/updates user records
   - Sets admin flags
   - Sets email verification
   - Sets profile completion

2. **admin_roles**
   - Creates/updates admin role assignments
   - Links users to their roles
   - Sets active status

## Security Notes

### For Development/Testing
✅ Use the seeder to quickly set up test accounts
✅ Convenient for local development
✅ Easy to reset and recreate

### For Production
⚠️ **Important Security Considerations:**

1. **Change Default Passwords**
   ```bash
   php artisan tinker
   
   $admin = User::where('email', 'superadmin@nemsu.edu.ph')->first();
   $admin->password = Hash::make('NewSecurePassword123!');
   $admin->save();
   ```

2. **Use Strong Passwords**
   - At least 12 characters
   - Mix of uppercase, lowercase, numbers, symbols
   - No dictionary words
   - Unique per account

3. **Change Email Addresses**
   - Use real admin email addresses
   - Not the default @nemsu.edu.ph test accounts

4. **Limit Admin Accounts**
   - Only create necessary admin accounts
   - Remove test accounts in production
   - Regularly audit admin access

5. **Consider Environment**
   ```php
   // Only seed in development
   if (app()->environment('local', 'development')) {
       $this->call([AdminUserSeeder::class]);
   }
   ```

## Troubleshooting

### "User already exists" Warning
This is normal if you run the seeder multiple times. The seeder will update the existing user instead of creating a duplicate.

### Can't Login After Seeding
1. Verify the seeder ran successfully
2. Check the database:
   ```sql
   SELECT email, is_admin, is_superadmin FROM users 
   WHERE email LIKE '%@nemsu.edu.ph';
   ```
3. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

### Wrong Redirect After Login
- Superadmins should go to `/superadmin`
- Admins/Editors should go to `/admin/dashboard`
- Check the `is_superadmin` flag in database

### Password Doesn't Work
Make sure you're using the correct password:
- Superadmin: `Superadmin123!`
- Admin: `Admin123!`
- Editor: `Editor123!`

Case-sensitive and includes the exclamation mark!

## Best Practices

### Development
1. Run seeder after migrations
2. Use seeder for quick setup
3. Test with different role levels
4. Reset database as needed

### Testing
1. Use seeded accounts for automated tests
2. Test role-based access control
3. Verify privilege checks
4. Test dashboard redirections

### Production
1. **Never** use seeded accounts in production
2. Create real admin accounts manually
3. Use strong, unique passwords
4. Enable two-factor authentication (if implemented)
5. Regularly audit admin accounts

## Commands Quick Reference

```bash
# Run all seeders
php artisan db:seed

# Run only admin seeder
php artisan db:seed --class=AdminUserSeeder

# Fresh database with seeders
php artisan migrate:fresh --seed

# Check seeded users
php artisan tinker
User::where('is_admin', true)->get(['email', 'display_name', 'is_superadmin']);

# Change password for seeded admin
php artisan tinker
$admin = User::where('email', 'admin@nemsu.edu.ph')->first();
$admin->password = Hash::make('NewPassword123!');
$admin->save();
```

## File Location

```
database/seeders/
├── AdminUserSeeder.php      ← Admin user seeder
└── DatabaseSeeder.php        ← Main seeder (includes AdminUserSeeder)
```

## Example Usage

### Fresh Setup for Development
```bash
# 1. Run migrations
php artisan migrate

# 2. Run seeders (includes admin users)
php artisan db:seed

# 3. Visit admin login
# http://localhost/admin/login

# 4. Login with:
# Email: superadmin@nemsu.edu.ph
# Password: Superadmin123!
```

### Reset Admin Accounts
```bash
# Re-run only admin seeder
php artisan db:seed --class=AdminUserSeeder

# This will update existing admin accounts
# with the default passwords and settings
```

## Integration with DatabaseSeeder

The `AdminUserSeeder` is automatically called by `DatabaseSeeder`:

```php
public function run(): void
{
    $this->call([
        AdminUserSeeder::class,  // Creates admin accounts first
        UsersSeeder::class,       // Then creates regular users
    ]);
}
```

This ensures admin accounts are created before regular users, maintaining proper role hierarchy.

## Summary

✅ **Three admin accounts created automatically**
✅ **Different role levels (Superadmin, Admin, Editor)**
✅ **Pre-configured with secure passwords**
✅ **Ready to use immediately after seeding**
✅ **Can be run multiple times safely**
✅ **Updates existing accounts if present**
✅ **Creates AdminRole records automatically**
✅ **Perfect for development and testing**

---

**Quick Start:**
```bash
php artisan db:seed --class=AdminUserSeeder
```

Then login at `/admin/login` with:
- `superadmin@nemsu.edu.ph` / `Superadmin123!`
- `admin@nemsu.edu.ph` / `Admin123!`
- `editor@nemsu.edu.ph` / `Editor123!`
