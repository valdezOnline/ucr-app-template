<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::firstWhere('net_id', 'admin');

        $applications = [
            [
                'name' => 'Billing Appeals',
                'description' => 'System for managing billing appeals',
                'url' => 'https://billingappeals.libportaltest.ucr.edu',
                'icon' => 'fas fa-file-invoice',
                'is_active' => true,
                'created_by' => $adminUser?->id,
                'roles' => ['library_staff', 'library_manager'],
            ],
            [
                'name' => 'Professional Development Requests',
                'description' => 'System for managing professional development requests',
                'url' => 'https://library-apps-test.ucr.edu/professional-development-requests',
                'icon' => 'fas fa-chalkboard-teacher',
                'is_active' => true,
                'created_by' => $adminUser?->id,
                'roles' => ['library_staff', 'library_manager', 'department_liaison'],
            ],
            [
                'name' => 'Digital Collections',
                'description' => 'Digital archives and special collections management',
                'url' => 'https://digital.library.ucr.edu',
                'icon' => 'fas fa-archive',
                'is_active' => true,
                'created_by' => $adminUser?->id,
                'roles' => ['library_staff', 'library_manager'],
            ],
            [
                'name' => 'IT Help Desk',
                'description' => 'Internal IT support and ticketing system',
                'url' => 'https://helpdesk.library.ucr.edu',
                'icon' => 'fas fa-tools',
                'is_active' => true,
                'created_by' => $adminUser?->id,
                'roles' => ['it_support', 'library_manager'],
            ],
            [
                'name' => 'Student TimeTracker',
                'description' => 'Time tracking system for student workers',
                'url' => 'https://timetracker.libportaltest.ucr.edu',
                'icon' => 'fas fa-clock',
                'is_active' => true,
                'created_by' => $adminUser?->id,
                'roles' => ['student_worker', 'library_manager'],
            ],
            [
                'name' => 'Administrative Dashboard',
                'description' => 'Executive dashboard with library analytics and reports',
                'url' => 'https://admin.library.ucr.edu',
                'icon' => 'fas fa-chart-line',
                'is_active' => true,
                'created_by' => $adminUser?->id,
                'roles' => ['library_manager'],
            ],
        ];

        foreach ($applications as $appData) {
            $roleNames = $appData['roles'];
            unset($appData['roles']);

            $application = Application::create($appData);

            // Assign roles to application
            foreach ($roleNames as $roleName) {
                $role = Role::firstWhere('name', $roleName);
                if ($role) {
                    $application->roles()->attach($role->id, ['assigned_by' => $adminUser?->id]);
                }
            }
        }
    }
}
