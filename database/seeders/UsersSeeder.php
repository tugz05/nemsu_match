<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Delete previously seeded users (nemsu_id like 'SEED-%'), then seed 500 users
     * with full profile data. Profile pictures are set from API image URLs.
     */
    public function run(): void
    {
        $deleted = User::where('nemsu_id', 'like', 'SEED-%')->delete();
        if ($deleted > 0) {
            $this->command->info("Deleted {$deleted} previously seeded users.");
        }

        $target = 500;
        $this->command->info("Creating {$target} users (profile pictures from API)...");

        User::factory()->count($target)->create();

        $this->command->info('Done. Total users: '.User::count());
    }
}
