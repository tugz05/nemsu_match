<?php

namespace App\Console\Commands;

use App\Http\Controllers\LeaderboardController;
use Illuminate\Console\Command;

class RefreshLeaderboardCache extends Command
{
    protected $signature = 'leaderboard:refresh';

    protected $description = 'Clear leaderboard cache so day/week/month data is recomputed on next request. Run daily via cron for fresh daily reset.';

    public function handle(): int
    {
        LeaderboardController::clearCache();
        $this->info('Leaderboard cache cleared. Next page load will recompute day/week/month rankings.');

        return self::SUCCESS;
    }
}
