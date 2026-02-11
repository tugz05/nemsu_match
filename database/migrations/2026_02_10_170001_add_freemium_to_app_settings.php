<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = fn (string $key) => DB::table('app_settings')->where('key', $key)->exists();

        if (!$exists('freemium_enabled')) {
            DB::table('app_settings')->insert([
                'key' => 'freemium_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'freemium',
                'description' => 'Enable NEMSU Match Plus (subscription). When ON, free users get limited likes and Plus features are shown.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$exists('plus_monthly_price')) {
            DB::table('app_settings')->insert([
                'key' => 'plus_monthly_price',
                'value' => '49',
                'type' => 'integer',
                'group' => 'freemium',
                'description' => 'Monthly price for NEMSU Match Plus (₱).',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$exists('free_daily_likes_limit')) {
            DB::table('app_settings')->insert([
                'key' => 'free_daily_likes_limit',
                'value' => '20',
                'type' => 'integer',
                'group' => 'freemium',
                'description' => 'Daily likes/swipes limit for free users when freemium is ON.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$exists('plus_daily_likes_limit')) {
            DB::table('app_settings')->insert([
                'key' => 'plus_daily_likes_limit',
                'value' => '999',
                'type' => 'integer',
                'group' => 'freemium',
                'description' => 'Daily likes limit for Plus users (high number = effectively unlimited).',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('app_settings')->whereIn('key', [
            'freemium_enabled',
            'plus_monthly_price',
            'free_daily_likes_limit',
            'plus_daily_likes_limit',
        ])->delete();
    }
};
