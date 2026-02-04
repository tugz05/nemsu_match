<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users (you'll need at least one user in database)
        $users = User::where('profile_completed', true)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users with completed profiles found. Please create users first.');
            return;
        }

        $samplePosts = [
            [
                'content' => "Just aced my Data Structures exam! 🎉 The hard work really paid off. Who else is crushing it this semester?",
                'likes_count' => 15,
                'comments_count' => 3,
            ],
            [
                'content' => "Looking for study partners for Web Development finals next week. Anyone interested in forming a study group? ☕📚",
                'likes_count' => 8,
                'comments_count' => 5,
            ],
            [
                'content' => "The sunset at NEMSU Tandag campus today was absolutely beautiful! Sometimes we need to take a break and appreciate the little things. 🌅",
                'likes_count' => 23,
                'comments_count' => 7,
            ],
            [
                'content' => "Coffee break at the library. Finals week is tough but we got this NEMSU! Stay strong everyone 💪☕",
                'likes_count' => 12,
                'comments_count' => 4,
            ],
            [
                'content' => "Finished my research paper on Climate Change! Big shoutout to my groupmates for the collaboration. Teamwork makes the dream work! 🌍📝",
                'likes_count' => 19,
                'comments_count' => 6,
            ],
            [
                'content' => "Excited for the upcoming NEMSU basketball tournament! Who's playing? Let's show our campus spirit! 🏀🔥",
                'likes_count' => 31,
                'comments_count' => 12,
            ],
            [
                'content' => "Just joined the Computer Science club! Looking forward to learning and growing with fellow tech enthusiasts. Anyone else in CS? 💻",
                'likes_count' => 14,
                'comments_count' => 8,
            ],
            [
                'content' => "Grateful for the amazing professors at NEMSU. Their dedication to teaching really inspires us to do better. Thank you, teachers! 🙏",
                'likes_count' => 45,
                'comments_count' => 15,
            ],
        ];

        foreach ($samplePosts as $postData) {
            $randomUser = $users->random();

            Post::create([
                'user_id' => $randomUser->id,
                'content' => $postData['content'],
                'likes_count' => $postData['likes_count'],
                'comments_count' => $postData['comments_count'],
                'reposts_count' => rand(0, 5),
            ]);
        }

        $this->command->info('Sample posts created successfully!');
    }
}
