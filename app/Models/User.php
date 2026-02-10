<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'display_name',
        'fullname',
        'campus',
        'academic_program',
        'year_level',
        'profile_picture',
        'courses',
        'research_interests',
        'extracurricular_activities',
        'academic_goals',
        'bio',
        'date_of_birth',
        'gender',
        'interests',
        'relationship_status',
        'looking_for',
        'preferred_gender',
        'preferred_age_min',
        'preferred_age_max',
        'preferred_campuses',
        'ideal_match_qualities',
        'preferred_courses',
        'profile_completed',
        'nemsu_id',
        'is_admin',
        'is_superadmin',
        'last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'date_of_birth' => 'date',
            'profile_completed' => 'boolean',
            'last_seen_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_superadmin' => 'boolean',
            'preferred_age_min' => 'integer',
            'preferred_age_max' => 'integer',
            'courses' => 'array',
            'research_interests' => 'array',
            'extracurricular_activities' => 'array',
            'academic_goals' => 'array',
            'interests' => 'array',
            'preferred_campuses' => 'array',
            'ideal_match_qualities' => 'array',
            'preferred_courses' => 'array',
        ];
    }

    /**
     * Consider user "online" if last_seen_at is within this many minutes.
     */
    public static function onlineWithinMinutes(): int
    {
        return 5;
    }

    /**
     * Whether this user is considered online (last seen within threshold).
     */
    public function isOnline(): bool
    {
        if (! $this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->diffInMinutes(now()) <= static::onlineWithinMinutes();
    }

    /**
     * Users that this user follows (following list)
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * Users that follow this user (followers list)
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    /**
     * Check if this user follows another user
     */
    public function isFollowing(User $user): bool
    {
        if ($this->id === $user->id) {
            return false;
        }

        return $this->following()->where('following_id', $user->id)->exists();
    }

    /**
     * Follow a user (no-op if already following or self)
     */
    public function follow(User $user): bool
    {
        if ($this->id === $user->id) {
            return false;
        }

        $this->following()->syncWithoutDetaching([$user->id]);

        return true;
    }

    /**
     * Unfollow a user
     */
    public function unfollow(User $user): bool
    {
        $this->following()->detach($user->id);

        return true;
    }

    /**
     * Users this user has blocked
     */
    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocker_id', 'blocked_id')
            ->withTimestamps();
    }

    /**
     * Users who have blocked this user
     */
    public function blockedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'blocks', 'blocked_id', 'blocker_id')
            ->withTimestamps();
    }

    public function isBlocking(User $user): bool
    {
        return $this->blockedUsers()->where('blocked_id', $user->id)->exists();
    }

    public function isBlockedBy(User $user): bool
    {
        return $this->blockedBy()->where('blocker_id', $user->id)->exists();
    }

    public function block(User $user): bool
    {
        if ($this->id === $user->id) {
            return false;
        }
        $this->blockedUsers()->syncWithoutDetaching([$user->id]);
        return true;
    }

    public function unblock(User $user): bool
    {
        $this->blockedUsers()->detach($user->id);
        return true;
    }

    /** Message requests sent by this user */
    public function sentMessageRequests(): HasMany
    {
        return $this->hasMany(MessageRequest::class, 'from_user_id');
    }

    /** Message requests received by this user */
    public function receivedMessageRequests(): HasMany
    {
        return $this->hasMany(MessageRequest::class, 'to_user_id');
    }

    /** Swipe actions this user has taken (dating, friend, study_buddy, ignored) */
    public function swipeActions(): HasMany
    {
        return $this->hasMany(SwipeAction::class);
    }

    /** Swipe actions where this user was the target */
    public function swipeActionsReceived(): HasMany
    {
        return $this->hasMany(SwipeAction::class, 'target_user_id');
    }

    /** Gallery photos uploaded by the user */
    public function galleryPhotos(): HasMany
    {
        return $this->hasMany(UserGalleryPhoto::class);
    }

    /** Admin role assigned to this user */
    public function adminRole(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Superadmin\AdminRole::class);
    }
}
