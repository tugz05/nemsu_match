<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('post_id')->constrained('post_comments')->onDelete('cascade');
        });

        Schema::create('post_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('post_comment_id')->constrained('post_comments')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'post_comment_id']);
            $table->index('post_comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_comment_likes');
        Schema::table('post_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });
    }
};
