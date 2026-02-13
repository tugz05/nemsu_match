<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('last_seen_at');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->timestamp('location_updated_at')->nullable()->after('longitude');
            $table->boolean('nearby_match_enabled')->default(false)->after('location_updated_at');
            $table->unsignedSmallInteger('nearby_match_radius_m')->default(1000)->after('nearby_match_enabled'); // 500–2000
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'location_updated_at',
                'nearby_match_enabled',
                'nearby_match_radius_m',
            ]);
        });
    }
};
