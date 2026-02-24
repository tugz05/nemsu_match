<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnonymousChatRoom extends Model
{
    protected $fillable = ['user1_id', 'user2_id', 'display_name', 'user1_agreed_to_reveal', 'user2_agreed_to_reveal'];

    protected $casts = [
        'user1_agreed_to_reveal' => 'boolean',
        'user2_agreed_to_reveal' => 'boolean',
    ];

    /** Singular superhero-themed display names by gender (for anonymous chat rooms) */
    private static function getSuperheroNamePools(): array
    {
        return [
            'male' => [
                'Iron Man',
                'Batman',
                'Superman',
                'Captain America',
                'Thor',
                'Spider-Man',
                'Wolverine',
                'Flash',
                'Green Lantern',
                'Hawkeye',
                'Black Panther',
                'Doctor Strange',
                'Ant-Man',
                'Star-Lord',
                'Nightwing',
                'Cyclops',
                'Human Torch',
                'Mr Fantastic',
                'Daredevil',
                'Punisher',
            ],
            'female' => [
                'Wonder Woman',
                'Black Widow',
                'Captain Marvel',
                'Scarlet Witch',
                'Storm',
                'Jean Grey',
                'Supergirl',
                'Batgirl',
                'Catwoman',
                'She-Hulk',
                'Black Canary',
                'Harley Quinn',
                'Gamora',
                'Nebula',
                'Wasp',
                'Invisible Woman',
                'Elektra',
                'Jessica Jones',
                'Phoenix',
                'Rogue',
            ],
            'neutral' => [
                'The Phantom',
                'The Shadow',
                'The Hulk',
                'The Vision',
                'The Falcon',
                'The Winter Soldier',
                'The Punisher',
                'Mystique',
                'Deadpool',
                'Ghost Rider',
                'Silver Surfer',
                'Blade',
                'Namor',
                'Aquaman',
                'Martian Manhunter',
                'Green Arrow',
                'Nightwing',
                'Robin',
                'The Atom',
                'The Question',
            ],
        ];
    }

    private static function normalizeGender(?string $gender): string
    {
        if ($gender === null || $gender === '') {
            return 'neutral';
        }
        $g = strtolower(trim($gender));
        if (in_array($g, ['male', 'm', 'man'], true)) {
            return 'male';
        }
        if (in_array($g, ['female', 'f', 'woman'], true)) {
            return 'female';
        }

        return 'neutral';
    }

    /**
     * Random singular superhero name for a room, based on the pair's gender.
     * Both same gender → that pool; mixed/other → neutral pool.
     */
    public static function getRandomDisplayNameForPair(int $user1Id, int $user2Id): string
    {
        $pools = self::getSuperheroNamePools();
        $user1 = User::find($user1Id);
        $user2 = User::find($user2Id);
        $g1 = self::normalizeGender($user1?->gender ?? null);
        $g2 = self::normalizeGender($user2?->gender ?? null);

        $poolKey = ($g1 === 'male' && $g2 === 'male') ? 'male'
            : (($g1 === 'female' && $g2 === 'female') ? 'female' : 'neutral');

        $names = $pools[$poolKey];

        return (string) collect($names)->random();
    }

    /** @deprecated Use getRandomDisplayNameForPair for gender-based names */
    public static function getRandomDisplayName(): string
    {
        $pools = self::getSuperheroNamePools();

        return (string) collect($pools['neutral'])->random();
    }

    public function user1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AnonymousChatMessage::class, 'anonymous_chat_room_id');
    }

    /**
     * Get the other user's id in this room (from current user's perspective). Never expose profile.
     */
    public function otherUserId(int $currentUserId): ?int
    {
        if ($this->user1_id === $currentUserId) {
            return $this->user2_id;
        }
        if ($this->user2_id === $currentUserId) {
            return $this->user1_id;
        }

        return null;
    }

    /**
     * Check if the given user is a participant in this room.
     */
    public function hasUser(int $userId): bool
    {
        return $this->user1_id === $userId || $this->user2_id === $userId;
    }

    /**
     * Get or create a room for two users (canonical: user1_id < user2_id).
     * New rooms get a random university-themed display name.
     */
    public static function getOrCreateForPair(int $userIdA, int $userIdB): ?self
    {
        if ($userIdA === $userIdB) {
            return null;
        }
        $user1Id = min($userIdA, $userIdB);
        $user2Id = max($userIdA, $userIdB);

        $room = self::firstOrCreate(
            ['user1_id' => $user1Id, 'user2_id' => $user2Id],
            ['display_name' => self::getRandomDisplayNameForPair($user1Id, $user2Id)]
        );

        if ($room->wasRecentlyCreated === false && empty($room->display_name)) {
            $room->update(['display_name' => self::getRandomDisplayNameForPair($user1Id, $user2Id)]);
        }

        return $room;
    }

    /** Whether both users have agreed to reveal identity. */
    public function isRevealed(): bool
    {
        return $this->user1_agreed_to_reveal && $this->user2_agreed_to_reveal;
    }

    /** Whether the given user (by position) has agreed to reveal. */
    public function hasUserAgreedToReveal(int $userId): bool
    {
        if ($this->user1_id === $userId) {
            return $this->user1_agreed_to_reveal;
        }
        if ($this->user2_id === $userId) {
            return $this->user2_agreed_to_reveal;
        }

        return false;
    }

    /** Get the other user in the room (for reveal). */
    public function getOtherUser(int $currentUserId): ?User
    {
        $otherId = $this->otherUserId($currentUserId);

        return $otherId ? User::find($otherId) : null;
    }
}
