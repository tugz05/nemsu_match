<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BannedUserDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'banned.demo@nemsu.edu.ph'],
            array_merge(User::factory()->definition(), [
                'email' => 'banned.demo@nemsu.edu.ph',
                'display_name' => 'Banned Demo',
                'fullname' => 'Banned Demo User',
                'nemsu_id' => 'SEED-BANNED-'.Str::random(5),
                'profile_completed' => true,
            ])
        );

        $user->forceFill([
            'banned_at' => now(),
            'ban_reason' => 'Demo ban for admin UI testing',
        ])->save();
    }
}

