<?php

namespace App\Models\Superadmin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key with type casting
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("app_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = static::firstOrCreate(['key' => $key]);
        $setting->value = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        $setting->save();

        Cache::forget("app_setting_{$key}");
    }

    /**
     * Cast value based on type
     */
    private static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => $value === 'true' || $value === '1',
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get all settings grouped by category
     */
    public static function getAllGrouped(): array
    {
        $settings = static::all();
        $grouped = [];

        foreach ($settings as $setting) {
            $group = $setting->group ?? 'general';

            if (! isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            $grouped[$group][] = [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => static::castValue($setting->value, $setting->type),
                'raw_value' => $setting->value,
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        }

        return $grouped;
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("app_setting_{$key}");
        }
    }
}
