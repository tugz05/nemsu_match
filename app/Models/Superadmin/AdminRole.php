<?php

namespace App\Models\Superadmin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRole extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'permissions',
        'is_active',
        'assigned_at',
        'assigned_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the user associated with this role
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who assigned this role
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if user has specific role
     */
    public static function hasRole(int $userId, string $role): bool
    {
        return static::where('user_id', $userId)
            ->where('role', $role)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get user's role
     */
    public static function getUserRole(int $userId): ?string
    {
        $adminRole = static::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        return $adminRole?->role;
    }

    /**
     * Scope to get only active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by role type
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
