<?php

namespace App\Http\Controllers;

use App\Models\SwipeAction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    private const PERIOD_DAY = 'day';
    private const PERIOD_WEEK = 'week';
    private const PERIOD_MONTH = 'month';

    private const CACHE_TTL_DAY = 300;      // 5 min
    private const CACHE_TTL_WEEK = 900;     // 15 min
    private const CACHE_TTL_MONTH = 3600;   // 1 hour

    /**
     * Show the leaderboard page.
     */
    public function index(): Response
    {
        return Inertia::render('Leaderboard');
    }

    /**
     * GET /api/leaderboard?period=day|week|month
     * Returns ranked users by likes received in the period (display_name, profile_picture, points, rank only).
     */
    public function data(Request $request)
    {
        $period = $request->input('period', self::PERIOD_DAY);
        if (! in_array($period, [self::PERIOD_DAY, self::PERIOD_WEEK, self::PERIOD_MONTH], true)) {
            $period = self::PERIOD_DAY;
        }

        $cacheKey = "leaderboard:{$period}";
        $ttl = match ($period) {
            self::PERIOD_WEEK => self::CACHE_TTL_WEEK,
            self::PERIOD_MONTH => self::CACHE_TTL_MONTH,
            default => self::CACHE_TTL_DAY,
        };

        $list = Cache::remember($cacheKey, $ttl, function () use ($period): array {
            return $this->computeLeaderboard($period);
        });

        return response()->json(['data' => $list, 'period' => $period]);
    }

    /**
     * Compute leaderboard for the given period.
     * Includes all users registered on the platform (excludes only disabled and banned accounts).
     */
    private function computeLeaderboard(string $period): array
    {
        $since = match ($period) {
            self::PERIOD_WEEK => Carbon::now()->subDays(7)->startOfDay(),
            self::PERIOD_MONTH => Carbon::now()->subDays(30)->startOfDay(),
            default => Carbon::today()->startOfDay(),
        };

        $likeIntents = [
            SwipeAction::INTENT_DATING,
            SwipeAction::INTENT_FRIEND,
            SwipeAction::INTENT_STUDY_BUDDY,
        ];

        $ranked = DB::table('swipe_actions')
            ->whereIn('intent', $likeIntents)
            ->where('created_at', '>=', $since)
            ->whereNotNull('target_user_id')
            ->selectRaw('target_user_id as user_id, count(*) as points')
            ->groupBy('target_user_id')
            ->orderByDesc('points')
            ->limit(10)
            ->get();

        $userIds = $ranked->pluck('user_id')->unique()->values()->all();
        if ($userIds === []) {
            return [];
        }

        // All registered users eligible for leaderboard: not disabled, not banned
        $users = User::query()
            ->whereIn('id', $userIds)
            ->where(function ($q) {
                $q->where('is_disabled', false)->orWhereNull('is_disabled');
            })
            ->whereNull('banned_at')
            ->get(['id', 'display_name', 'profile_picture'])
            ->keyBy('id');

        $list = [];
        $rank = 1;
        foreach ($ranked as $row) {
            $user = $users->get($row->user_id);
            if (! $user) {
                continue;
            }
            $list[] = [
                'rank' => $rank,
                'display_name' => $user->display_name ?? 'Unknown',
                'profile_picture' => $user->profile_picture,
                'points' => (int) $row->points,
            ];
            $rank++;
        }

        return $list;
    }

    /**
     * Clear leaderboard cache (for cron / artisan command).
     */
    public static function clearCache(): void
    {
        Cache::forget('leaderboard:day');
        Cache::forget('leaderboard:week');
        Cache::forget('leaderboard:month');
    }
}
