<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReport extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'reason',
        'description',
        'status',
    ];

    /**
     * Get the user who reported
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reported post
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
