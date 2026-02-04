<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['user1_id', 'user2_id'];

    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    /** Get the other participant in the conversation from the current user's perspective */
    public function otherUser(int $currentUserId): User
    {
        return (int) $this->user1_id === (int) $currentUserId ? $this->user2 : $this->user1;
    }

    /** Find or create a conversation between two users (user1_id < user2_id for uniqueness) */
    public static function between(int $userIdA, int $userIdB): self
    {
        $user1 = min($userIdA, $userIdB);
        $user2 = max($userIdA, $userIdB);

        return self::firstOrCreate(
            ['user1_id' => $user1, 'user2_id' => $user2],
            ['user1_id' => $user1, 'user2_id' => $user2]
        );
    }
}
