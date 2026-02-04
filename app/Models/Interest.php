<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interest extends Model
{
    protected $fillable = [
        'name',
        'category',
        'usage_count',
    ];

    /**
     * Increment usage count or create new interest
     */
    public static function incrementOrCreate(string $name, ?string $category = null): void
    {
        $interest = static::firstOrNew(['name' => $name]);
        $interest->category = $category ?? $interest->category;
        $interest->usage_count = ($interest->usage_count ?? 0) + 1;
        $interest->save();
    }

    /**
     * Get suggestions based on query and optional category
     */
    public static function getSuggestions(string $query, ?string $category = null, int $limit = 10): array
    {
        $queryBuilder = static::where('name', 'LIKE', "%{$query}%");

        if ($category) {
            $queryBuilder->where('category', $category);
        }

        return $queryBuilder
            ->orderBy('usage_count', 'desc')
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->pluck('name')
            ->toArray();
    }
}
