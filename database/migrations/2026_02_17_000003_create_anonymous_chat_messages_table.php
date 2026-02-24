<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anonymous_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anonymous_chat_room_id')->constrained('anonymous_chat_rooms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // sender (identity not shown to other)
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['anonymous_chat_room_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymous_chat_messages');
    }
};
