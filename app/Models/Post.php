<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'image',
        'images',
        'likes_count',
        'comments_count',
        'reposts_count',
    ];

    protected $with = ['user'];

    protected $appends = ['is_liked_by_user', 'time_ago', 'images_list'];

    protected function casts(): array
    {
        return [
            'images' => 'array',
        ];
    }

    /**
     * Get list of image URLs (from images array or legacy single image)
     */
    public function getImagesListAttribute(): array
    {
        $images = $this->images;
        if (is_array($images) && count($images) > 0) {
            return $images;
        }
        if ($this->image) {
            return [$this->image];
        }

        return [];
    }

    /**
     * Get the user who created the post
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all comments for the post
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * Get users who liked this post
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_likes')
            ->withTimestamps();
    }

    /**
     * Check if current user liked this post
     */
    public function getIsLikedByUserAttribute(): bool
    {
        return $this->likes()
            ->where('user_id', auth()->id())
            ->exists();
    }

    /**
     * Get human readable time ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Toggle like for a post
     */
    public function toggleLike(int $userId): bool
    {
        $liked = $this->likes()->toggle($userId);

        // Update likes count
        $this->likes_count = $this->likes()->count();
        $this->save();

        return count($liked['attached']) > 0;
    }
}
