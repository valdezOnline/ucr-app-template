<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('net_id', 'admin')->first();

        $roles = [
            [
                'name' => 'library_staff',
                'display_name' => 'Library Staff',
                'description' => 'General library staff members with basic access',
                'color' => '#28a745',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'library_manager',
                'display_name' => 'Library Manager',
                'description' => 'Library managers with elevated permissions',
                'color' => '#007bff',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'it_support',
                'display_name' => 'IT Support',
                'description' => 'IT support staff with technical access',
                'color' => '#6f42c1',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'student_worker',
                'display_name' => 'Student Worker',
                'description' => 'Student employees with limited access',
                'color' => '#fd7e14',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
            [
                'name' => 'department_liaison',
                'display_name' => 'Department Liaison',
                'description' => 'Academic department liaisons',
                'color' => '#20c997',
                'is_active' => true,
                'created_by' => $adminUser?->id,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        // Assign some roles to existing users
        if ($adminUser) {
            $libraryManagerRole = Role::where('name', 'library_manager')->first();
            if ($libraryManagerRole) {
                $adminUser->assignRole($libraryManagerRole);
            }
        }

        $managerUser = User::where('net_id', 'manager01')->first();
        if ($managerUser) {
            $libraryManagerRole = Role::where('name', 'library_manager')->first();
            if ($libraryManagerRole) {
                $managerUser->assignRole($libraryManagerRole);
            }
        }

        $staffUser = User::where('net_id', 'staff01')->first();
        if ($staffUser) {
            $libraryStaffRole = Role::where('name', 'library_staff')->first();
            if ($libraryStaffRole) {
                $staffUser->assignRole($libraryStaffRole);
            }
        }
    }
}
