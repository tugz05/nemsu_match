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
        // Academic Programs table for suggestions
        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('usage_count')->default(1);
            $table->timestamps();

            $table->index('name');
            $table->index('usage_count');
        });

        // Courses table for suggestions
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('usage_count')->default(1);
            $table->timestamps();

            $table->index('name');
            $table->index('usage_count');
        });

        // Interests table for suggestions (covers research interests, hobbies, etc.)
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->nullable(); // research, extracurricular, hobby, academic_goal
            $table->integer('usage_count')->default(1);
            $table->timestamps();

            $table->index('name');
            $table->index('category');
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_programs');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('interests');
    }
};
