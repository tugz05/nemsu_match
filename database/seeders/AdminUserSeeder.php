<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Superadmin\AdminRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating admin users...');

        // Define admin users
        $admins = [
            [
                'name' => 'Super Administrator',
                'display_name' => 'Super Admin',
                'fullname' => 'Super Administrator',
                'email' => 'superadmin@nemsu.edu.ph',
                'password' => 'Superadmin123!',
                'nemsu_id' => 'SA-2024-001',
                'role' => 'superadmin',
                'is_admin' => true,
                'is_superadmin' => true,
                'profile_completed' => true,
            ],
            [
                'name' => 'System Administrator',
                'display_name' => 'Admin User',
                'fullname' => 'System Administrator',
                'email' => 'admin@nemsu.edu.ph',
                'password' => 'Admin123!',
                'nemsu_id' => 'AD-2024-001',
                'role' => 'admin',
                'is_admin' => true,
                'is_superadmin' => false,
                'profile_completed' => true,
            ],
            [
                'name' => 'Content Editor',
                'display_name' => 'Editor User',
                'fullname' => 'Content Editor',
                'email' => 'editor@nemsu.edu.ph',
                'password' => 'Editor123!',
                'nemsu_id' => 'ED-2024-001',
                'role' => 'editor',
                'is_admin' => true,
                'is_superadmin' => false,
                'profile_completed' => true,
            ],
        ];

        foreach ($admins as $adminData) {
            // Extract role and password
            $role = $adminData['role'];
            $plainPassword = $adminData['password'];
            
            unset($adminData['role']);
            unset($adminData['password']);

            // Check if user already exists
            $user = User::where('email', $adminData['email'])->first();

            if ($user) {
                $this->command->warn("User already exists: {$adminData['email']}");
                
                // Update existing user
                $user->update([
                    'password' => Hash::make($plainPassword),
                    'is_admin' => $adminData['is_admin'],
                    'is_superadmin' => $adminData['is_superadmin'],
                    'profile_completed' => $adminData['profile_completed'],
                ]);
                
                $this->command->info("Updated user: {$adminData['email']}");
            } else {
                // Create new user
                $user = User::create([
                    'name' => $adminData['name'],
                    'display_name' => $adminData['display_name'],
                    'fullname' => $adminData['fullname'],
                    'email' => $adminData['email'],
                    'password' => Hash::make($plainPassword),
                    'nemsu_id' => $adminData['nemsu_id'],
                    'email_verified_at' => now(),
                    'is_admin' => $adminData['is_admin'],
                    'is_superadmin' => $adminData['is_superadmin'],
                    'profile_completed' => $adminData['profile_completed'],
                    'gender' => 'prefer_not_to_say',
                    'date_of_birth' => now()->subYears(25),
                    'campus' => 'Main Campus',
                    'academic_program' => 'Administration',
                    'year_level' => 'Faculty',
                ]);

                $this->command->info("Created user: {$adminData['email']}");
            }

            // Create or update admin role
            $adminRole = AdminRole::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'role' => $role,
                    'is_active' => true,
                    'assigned_by' => $user->id, // Self-assigned for seeder
                    'permissions' => null,
                ]
            );

            $this->command->info("Assigned role '{$role}' to: {$user->email}");
        }

        $this->command->newLine();
        $this->command->info('Admin users created successfully!');
        $this->command->newLine();
        $this->command->table(
            ['Email', 'Password', 'Role', 'Access'],
            [
                ['superadmin@nemsu.edu.ph', 'Superadmin123!', 'Superadmin', 'Full system access'],
                ['admin@nemsu.edu.ph', 'Admin123!', 'Admin', 'User management'],
                ['editor@nemsu.edu.ph', 'Editor123!', 'Editor', 'Content moderation'],
            ]
        );
        $this->command->newLine();
        $this->command->info('You can now login at: /admin/login');
    }
}
