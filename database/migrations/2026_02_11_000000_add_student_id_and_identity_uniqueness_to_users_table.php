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
        // Add columns only if they don't already exist (to avoid duplicate-column errors)
        if (! Schema::hasColumn('users', 'student_id') || ! Schema::hasColumn('users', 'is_workspace_verified')) {
            Schema::table('users', function (Blueprint $table): void {
                // Student ID in format 00-00000 (stored as string, unique when present)
                if (! Schema::hasColumn('users', 'student_id')) {
                    $table->string('student_id', 8)->nullable()->unique()->after('nemsu_id');
                }

                // Whether this account used NEMSU Google Workspace for sign-in
                if (! Schema::hasColumn('users', 'is_workspace_verified')) {
                    $table->boolean('is_workspace_verified')->default(false)->after('student_id');
                }
            });
        }

        // Add unique indexes for identity fields.
        Schema::table('users', function (Blueprint $table): void {
            $table->unique('display_name');
            $table->unique('fullname');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['display_name']);
            $table->dropUnique(['fullname']);
            $table->dropColumn(['student_id', 'is_workspace_verified']);
        });
    }
};

