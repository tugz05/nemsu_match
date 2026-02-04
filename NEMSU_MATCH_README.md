# NEMSU Match - Dating App Feature

A modern, mobile-responsive dating app feature designed exclusively for NEMSU (North Eastern Mindanao State University) students. Connect with fellow students based on academic programs, extracurricular involvement, and mutual interests.

## Features

### 🎓 Student-Exclusive Access
- Login with NEMSU workspace account (OAuth integration)
- Email verification with @nemsu.edu.ph domain restriction
- Secure authentication using Laravel Fortify

### 👤 Comprehensive Profile System
Students create detailed profiles including:
- **Basic Information**: Display name, full name, date of birth, gender
- **Academic Details**: Campus, academic program, year level, favorite courses
- **Interests & Activities**: Research interests, extracurricular activities, hobbies
- **Goals**: Academic goals and aspirations
- **Profile Picture**: Upload and display profile photos
- **Bio**: Personal introduction (20-500 characters)

### 💝 Smart Matching
- Discover potential matches based on campus, academic program, and interests
- Swipe-based interface (Like, Pass, Super Like)
- Mobile-first, responsive design
- Beautiful gradient UI with pink/purple theme

### 🔒 Privacy & Security
- Profile completion required before accessing the app
- NEMSU-only access restriction
- Secure data handling and validation

## Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & npm
- MySQL/SQLite database
- Laravel 12.x

### Step 1: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### Step 2: Configure Environment

1. Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

2. Generate application key:
```bash
php artisan key:generate
```

3. Configure your database in `.env`:
```env
DB_CONNECTION=sqlite
# OR for MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nemsu_match
DB_USERNAME=root
DB_PASSWORD=
```

4. Configure NEMSU OAuth (see OAuth Configuration section below)

### Step 3: Run Migrations

```bash
# Create database tables
php artisan migrate
```

This will create the users table with all NEMSU Match profile fields:
- Basic user info (name, email, password)
- Display name and full name
- Campus and academic information
- Profile picture path
- Interests and activities
- Academic goals
- Bio and personal details
- Profile completion status
- NEMSU ID

### Step 4: Create Storage Symlink

```bash
# Link storage for profile pictures
php artisan storage:link
```

### Step 5: Build Frontend Assets

```bash
# For development
npm run dev

# For production
npm run build
```

### Step 6: Start Development Server

```bash
# Start Laravel server
php artisan serve

# Or use the built-in dev script (runs server + queue + vite)
composer dev
```

## OAuth Configuration

### Setting Up NEMSU Workspace Authentication

The app is designed to integrate with NEMSU's OAuth provider. You have several options:

#### Option 1: Google Workspace (Recommended if NEMSU uses Gmail)

1. Install Laravel Socialite:
```bash
composer require laravel/socialite
composer require socialiteproviders/google
```

2. Configure in `.env`:
```env
NEMSU_OAUTH_CLIENT_ID=your_google_client_id
NEMSU_OAUTH_CLIENT_SECRET=your_google_client_secret
NEMSU_OAUTH_REDIRECT_URI=http://localhost:8000/oauth/nemsu/callback
NEMSU_OAUTH_DOMAIN=nemsu.edu.ph
```

3. Update `app/Http/Controllers/Auth/NEMSUOAuthController.php`:

```php
use Laravel\Socialite\Facades\Socialite;

public function redirect()
{
    return Socialite::driver('google')
        ->with(['hd' => 'nemsu.edu.ph']) // Restrict to NEMSU domain
        ->redirect();
}

public function callback(Request $request)
{
    $nemsuUser = Socialite::driver('google')->user();
    
    // Validate email domain
    if (!Str::endsWith($nemsuUser->email, '@nemsu.edu.ph')) {
        return redirect()->route('nemsu.login')->withErrors([
            'email' => 'Only NEMSU email addresses are allowed.',
        ]);
    }
    
    // Find or create user
    $user = User::firstOrCreate(
        ['email' => $nemsuUser->email],
        [
            'name' => $nemsuUser->name,
            'nemsu_id' => $nemsuUser->id,
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(32)),
        ]
    );
    
    Auth::login($user, true);
    
    if (!$user->profile_completed) {
        return redirect()->route('profile.setup');
    }
    
    return redirect()->route('dashboard');
}
```

#### Option 2: Microsoft 365 (If NEMSU uses Office 365)

Similar setup using the Microsoft OAuth provider.

#### Option 3: Custom OAuth Provider

If NEMSU has a custom authentication system, integrate accordingly.

## Routes

### Public Routes
- `GET /nemsu/login` - NEMSU Match login page
- `GET /oauth/nemsu/redirect` - Redirect to OAuth provider
- `GET /oauth/nemsu/callback` - OAuth callback handler

### Authenticated Routes
- `GET /profile/setup` - Profile setup page (multi-step form)
- `POST /profile/setup` - Submit profile setup
- `PUT /profile/update` - Update existing profile
- `GET /dashboard` - Main dating app dashboard
- `POST /nemsu/logout` - Logout

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── NEMSUOAuthController.php
│   │   └── ProfileSetupController.php
│   ├── Middleware/
│   │   └── EnsureProfileCompleted.php
│   └── Requests/
│       └── ProfileSetupRequest.php
├── Models/
│   └── User.php
database/
├── migrations/
│   └── 2026_02_03_091344_add_nemsu_match_fields_to_users_table.php
resources/
├── js/
│   ├── layouts/
│   │   └── auth/
│   │       └── NEMSUMatchLayout.vue
│   └── pages/
│       ├── auth/
│       │   └── NEMSULogin.vue
│       ├── profile/
│       │   └── ProfileSetup.vue
│       └── NEMSUMatchDashboard.vue
```

## Profile Setup Flow

1. **Authentication**: User logs in with NEMSU workspace account
2. **Profile Check**: System checks if profile is completed
3. **Profile Setup** (if not completed): 4-step form
   - Step 1: Basic Information (name, DOB, gender)
   - Step 2: Academic Details (campus, program, year, courses)
   - Step 3: Interests & Activities (research, hobbies, goals)
   - Step 4: Profile Photo & Bio
4. **Dashboard Access**: After profile completion, access main app

## UI/UX Features

### Mobile-Responsive Design
- Optimized for mobile devices (primary platform)
- Responsive breakpoints for tablet and desktop
- Touch-friendly swipe gestures

### Modern Design Elements
- Gradient backgrounds (pink/purple theme)
- Rounded cards and buttons
- Smooth animations and transitions
- Floating card effects
- Progress indicators

### User Experience
- Multi-step form with progress tracking
- Clear validation messages
- Intuitive navigation
- Visual feedback for actions

## Security Features

1. **Email Domain Restriction**: Only @nemsu.edu.ph emails allowed
2. **OAuth Authentication**: Secure login via NEMSU workspace
3. **Profile Completion Middleware**: Ensures complete profiles
4. **Form Validation**: Server-side and client-side validation
5. **File Upload Security**: Image validation, size limits (5MB max)
6. **CSRF Protection**: Laravel's built-in CSRF tokens

## Database Schema

### Users Table (Extended)

| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| name | string | User's name |
| email | string | NEMSU email (unique) |
| password | string | Hashed password |
| display_name | string | Preferred display name |
| fullname | string | Complete legal name |
| campus | string | NEMSU campus location |
| academic_program | string | Degree program |
| year_level | string | Current year/level |
| profile_picture | string | Path to profile image |
| courses | text | Favorite courses |
| research_interests | text | Academic research interests |
| extracurricular_activities | text | Clubs, orgs, sports |
| academic_goals | text | Future aspirations |
| bio | text | Personal bio (500 chars max) |
| date_of_birth | date | Birth date |
| gender | string | Gender identity |
| interests | text | Hobbies and interests |
| profile_completed | boolean | Profile completion status |
| nemsu_id | string | NEMSU ID from OAuth |
| email_verified_at | timestamp | Email verification |
| created_at | timestamp | Account creation |
| updated_at | timestamp | Last update |

## Customization

### Changing Colors
Edit the Tailwind classes in the Vue components:
- Primary: `pink-500`, `pink-600`
- Secondary: `purple-500`, `purple-600`
- Backgrounds: `from-pink-100`, `via-purple-50`, `to-pink-50`

### Adding More Profile Fields
1. Create a new migration
2. Update User model's `$fillable` array
3. Add fields to ProfileSetupRequest validation
4. Update ProfileSetup.vue form

### Customizing Match Algorithm
Edit the matching logic in the dashboard controller (to be implemented).

## Testing

```bash
# Run tests
php artisan test

# Specific test
php artisan test --filter=ProfileSetupTest
```

## Deployment

### Production Checklist
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure actual NEMSU OAuth credentials
- [ ] Set up SSL/HTTPS
- [ ] Configure proper database (MySQL/PostgreSQL)
- [ ] Run `npm run build` for optimized assets
- [ ] Set up proper file storage (S3, etc.)
- [ ] Configure email service for notifications
- [ ] Set up queue workers for background jobs
- [ ] Enable rate limiting
- [ ] Configure proper logging

### Production Commands
```bash
# Optimize application
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build assets
npm run build
```

## Future Enhancements

### Planned Features
- [ ] Advanced matching algorithm based on compatibility
- [ ] Real-time messaging system
- [ ] Match history and favorites
- [ ] Search and filter options
- [ ] Profile verification badges
- [ ] Event creation and RSVP
- [ ] Study group matching
- [ ] Privacy settings and blocking
- [ ] Notification system
- [ ] Admin dashboard for moderation

### Technical Improvements
- [ ] Implement actual matching algorithm
- [ ] Add WebSocket for real-time features
- [ ] Implement image optimization
- [ ] Add comprehensive test coverage
- [ ] Set up CI/CD pipeline
- [ ] Add analytics and reporting

## Support & Documentation

### Common Issues

**Issue**: "Only NEMSU email addresses are allowed"
- **Solution**: Ensure you're using your @nemsu.edu.ph email address

**Issue**: Profile picture upload fails
- **Solution**: Check file size (max 5MB) and format (JPG, PNG, GIF)

**Issue**: Can't access dashboard
- **Solution**: Complete your profile setup first

### Laravel Resources
- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com)
- [Vue.js Documentation](https://vuejs.org)
- [Tailwind CSS Documentation](https://tailwindcss.com)

## License

This project is built for NEMSU and follows the institution's guidelines and policies.

## Contributors

Developed for North Eastern Mindanao State University (NEMSU)

## Contact

For issues, questions, or contributions, please contact the development team.

---

**Note**: This is a dating/matchmaking application designed specifically for NEMSU students. Please use responsibly and respect all users. Follow NEMSU's code of conduct and community guidelines.
