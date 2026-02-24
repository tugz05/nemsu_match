<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nearby_taps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // who tapped
            $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade'); // who was tapped
            $table->timestamps();

            $table->unique(['user_id', 'target_user_id']);
            $table->index('target_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nearby_taps');
    }
};
