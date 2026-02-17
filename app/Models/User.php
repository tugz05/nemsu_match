<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Superadmin\AppSetting;
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
        'terms_accepted_at',
        'subscription_plan',
        'subscription_ends_at',
        'boost_ends_at',
        'super_like_count_today',
        'super_like_reset_at',
        'nemsu_id',
        'student_id',
        'is_workspace_verified',
        'is_admin',
        'is_superadmin',
        'is_disabled',
        'disabled_reason',
        'disabled_at',
        'disabled_by',
        'last_seen_at',
        'latitude',
        'longitude',
        'location_updated_at',
        'nearby_match_enabled',
        'nearby_match_radius_m',
        // --- NEW FIELDS FOR ADMIN BANNING ---
        'banned_at',
        'ban_reason',
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
            'terms_accepted_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'boost_ends_at' => 'datetime',
            'super_like_reset_at' => 'date',
            'last_seen_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_superadmin' => 'boolean',
            'is_disabled' => 'boolean',
            'disabled_at' => 'datetime',
            'preferred_age_min' => 'integer',
            'preferred_age_max' => 'integer',
            'nearby_match_enabled' => 'boolean',
            'location_updated_at' => 'datetime',
            'courses' => 'array',
            'research_interests' => 'array',
            'extracurricular_activities' => 'array',
            'academic_goals' => 'array',
            'interests' => 'array',
            'preferred_campuses' => 'array',
            'ideal_match_qualities' => 'array',
            'preferred_courses' => 'array',
            // --- NEW CAST ---
            'banned_at' => 'datetime',
        ];
    }

    /**
     * Check if the user is currently banned by an admin.
     */
    public function isBanned(): bool
    {
        return $this->banned_at !== null;
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

    /** Whether freemium / NEMSU Match Plus is enabled (Superadmin toggle). */
    public static function freemiumEnabled(): bool
    {
        return (bool) AppSetting::get('freemium_enabled', false);
    }

    /** Whether this user has an active Plus subscription. */
    public function isPlus(): bool
    {
        if ($this->subscription_plan !== 'plus') {
            return false;
        }
        if ($this->subscription_ends_at === null) {
            return true;
        }
        return $this->subscription_ends_at->isFuture();
    }

    /** Daily likes limit for this user (when freemium is on). */
    public function getDailyLikesLimit(): int
    {
        if (! static::freemiumEnabled()) {
            return (int) AppSetting::get('plus_daily_likes_limit', 999);
        }
        return $this->isPlus()
            ? (int) AppSetting::get('plus_daily_likes_limit', 999)
            : (int) AppSetting::get('free_daily_likes_limit', 20);
    }

    /** Number of like-intent swipes (dating, friend, study_buddy) performed today. */
    public function getTodayLikesCount(): int
    {
        $today = now()->startOfDay();
        return $this->swipeActions()
            ->whereIn('intent', [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY])
            ->where('updated_at', '>=', $today)
            ->count();
    }

    /** Remaining likes available today (when freemium on). */
    public function getRemainingLikesToday(): int
    {
        $limit = $this->getDailyLikesLimit();
        $used = $this->getTodayLikesCount();
        return max(0, $limit - $used);
    }

    /** Whether this user can use a Super Like today (Plus only, 1/day when freemium on). */
    public function canSuperLikeToday(): bool
    {
        if (! static::freemiumEnabled() || ! $this->isPlus()) {
            return false;
        }
        $today = now()->toDateString();
        if ($this->super_like_reset_at !== $today) {
            return true;
        }
        return $this->super_like_count_today < 1;
    }

    /** Record use of today's Super Like (call after applying a super like). */
    public function useSuperLike(): void
    {
        $today = now()->toDateString();
        if ($this->super_like_reset_at !== $today) {
            $this->update([
                'super_like_count_today' => 1,
                'super_like_reset_at' => $today,
            ]);
        } else {
            $this->increment('super_like_count_today');
        }
    }

    /** Whether this user's profile is currently boosted. */
    public function isBoosted(): bool
    {
        return $this->boost_ends_at !== null && $this->boost_ends_at->isFuture();
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

    /** Reports where this user is the reported account */
    public function reportsAgainst(): HasMany
    {
        return $this->hasMany(UserReport::class, 'reported_user_id');
    }

    /** Appeals submitted by this user after disable */
    public function reportAppeals(): HasMany
    {
        return $this->hasMany(\App\Models\UserReportAppeal::class);
    }

    /** Admin role assigned to this user */
    public function adminRole(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Superadmin\AdminRole::class);
    }

    /**
     * Whether this user is staff (superadmin, admin, or editor) and should bypass
     * maintenance mode and pre-registration mode restrictions.
     */
    public function isStaff(): bool
    {
        if ($this->is_superadmin || $this->is_admin) {
            return true;
        }

        return $this->adminRole()->where('is_active', true)->exists();
    }
}