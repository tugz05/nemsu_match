<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'is_disabled')) {
                $table->boolean('is_disabled')->default(false)->after('is_superadmin');
            }
            if (! Schema::hasColumn('users', 'disabled_reason')) {
                $table->string('disabled_reason', 500)->nullable()->after('is_disabled');
            }
            if (! Schema::hasColumn('users', 'disabled_at')) {
                $table->timestamp('disabled_at')->nullable()->after('disabled_reason');
            }
            if (! Schema::hasColumn('users', 'disabled_by')) {
                $table->foreignId('disabled_by')->nullable()->after('disabled_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::create('user_report_appeals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_report_id')->nullable()->constrained('user_reports')->nullOnDelete();
            $table->text('message');
            $table->string('status', 20)->default('pending'); // pending | reviewed | approved | rejected
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_report_appeals');

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'disabled_by')) {
                $table->dropConstrainedForeignId('disabled_by');
            }
            if (Schema::hasColumn('users', 'disabled_at')) {
                $table->dropColumn('disabled_at');
            }
            if (Schema::hasColumn('users', 'disabled_reason')) {
                $table->dropColumn('disabled_reason');
            }
            if (Schema::hasColumn('users', 'is_disabled')) {
                $table->dropColumn('is_disabled');
            }
        });
    }
};

