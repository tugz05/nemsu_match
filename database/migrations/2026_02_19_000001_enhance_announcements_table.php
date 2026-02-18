<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'target_group')) {
                $table->string('target_group')->default('all')->after('is_active');
            }
            if (! Schema::hasColumn('announcements', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('target_group');
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'target_group')) {
                $table->dropColumn('target_group');
            }
            if (Schema::hasColumn('announcements', 'scheduled_at')) {
                $table->dropColumn('scheduled_at');
            }
        });
    }
};