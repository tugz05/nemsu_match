<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name',
        'usage_count',
    ];

    /**
     * Increment usage count or create new course
     */
    public static function incrementOrCreate(string $name): void
    {
        $course = static::firstOrNew(['name' => $name]);
        $course->usage_count = ($course->usage_count ?? 0) + 1;
        $course->save();
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
