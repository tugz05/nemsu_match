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

        if (!$exists('app_logo')) {
            DB::table('app_settings')->insert([
                'key' => 'app_logo',
                'value' => '',
                'type' => 'string',
                'group' => 'branding',
                'description' => 'App logo image (PNG or SVG). Shown in app branding areas.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!$exists('header_icon')) {
            DB::table('app_settings')->insert([
                'key' => 'header_icon',
                'value' => '',
                'type' => 'string',
                'group' => 'branding',
                'description' => 'Header/favicon icon (PNG or SVG). Shown in header and browser tab.',
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
        DB::table('app_settings')->whereIn('key', ['app_logo', 'header_icon'])->delete();
    }
};
