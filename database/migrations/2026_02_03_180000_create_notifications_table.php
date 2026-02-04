<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // recipient
            $table->string('type'); // comment, like, follow, comment_like
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->string('notifiable_type', 50); // post, comment, user
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->json('data')->nullable(); // e.g. {"comment_id":1,"excerpt":"..."}
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
