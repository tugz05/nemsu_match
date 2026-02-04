# NEMSU Match - Quick Start Guide

Get the NEMSU Match dating app up and running in minutes!

## 🚀 Quick Setup (5 Minutes)

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Configure Environment
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Setup Database
```bash
# For SQLite (easiest)
touch database/database.sqlite

# Run migrations
php artisan migrate
```

### 4. Create Storage Link
```bash
php artisan storage:link
```

### 5. Start Development
```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start Vite dev server
npm run dev
```

**OR use the all-in-one command:**
```bash
composer dev
```

### 6. Visit the App
Open your browser and navigate to:
```
http://localhost:8000/nemsu/login
```

## 📱 Testing the App (Development Mode)

Since OAuth is not configured yet, you can test with mock data:

1. Visit `http://localhost:8000/nemsu/login`
2. Click "Login with NEMSU Account"
3. The callback will create a test user automatically
4. Complete the 4-step profile setup
5. Access the main dashboard

## 🔧 Configuration

### Minimum Required Settings (.env)
```env
APP_NAME="NEMSU Match"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite

SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### Optional: Configure OAuth (Production)

For production with real NEMSU OAuth:

1. Install Socialite:
```bash
composer require laravel/socialite
```

2. Add to `.env`:
```env
NEMSU_OAUTH_CLIENT_ID=your_client_id
NEMSU_OAUTH_CLIENT_SECRET=your_client_secret
NEMSU_OAUTH_REDIRECT_URI=http://localhost:8000/oauth/nemsu/callback
NEMSU_OAUTH_DOMAIN=nemsu.edu.ph
```

3. Update `NEMSUOAuthController.php` with actual OAuth implementation

## 📋 Features to Test

### ✅ Login Page
- Beautiful mobile-responsive design
- Pink/purple gradient theme
- Animated profile cards
- Single sign-on button

### ✅ Profile Setup (4 Steps)
1. **Basic Info**: Name, DOB, gender
2. **Academic**: Campus, program, year, courses
3. **Interests**: Research, activities, hobbies, goals
4. **Photo & Bio**: Upload picture, write bio

### ✅ Dashboard
- Swipe-based matching interface
- Like/Pass/Super Like actions
- Match cards with profile details
- Mobile-optimized UI

## 🎨 Customization

### Change Brand Colors
Edit Vue components:
- Primary: `pink-500` → your color
- Secondary: `purple-500` → your color

### Add Campus Options
Edit `resources/js/pages/profile/ProfileSetup.vue`:
```javascript
const campusList = [
    'Main Campus - Tandag City',
    'Cantilan Campus',
    'Lianga Campus',
    'Bislig Campus',
    // Add more...
];
```

## 🐛 Troubleshooting

### Issue: "SQLSTATE[HY000] [14] unable to open database file"
**Solution**: Create the database file
```bash
touch database/database.sqlite
```

### Issue: "The stream or file could not be opened"
**Solution**: Set proper permissions
```bash
chmod -R 775 storage bootstrap/cache
```

### Issue: "Mix file does not exist"
**Solution**: Build assets
```bash
npm run dev
```

### Issue: Profile pictures not showing
**Solution**: Create storage link
```bash
php artisan storage:link
```

## 📚 Next Steps

1. ✅ Test the login flow
2. ✅ Complete a test profile
3. ✅ Explore the dashboard
4. 📖 Read the full `NEMSU_MATCH_README.md`
5. 🔧 Configure real OAuth for production
6. 🎨 Customize design to match NEMSU branding
7. 🚀 Deploy to production

## 🎯 Production Deployment

When ready to deploy:

1. Set environment to production:
```env
APP_ENV=production
APP_DEBUG=false
```

2. Configure real database (MySQL/PostgreSQL)

3. Set up actual NEMSU OAuth

4. Build optimized assets:
```bash
npm run build
```

5. Optimize Laravel:
```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. Set up proper file storage (AWS S3, etc.)

7. Configure queue workers for background jobs

8. Enable SSL/HTTPS

## 💡 Tips

- **Mobile First**: Most students will use mobile devices
- **Fast Loading**: Keep profile pictures optimized
- **Clear CTAs**: Make actions obvious and easy
- **Privacy**: Respect user privacy and data
- **Moderation**: Plan for content moderation

## 🆘 Need Help?

Refer to:
- `NEMSU_MATCH_README.md` - Comprehensive documentation
- [Laravel Docs](https://laravel.com/docs)
- [Vue.js Docs](https://vuejs.org)
- [Inertia.js Docs](https://inertiajs.com)

---

**Happy Coding! 💝**

Connect NEMSU students and build meaningful relationships!
