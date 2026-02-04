<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicProgram extends Model
{
    protected $fillable = [
        'name',
        'usage_count',
    ];

    /**
     * Increment usage count or create new program
     */
    public static function incrementOrCreate(string $name): void
    {
        $program = static::firstOrNew(['name' => $name]);
        $program->usage_count = ($program->usage_count ?? 0) + 1;
        $program->save();
    }

    /**
     * Get suggestions based on query
     */
    public static function getSuggestions(string $query, int $limit = 10): array
    {
        return static::where('name', 'LIKE', "%{$query}%")
            ->orderBy('usage_count', 'desc')
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->pluck('name')
            ->toArray();
    }
}
