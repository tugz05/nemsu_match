<?php

namespace Database\Seeders;

use App\Http\Controllers\LeaderboardController;
use App\Models\SwipeAction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeaderboardSeeder extends Seeder
{
    /**
     * Seed swipe actions (likes) so the leaderboard has data for Today / Week / Month.
     * Picks a subset of users as "targets" and gives them likes from other users,
     * with created_at spread over the last 30 days so all period tabs show results.
     */
    public function run(): void
    {
        $users = User::query()
            ->where('profile_completed', true)
            ->where(function ($q) {
                $q->where('is_disabled', false)->orWhereNull('is_disabled');
            })
            ->orderBy('id')
            ->get(['id']);

        if ($users->count() < 2) {
            $this->command->warn('Need at least 2 users with completed profiles. Run UsersSeeder first.');
            return;
        }

        $likeIntents = [
            SwipeAction::INTENT_DATING,
            SwipeAction::INTENT_FRIEND,
            SwipeAction::INTENT_STUDY_BUDDY,
        ];

        // Targets that will appear on the leaderboard (e.g. first 50 users, or random)
        $targetIds = $users->pluck('id')->take(50)->values()->all();
        $swiperIds = $users->pluck('id')->values()->all();

        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $weekStart = $now->copy()->subDays(7)->startOfDay();
        $monthStart = $now->copy()->subDays(30)->startOfDay();

        $created = 0;
        $skipped = 0;

        foreach ($targetIds as $targetId) {
            // How many "likes" this user should receive (so they rank on leaderboard)
            $likeCount = random_int(3, 35);
            $possibleSwipers = array_values(array_filter($swiperIds, fn ($id) => $id !== $targetId));

            if (count($possibleSwipers) < $likeCount) {
                $likeCount = count($possibleSwipers);
            }

            shuffle($possibleSwipers);
            $pickedSwipers = array_slice($possibleSwipers, 0, $likeCount);

            foreach ($pickedSwipers as $swiperId) {
                $exists = SwipeAction::where('user_id', $swiperId)
                    ->where('target_user_id', $targetId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Spread created_at: ~40% today, ~30% last 7 days, ~30% last 30 days
                $r = random_int(1, 100);
                if ($r <= 40) {
                    $createdAt = Carbon::createFromTimestamp(
                        random_int($todayStart->timestamp, $now->timestamp)
                    );
                } elseif ($r <= 70) {
                    $createdAt = Carbon::createFromTimestamp(
                        random_int($weekStart->timestamp, $todayStart->timestamp - 1)
                    );
                } else {
                    $createdAt = Carbon::createFromTimestamp(
                        random_int($monthStart->timestamp, $weekStart->timestamp - 1)
                    );
                }

                SwipeAction::create([
                    'user_id' => $swiperId,
                    'target_user_id' => $targetId,
                    'intent' => $likeIntents[array_rand($likeIntents)],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $created++;
            }
        }

        LeaderboardController::clearCache();

        $this->command->info("Leaderboard seeder: {$created} like swipe actions created, {$skipped} skipped (already exist). Cache cleared so leaderboard will show fresh data.");
    }
}
