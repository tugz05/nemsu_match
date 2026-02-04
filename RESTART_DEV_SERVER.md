# Fix: Unable to Locate ProfileSetup.vue ⚠️

## Problem
```
Illuminate\Foundation\ViteException
Unable to locate file in Vite manifest: resources/js/pages/profile/ProfileSetup.vue
```

## Why This Happened
The `ProfileSetup.vue` file was created after Vite started, so it's not in Vite's build manifest yet.

## ✅ Quick Fix (30 seconds)

### Step 1: Stop the Dev Server
In your terminal where `composer run dev` is running:

**Press:** `Ctrl+C`

### Step 2: Restart the Dev Server
Run:
```bash
composer run dev
```

Wait for it to show:
```
LARAVEL v12.49.0  plugin v2.1.0
➜  APP_URL: http://localhost:8000
```

### Step 3: Test Again
Visit: http://localhost:8000

Click "Continue with Google" - should work now!

## Alternative: Manual Restart

If you closed the terminal:

```bash
cd c:\laragon\www\dating-app
composer run dev
```

## What This Does
- Rebuilds Vite's manifest.json
- Includes all Vue components (including ProfileSetup.vue)
- Updates the asset compilation

## ✅ Verification

After restarting, the manifest should include ProfileSetup:

```bash
# Check if it's now in the manifest
grep -i "profilesetup" public/build/manifest.json
```

Should show output (not "not in manifest")

## Other Common Vite Issues

### If Error Persists:

**Clear everything and rebuild:**
```bash
# Stop dev server (Ctrl+C)
rm -rf public/build
php artisan optimize:clear
composer run dev
```

### If Port is Already in Use:
```
Error: Port 5173 is already in use
```

**Solution:**
```bash
# Kill the process using the port
npx kill-port 5173
composer run dev
```

## 🎯 Summary

**The Fix:**
1. Ctrl+C (stop server)
2. `composer run dev` (restart)
3. Done!

**Time:** 30 seconds  
**Difficulty:** Easy  

---

**Why does this happen?**  
Vite creates a manifest of all files when it starts. If you create new Vue files while it's running, they won't be in the manifest until you restart Vite.

**Permanent Solution:**  
Always restart the dev server after creating new Vue components.
