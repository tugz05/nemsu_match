<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Delete previously seeded users (nemsu_id like 'SEED-%'), then seed users
     * with all profile fields populated via UserFactory (basic info, academic,
     * interests, relationship_status, looking_for, preferred_gender, preferred_age_*,
     * preferred_campuses, ideal_match_qualities, preferred_courses, etc.).
     */
    public function run(): void
    {
        $deleted = User::where('nemsu_id', 'like', 'SEED-%')->delete();
        if ($deleted > 0) {
            $this->command->info("Deleted {$deleted} previously seeded users.");
        }

        $target = 500;
        $this->command->info("Creating {$target} users with all user table fields...");

        User::factory()->count($target)->create();

        $this->command->info('Done. Total users: '.User::count());
    }
}
