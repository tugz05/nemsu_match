<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->string('status', 20)->default('pending'); // pending, accepted, declined
            $table->timestamps();

            $table->index(['to_user_id', 'status']);
            $table->index('from_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_requests');
    }
};
