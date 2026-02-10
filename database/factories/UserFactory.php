<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    private static array $campuses = [
        'Tandag', 'Bislig', 'Tagbina', 'Lianga', 'Cagwait', 'San Miguel', 'Marihatag Offsite', 'Cantilan',
    ];

    private static array $programs = [
        'BS Computer Science', 'BS Information Technology', 'BS Business Administration',
        'BS Nursing', 'BS Education', 'BS Criminology', 'BS Engineering', 'BS Agriculture',
        'BS Psychology', 'BS Accountancy', 'BS Biology', 'BS Mathematics',
    ];

    private static array $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year', 'Graduate'];

    private static array $interestsPool = [
        'Coding', 'Gaming', 'Coffee', 'Reading', 'Music', 'Travel', 'Photography', 'Art',
        'Sports', 'Cooking', 'Movies', 'Hiking', 'Research', 'Writing', 'Volunteering',
        'Healthcare', 'Business', 'Design', 'Fitness', 'Nature', 'Dancing', 'Chess',
    ];

    private static array $coursesPool = [
        'Data Structures', 'Algorithms', 'Web Development', 'Database Systems',
        'Calculus', 'Statistics', 'Psychology 101', 'Marketing', 'Accounting',
        'Anatomy', 'Chemistry', 'Physics', 'Literature', 'Sociology',
    ];

    private static array $researchPool = [
        'Machine Learning', 'Renewable Energy', 'Public Health', 'Social Media',
        'Sustainable Agriculture', 'Education Technology', 'Climate Change',
    ];

    private static array $goalsPool = [
        'Graduate with honors', 'Pursue graduate studies', 'Start a business',
        'Work abroad', 'Research career', 'Teach', 'Tech industry',
    ];

    private static array $extracurricularPool = [
        'Student Council', 'Debate Club', 'Sports Team', 'Music Band', 'Theater',
        'Volunteer Org', 'Coding Club', 'Dance Troupe', 'Chess Club',
    ];

    private static array $relationshipStatuses = ['Single', 'In a Relationship', "It's Complicated"];

    private static array $lookingForOptions = ['Friendship', 'Relationship', 'Casual Date'];

    private static array $genderOptions = ['Male', 'Female', 'Lesbian', 'Gay'];

    private static array $idealMatchPool = [
        'Funny', 'Ambitious', 'Adventurous', 'Kind', 'Honest', 'Creative',
        'Supportive', 'Open-minded', 'Passionate', 'Thoughtful',
    ];

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $fullname = "{$firstName} {$lastName}";
        $displayName = $firstName.Str::limit(Str::slug($lastName), 1, '').fake()->optional(0.7)->numerify('#');

        return [
            'name' => $fullname,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'display_name' => $displayName,
            'fullname' => $fullname,
            'campus' => fake()->randomElement(self::$campuses),
            'academic_program' => fake()->randomElement(self::$programs),
            'year_level' => fake()->randomElement(self::$yearLevels),
            'profile_picture' => self::randomProfilePictureUrl(),
            'courses' => $this->randomJsonSubset(self::$coursesPool, 2, 5),
            'research_interests' => $this->randomJsonSubset(self::$researchPool, 0, 4),
            'extracurricular_activities' => $this->randomJsonSubset(self::$extracurricularPool, 0, 3),
            'academic_goals' => $this->randomJsonSubset(self::$goalsPool, 0, 3),
            'bio' => fake()->optional(0.9)->sentence(12),
            'date_of_birth' => fake()->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(self::$genderOptions),
            'interests' => $this->randomJsonSubset(self::$interestsPool, 2, 6),
            'profile_completed' => true,
            'nemsu_id' => 'SEED-'.Str::upper(Str::random(8)).fake()->unique()->numerify('###'),
            'relationship_status' => fake()->randomElement(self::$relationshipStatuses),
            'looking_for' => fake()->randomElement(self::$lookingForOptions),
            'preferred_gender' => fake()->optional(0.7)->randomElement(array_merge([''], self::$genderOptions)),
            'preferred_age_min' => $preferredAgeMin = fake()->optional(0.6)->numberBetween(18, 28),
            'preferred_age_max' => $preferredAgeMin !== null ? fake()->numberBetween($preferredAgeMin, min(35, $preferredAgeMin + 10)) : fake()->optional(0.6)->numberBetween(22, 35),
            'preferred_campuses' => $this->randomJsonSubset(self::$campuses, 0, 4),
            'ideal_match_qualities' => $this->randomJsonSubset(self::$idealMatchPool, 0, 6),
            'preferred_courses' => $this->randomJsonSubset(self::$coursesPool, 0, 5),
        ];
    }

    /**
     * Return a random profile picture URL from a public image API.
     */
    private static function randomProfilePictureUrl(): string
    {
        $id = fake()->unique()->numberBetween(1, 10000);

        return "https://picsum.photos/400/400?random={$id}";
    }

    private function randomJsonSubset(array $pool, int $min, int $max): string
    {
        $count = fake()->numberBetween($min, min($max, count($pool)));
        $selected = fake()->randomElements($pool, $count);

        return json_encode(array_values($selected));
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
