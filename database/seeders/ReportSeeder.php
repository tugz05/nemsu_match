<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;
use App\Models\User;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have at least one user to attach reports to
        $user = User::first();

        if (!$user) {
            // If no users exist, create one
            $user = User::factory()->create([
                'fullname' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Create 5 fake reports
        Report::create([
            'user_id' => $user->id,          // Who submitted it
            'reported_user_id' => $user->id, // Who is reported (using same user for test)
            'type' => 'Harassment',
            'description' => 'This user is sending mean messages.',
            'status' => 'Pending'
        ]);

        Report::create([
            'user_id' => $user->id,
            'reported_user_id' => $user->id,
            'type' => 'Spam',
            'description' => 'Posting links to other sites.',
            'status' => 'Resolved'
        ]);

        Report::create([
            'user_id' => $user->id,
            'reported_user_id' => $user->id,
            'type' => 'Inappropriate Content',
            'description' => 'Profile picture is offensive.',
            'status' => 'Pending'
        ]);
    }
}