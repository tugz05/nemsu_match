<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('relationship_status', 50)->nullable()->after('gender');
            $table->string('looking_for', 50)->nullable()->after('relationship_status');
            $table->unsignedTinyInteger('preferred_age_min')->nullable()->after('looking_for');
            $table->unsignedTinyInteger('preferred_age_max')->nullable()->after('preferred_age_min');
            $table->text('preferred_campuses')->nullable()->after('preferred_age_max'); // JSON array of campus names
            $table->text('ideal_match_qualities')->nullable()->after('preferred_campuses'); // JSON array of strings
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'relationship_status',
                'looking_for',
                'preferred_age_min',
                'preferred_age_max',
                'preferred_campuses',
                'ideal_match_qualities',
            ]);
        });
    }
};
