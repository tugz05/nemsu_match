<?php

/**
 * Hostinger cron script: refresh leaderboard cache so day/week/month rankings
 * are recomputed. Run this daily (e.g. at 00:05) so "Today" resets properly.
 *
 * Cron examples (adjust path to your project):
 *   Daily at 00:05:  5 0 * * * php /home/username/public_html/cron_leaderboard.php
 *   Or use Artisan:  5 0 * * * cd /home/username/public_html && php artisan leaderboard:refresh
 */

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\App\Http\Controllers\LeaderboardController::clearCache();

// Optional: log for debugging (if you have a log path)
// file_put_contents(__DIR__ . '/storage/logs/cron_leaderboard.log', date('c') . " OK\n", FILE_APPEND);
