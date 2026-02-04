<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'from_user_id',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Create a notification unless recipient is the same as actor.
     */
    public static function notify(int $recipientUserId, string $type, int $fromUserId, string $notifiableType, ?int $notifiableId = null, array $data = []): ?self
    {
        if ($recipientUserId === $fromUserId) {
            return null;
        }

        return self::create([
            'user_id' => $recipientUserId,
            'type' => $type,
            'from_user_id' => $fromUserId,
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiableId,
            'data' => $data ?: null,
        ]);
    }
}
