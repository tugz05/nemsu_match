<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\Course;
use App\Models\Interest;
use Illuminate\Database\Seeder;

class ProfileSuggestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Academic Programs (NEMSU common programs)
        $programs = [
            'BS Computer Science',
            'BS Information Technology',
            'BS Accountancy',
            'BS Business Administration',
            'BS Education',
            'BS Elementary Education',
            'BS Secondary Education',
            'BS Civil Engineering',
            'BS Electrical Engineering',
            'BS Mechanical Engineering',
            'BS Agriculture',
            'BS Fisheries',
            'BS Marine Biology',
            'BS Environmental Science',
            'BS Forestry',
            'BS Nursing',
            'BS Psychology',
            'BS Criminology',
            'BS Tourism Management',
            'BS Hospitality Management',
            'Bachelor of Arts in English',
            'Bachelor of Arts in Social Science',
            'BS Mathematics',
            'BS Biology',
            'BS Chemistry',
        ];

        foreach ($programs as $program) {
            AcademicProgram::create([
                'name' => $program,
                'usage_count' => rand(5, 50)
            ]);
        }

        // Seed Common Courses
        $courses = [
            'Data Structures and Algorithms',
            'Web Development',
            'Database Management',
            'Computer Programming',
            'Mobile App Development',
            'Software Engineering',
            'Network Administration',
            'Calculus',
            'Physics',
            'Chemistry',
            'Biology',
            'Statistics',
            'Accounting',
            'Financial Management',
            'Marketing',
            'English Literature',
            'Filipino',
            'Social Science',
            'Philippine History',
            'Research Methods',
            'Thesis Writing',
            'Environmental Science',
            'Agriculture Technology',
            'Fisheries Management',
            'Marine Conservation',
            'Engineering Mathematics',
            'Thermodynamics',
            'Circuit Analysis',
            'Structural Engineering',
            'Educational Psychology',
            'Curriculum Development',
        ];

        foreach ($courses as $course) {
            Course::create([
                'name' => $course,
                'usage_count' => rand(3, 30)
            ]);
        }

        // Seed Research Interests
        $researchInterests = [
            'Artificial Intelligence',
            'Machine Learning',
            'Data Science',
            'Cybersecurity',
            'Climate Change',
            'Marine Conservation',
            'Sustainable Agriculture',
            'Renewable Energy',
            'Educational Technology',
            'Social Media Impact',
            'Environmental Protection',
            'Biodiversity',
            'Coastal Management',
            'Community Development',
            'Public Health',
            'Mental Health Awareness',
            'Indigenous Knowledge',
            'Food Security',
            'Water Resources',
            'Disaster Risk Reduction',
        ];

        foreach ($researchInterests as $interest) {
            Interest::create([
                'name' => $interest,
                'category' => 'research',
                'usage_count' => rand(2, 25)
            ]);
        }

        // Seed Extracurricular Activities
        $extracurricular = [
            'Student Council',
            'Basketball',
            'Volleyball',
            'Football',
            'Badminton',
            'Chess Club',
            'Debate Team',
            'Drama Club',
            'Music Club',
            'Dance Troupe',
            'Choir',
            'Art Club',
            'Photography Club',
            'Journalism',
            'School Paper',
            'Science Club',
            'Math Club',
            'Computer Society',
            'Environmental Club',
            'Outreach Programs',
            'Red Cross Youth',
            'Scouting',
            'ROTC',
            'Cultural Dance',
            'Band',
        ];

        foreach ($extracurricular as $activity) {
            Interest::create([
                'name' => $activity,
                'category' => 'extracurricular',
                'usage_count' => rand(3, 35)
            ]);
        }

        // Seed Hobbies
        $hobbies = [
            'Reading',
            'Writing',
            'Gaming',
            'Cooking',
            'Baking',
            'Photography',
            'Painting',
            'Drawing',
            'Singing',
            'Playing Guitar',
            'Playing Piano',
            'Dancing',
            'Traveling',
            'Hiking',
            'Swimming',
            'Running',
            'Cycling',
            'Yoga',
            'Meditation',
            'Watching Movies',
            'Watching Anime',
            'K-Drama',
            'Listening to Music',
            'Playing Sports',
            'Collecting',
            'Gardening',
            'Coding',
            'Video Editing',
            'Graphic Design',
            'Fashion',
        ];

        foreach ($hobbies as $hobby) {
            Interest::create([
                'name' => $hobby,
                'category' => 'hobby',
                'usage_count' => rand(5, 40)
            ]);
        }

        // Seed Academic Goals
        $goals = [
            'Graduate with honors',
            'Publish research',
            'Win academic competition',
            'Get scholarship',
            'Study abroad',
            'Pass board exam',
            'Dean\'s lister',
            'President\'s lister',
            'Magna cum laude',
            'Summa cum laude',
            'Complete thesis on time',
            'Present at conference',
            'Win research grant',
            'Join international competition',
            'Master\'s degree',
            'PhD degree',
            'Professional license',
            'Land dream job',
            'Start own business',
            'Become professor',
        ];

        foreach ($goals as $goal) {
            Interest::create([
                'name' => $goal,
                'category' => 'academic_goal',
                'usage_count' => rand(3, 20)
            ]);
        }

        $this->command->info('Profile suggestions seeded successfully!');
    }
}
