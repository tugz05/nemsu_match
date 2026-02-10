<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reported_user_id')->constrained('users')->onDelete('cascade');
            $table->string('reason', 50);
            $table->string('description', 500)->nullable();
            $table->string('status', 20)->default('pending'); // pending | reviewed | dismissed
            $table->timestamps();

            $table->index(['reported_user_id', 'status']);
            $table->index(['reporter_id', 'reported_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reports');
    }
};

