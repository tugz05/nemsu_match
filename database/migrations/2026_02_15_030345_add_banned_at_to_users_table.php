<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        if (!Schema::hasColumn('users', 'banned_at')) {
            $table->timestamp('banned_at')->nullable();
        }
        if (!Schema::hasColumn('users', 'ban_reason')) {
            $table->text('ban_reason')->nullable();
        }
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['banned_at', 'ban_reason']);
    });
}
};
