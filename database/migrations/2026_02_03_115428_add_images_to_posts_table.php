<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('images')->nullable()->after('image');
        });

        // Migrate existing single image to images array
        \DB::table('posts')->whereNotNull('image')->get()->each(function ($post) {
            \DB::table('posts')->where('id', $post->id)->update([
                'images' => json_encode([$post->image]),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
