<?php

namespace Database\Seeders;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Seeder;

class FeedbackDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@nemsu.edu.ph')->first() ?: User::first();

        if (! $user) {
            return;
        }

        $entries = [
            [
                'category' => 'Bug Report',
                'message' => 'Demo: Some buttons are hard to tap on mobile.',
            ],
            [
                'category' => 'Feature Request',
                'message' => 'Demo: Please add dark mode for late night studying.',
            ],
            [
                'category' => 'General Feedback',
                'message' => 'Demo: Overall the app feels smooth and easy to use.',
            ],
        ];

        foreach ($entries as $data) {
            Feedback::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'message' => $data['message'],
                ],
                [
                    'category' => $data['category'],
                    'is_read' => false,
                ]
            );
        }
    }
}

