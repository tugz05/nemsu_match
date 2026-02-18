<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',       // your existing column (not 'body')
        'type',          // general, urgent, event
        'user_id',       // your existing column (not 'created_by')
        'is_active',
        'target_group',  // new column
        'scheduled_at',  // new column
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'scheduled_at' => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────────

    /** Only published (active + scheduled_at in the past or null) */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('scheduled_at')
                           ->orWhere('scheduled_at', '<=', now());
                     });
    }

    /** Scheduled but not yet live */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('is_active', true)
                     ->whereNotNull('scheduled_at')
                     ->where('scheduled_at', '>', now());
    }

    /** Inactive / draft */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    // ─── Computed Attributes ─────────────────────────────────────────────────────

    public function getStatusAttribute(): string
    {
        if (! $this->is_active) return 'draft';
        if ($this->scheduled_at && $this->scheduled_at->isFuture()) return 'scheduled';
        return 'published';
    }

    public function getTargetGroupLabelAttribute(): string
    {
        return match ($this->target_group) {
            'male'      => 'Male Users',
            'female'    => 'Female Users',
            'verified'  => 'Verified Students',
            'new_users' => 'New Users (< 7 days)',
            default     => 'All Users',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'urgent' => '🔴 Urgent',
            'event'  => '📅 Event',
            default  => '📢 General',
        };
    }
}