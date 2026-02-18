<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add moderation columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('email');
            $table->boolean('is_verified_student')->default(false)->after('is_suspended');
            $table->boolean('is_banned')->default(false)->after('is_verified_student');
            $table->timestamp('suspended_at')->nullable()->after('is_banned');
            $table->string('suspension_reason')->nullable()->after('suspended_at');
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete()->after('suspension_reason');
        });

        // Content reports table
        Schema::create('content_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reportable_type'); // post, user, message
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason'); // spam, inappropriate, harassment, fake_profile, etc.
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, reviewed, resolved, dismissed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_suspended', 'is_verified_student', 'is_banned',
                'suspended_at', 'suspension_reason', 'suspended_by'
            ]);
        });

        Schema::dropIfExists('content_reports');
    }
};