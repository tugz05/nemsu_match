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
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_plan', 32)->default('free')->after('terms_accepted_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_plan');
            $table->timestamp('boost_ends_at')->nullable()->after('subscription_ends_at');
            $table->unsignedTinyInteger('super_like_count_today')->default(0)->after('boost_ends_at');
            $table->date('super_like_reset_at')->nullable()->after('super_like_count_today');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'subscription_ends_at',
                'boost_ends_at',
                'super_like_count_today',
                'super_like_reset_at',
            ]);
        });
    }
};
