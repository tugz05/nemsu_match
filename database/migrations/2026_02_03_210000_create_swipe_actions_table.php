<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swipe_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade');
            $table->string('intent', 20); // dating, friend, study_buddy, ignored
            $table->timestamps();

            $table->unique(['user_id', 'target_user_id']);
            $table->index(['target_user_id', 'intent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swipe_actions');
    }
};
