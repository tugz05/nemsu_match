<?php

namespace App\Console\Commands;

use App\Models\Superadmin\AdminRole;
use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:superadmin {email : The email address of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user a superadmin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found.");

            return self::FAILURE;
        }

        // Update user flags
        $user->is_superadmin = true;
        $user->is_admin = true;
        $user->save();

        // Create or update admin role
        $adminRole = AdminRole::updateOrCreate(
            ['user_id' => $user->id],
            [
                'role' => 'superadmin',
                'is_active' => true,
                'assigned_by' => $user->id,
            ]
        );

        $this->info("✓ User '{$user->display_name}' ({$email}) is now a superadmin!");
        $this->info('  They can access the superadmin portal at: /superadmin');

        return self::SUCCESS;
    }
}
