<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editor_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('editor_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // created, updated, deleted, suspended, verified, banned
            $table->string('target_type'); // announcement, user, post
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_audit_logs');
    }
};