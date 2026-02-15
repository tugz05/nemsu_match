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
        Schema::table('announcements', function (Blueprint $table): void {
            if (! Schema::hasColumn('announcements', 'created_by')) {
                $table->foreignId('created_by')->after('id')->constrained('users')->onDelete('cascade');
            }

            if (! Schema::hasColumn('announcements', 'title')) {
                $table->string('title')->after('created_by');
            }

            if (! Schema::hasColumn('announcements', 'body')) {
                $table->text('body')->after('title');
            }

            if (! Schema::hasColumn('announcements', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('body');
            }

            if (! Schema::hasColumn('announcements', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('is_pinned');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            if (Schema::hasColumn('announcements', 'published_at')) {
                $table->dropColumn('published_at');
            }

            if (Schema::hasColumn('announcements', 'is_pinned')) {
                $table->dropColumn('is_pinned');
            }

            if (Schema::hasColumn('announcements', 'body')) {
                $table->dropColumn('body');
            }

            if (Schema::hasColumn('announcements', 'title')) {
                $table->dropColumn('title');
            }

            if (Schema::hasColumn('announcements', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};

