<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = ['user_id', 'matched_user_id', 'intent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matchedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }

    /**
     * Store a mutual match (one row per pair: smaller id in user_id, larger in matched_user_id).
     */
    public static function record(int $userId1, int $userId2, ?string $intent = null): self
    {
        $user_id = min($userId1, $userId2);
        $matched_user_id = max($userId1, $userId2);
        if ($user_id === $matched_user_id) {
            throw new \InvalidArgumentException('Cannot match user with themselves');
        }

        return self::firstOrCreate(
            ['user_id' => $user_id, 'matched_user_id' => $matched_user_id],
            ['intent' => $intent]
        );
    }

    /**
     * Check if two users are matched (can message each other directly).
     */
    public static function areMatched(int $userId1, int $userId2): bool
    {
        if ($userId1 === $userId2) {
            return false;
        }
        $user_id = min($userId1, $userId2);
        $matched_user_id = max($userId1, $userId2);

        return self::query()
            ->where('user_id', $user_id)
            ->where('matched_user_id', $matched_user_id)
            ->exists();
    }
}
