<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwipeAction extends Model
{
    public const INTENT_IGNORED = 'ignored';

    public const INTENT_FRIEND = 'friend';

    public const INTENT_STUDY_BUDDY = 'study_buddy';

    public const INTENT_DATING = 'dating';

    protected $fillable = ['user_id', 'target_user_id', 'intent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public static function isLikeIntent(string $intent): bool
    {
        return in_array($intent, [self::INTENT_DATING, self::INTENT_FRIEND, self::INTENT_STUDY_BUDDY], true);
    }
}
