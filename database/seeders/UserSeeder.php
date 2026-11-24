<?php

namespace Database\Seeders;

use Modules\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create an admin user
        User::create([
            'net_id' => 'admin',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'name' => 'Admin User',
            'email' => 'admin@ucr.edu',
            'department' => 'IT Department',
            'title' => 'System Administrator',
            'supervisor_net_id' => null,
            'supervisor_name' => null,
            'is_active' => true,
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create a manager user
        User::create([
            'net_id' => 'manager01',
            'first_name' => 'Jane',
            'last_name' => 'Manager',
            'name' => 'Jane Manager',
            'email' => 'manager@ucr.edu',
            'department' => 'University Library',
            'title' => 'Library Manager',
            'supervisor_net_id' => 'admin',
            'supervisor_name' => 'Admin User',
            'is_active' => true,
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create a regular staff user
        User::create([
            'net_id' => 'staff01',
            'first_name' => 'John',
            'last_name' => 'Staff',
            'name' => 'John Staff',
            'email' => 'staff@ucr.edu',
            'department' => 'University Library',
            'title' => 'Library Assistant',
            'supervisor_net_id' => 'manager01',
            'supervisor_name' => 'Jane Manager',
            'is_active' => true,
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create an inactive user
        User::create([
            'net_id' => 'inactive01',
            'first_name' => 'Former',
            'last_name' => 'Employee',
            'name' => 'Former Employee',
            'email' => 'former@ucr.edu',
            'department' => 'University Library',
            'title' => 'Former Position',
            'supervisor_net_id' => 'manager01',
            'supervisor_name' => 'Jane Manager',
            'is_active' => false,
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create a supervisor user
        User::create([
            'net_id' => 'supervisor01',
            'first_name' => 'Bob',
            'last_name' => 'Supervisor',
            'name' => 'Bob Supervisor',
            'email' => 'supervisor@ucr.edu',
            'department' => 'University Library',
            'title' => 'Senior Librarian',
            'supervisor_net_id' => 'manager01',
            'supervisor_name' => 'Jane Manager',
            'is_active' => true,
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Assign system roles (the 'admin', 'manager', 'supervisor' roles are created by migration)
        $systemRoles = [
            'admin' => ['admin'],
            'manager01' => ['manager', 'supervisor'],
            'supervisor01' => ['supervisor'],
        ];

        foreach ($systemRoles as $netId => $roleNames) {
            $user = User::firstWhere('net_id', $netId);
            foreach ($roleNames as $roleName) {
                $user?->assignRole($roleName);
            }
        }
    }
}
