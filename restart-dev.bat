@echo off
echo.
echo ========================================
echo  Restarting NEMSU Match Dev Server
echo ========================================
echo.

cd /d %~dp0

echo [1/3] Clearing caches...
php artisan optimize:clear

echo.
echo [2/3] Removing old build...
if exist public\build\manifest.json (
    del public\build\manifest.json
    echo Manifest removed
)

echo.
echo [3/3] Starting dev server...
echo.
echo Press Ctrl+C to stop the server when needed
echo.

composer run dev
