<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            array_merge(User::factory()->definition(), ['email' => 'test@example.com', 'name' => 'Test User'])
        );

        $this->call([
            AdminUserSeeder::class,
            UsersSeeder::class,
            LeaderboardSeeder::class,
        ]);
    }
}
