<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostComment extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'content',
        'likes_count',
    ];

    protected $with = ['user'];

    protected $appends = ['time_ago', 'is_liked_by_user'];

    /**
     * Get the user who created the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post this comment belongs to
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the parent comment (for replies)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    /**
     * Get direct replies to this comment
     */
    public function replies(): HasMany
    {
        return $this->hasMany(PostComment::class, 'parent_id')->latest();
    }

    /**
     * Users who liked this comment
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_comment_likes', 'post_comment_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Check if current user liked this comment
     */
    public function getIsLikedByUserAttribute(): bool
    {
        return $this->likes()
            ->where('user_id', auth()->id())
            ->exists();
    }

    /**
     * Toggle like for a comment
     */
    public function toggleLike(int $userId): bool
    {
        $attached = $this->likes()->toggle($userId);

        $this->likes_count = $this->likes()->count();
        $this->save();

        return count($attached['attached']) > 0;
    }

    /**
     * Get human readable time ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
