<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // storage path, e.g. gallery/123/abc.jpg
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gallery_photos');
    }
};
