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
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('fullname')->nullable()->after('display_name');
            $table->string('campus')->nullable()->after('fullname');
            $table->string('academic_program')->nullable()->after('campus');
            $table->string('year_level')->nullable()->after('academic_program');
            $table->string('profile_picture')->nullable()->after('year_level');
            $table->text('courses')->nullable()->after('profile_picture');
            $table->text('research_interests')->nullable()->after('courses');
            $table->text('extracurricular_activities')->nullable()->after('research_interests');
            $table->text('academic_goals')->nullable()->after('extracurricular_activities');
            $table->text('bio')->nullable()->after('academic_goals');
            $table->date('date_of_birth')->nullable()->after('bio');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->text('interests')->nullable()->after('gender');
            $table->boolean('profile_completed')->default(false)->after('interests');
            $table->string('nemsu_id')->nullable()->unique()->after('profile_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'fullname',
                'campus',
                'academic_program',
                'year_level',
                'profile_picture',
                'courses',
                'research_interests',
                'extracurricular_activities',
                'academic_goals',
                'bio',
                'date_of_birth',
                'gender',
                'interests',
                'profile_completed',
                'nemsu_id',
            ]);
        });
    }
};
