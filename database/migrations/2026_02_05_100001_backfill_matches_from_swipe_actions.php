<?php

use App\Models\Conversation;
use App\Models\UserMatch;
use App\Models\SwipeAction;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $likeIntents = [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY];

        // Get all (user_id, target_user_id) where user liked target with a like intent
        $likes = SwipeAction::query()
            ->whereIn('intent', $likeIntents)
            ->select('user_id', 'target_user_id', 'intent')
            ->get();

        $pairs = [];
        foreach ($likes as $like) {
            $a = (int) $like->user_id;
            $b = (int) $like->target_user_id;
            if ($a === $b) {
                continue;
            }
            $key = min($a, $b) . '_' . max($a, $b);
            if (isset($pairs[$key])) {
                continue;
            }
            // Check if the other user also liked this user (mutual)
            $theyLikedMe = SwipeAction::query()
                ->where('user_id', $b)
                ->where('target_user_id', $a)
                ->whereIn('intent', $likeIntents)
                ->exists();
            if ($theyLikedMe) {
                $pairs[$key] = ['user_id' => min($a, $b), 'matched_user_id' => max($a, $b), 'intent' => $like->intent];
            }
        }

        foreach ($pairs as $row) {
            UserMatch::firstOrCreate(
                ['user_id' => $row['user_id'], 'matched_user_id' => $row['matched_user_id']],
                ['intent' => $row['intent']]
            );
            Conversation::between($row['user_id'], $row['matched_user_id']);
        }
    }

    public function down(): void
    {
        // Optionally clear matches table; leave as no-op so rollback doesn't lose data
    }
};
