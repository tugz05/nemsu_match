<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anonymous_chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('users')->onDelete('cascade'); // smaller user id
            $table->foreignId('user2_id')->constrained('users')->onDelete('cascade'); // larger user id
            $table->timestamps();

            $table->unique(['user1_id', 'user2_id']);
            $table->index('user1_id');
            $table->index('user2_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymous_chat_rooms');
    }
};
