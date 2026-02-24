<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NearbyTap extends Model
{
    protected $fillable = ['user_id', 'target_user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Check if both users have tapped each other (mutual tap).
     */
    public static function areMutual(int $userIdA, int $userIdB): bool
    {
        if ($userIdA === $userIdB) {
            return false;
        }
        $a = self::where('user_id', $userIdA)->where('target_user_id', $userIdB)->exists();
        $b = self::where('user_id', $userIdB)->where('target_user_id', $userIdA)->exists();

        return $a && $b;
    }
}
