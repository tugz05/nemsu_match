<?php

namespace App\Models\Editor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EditorAuditLog extends Model
{
    protected $fillable = [
        'editor_id',
        'action',
        'target_type',
        'target_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public static function record(
        string $action,
        string $targetType,
        ?int $targetId = null,
        array $oldValues = [],
        array $newValues = []
    ): self {
        return self::create([
            'editor_id'   => auth()->id(),
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'old_values'  => $oldValues ?: null,
            'new_values'  => $newValues ?: null,
            'ip_address'  => request()->ip(),
        ]);
    }
}