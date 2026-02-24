<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anonymous_chat_rooms', function (Blueprint $table) {
            $table->string('display_name', 80)->nullable()->after('user2_id');
            $table->boolean('user1_agreed_to_reveal')->default(false)->after('display_name');
            $table->boolean('user2_agreed_to_reveal')->default(false)->after('user1_agreed_to_reveal');
        });
    }

    public function down(): void
    {
        Schema::table('anonymous_chat_rooms', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'user1_agreed_to_reveal', 'user2_agreed_to_reveal']);
        });
    }
};
