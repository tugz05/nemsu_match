<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('group')->default('general'); // general, users, features, etc.
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('app_settings')->insert([
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Enable maintenance mode to prevent users from accessing the app',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pre_registration_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'users',
                'description' => 'Enable pre-registration mode to allow users to sign up before official launch',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'allow_registration',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'users',
                'description' => 'Allow new users to register',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_daily_swipes',
                'value' => '50',
                'type' => 'integer',
                'group' => 'features',
                'description' => 'Maximum number of swipes per day per user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_daily_matches',
                'value' => '20',
                'type' => 'integer',
                'group' => 'features',
                'description' => 'Maximum number of matches per day per user',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_chat',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable chat functionality',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_video_call',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable video call functionality',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
