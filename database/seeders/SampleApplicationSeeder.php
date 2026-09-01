<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user to assign as creator
        $adminUser = User::where('email', 'like', '%admin%')->orWhere('net_id', 'like', '%admin%')->first();
        $createdBy = $adminUser ? $adminUser->id : 1;

        // Get some roles for assignment
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();

        // Create sample applications with different URL types

        // 1. External Website
        $externalApp = Application::create([
            'name' => 'Google Scholar',
            'description' => 'Academic search engine for scholarly articles, theses, and academic publications.',
            'url' => 'https://scholar.google.com',
            'url_type' => 'external',
            'sub_path' => null,
            'base_domain' => null,
            'icon' => 'fas fa-graduation-cap',
            'is_active' => true,
            'created_by' => $createdBy,
        ]);

        // 2. Sub-application within same domain
        $subApp = Application::create([
            'name' => 'Library Admin Panel',
            'description' => 'Administrative interface for managing library resources, users, and settings.',
            'url' => null,
            'url_type' => 'sub-application',
            'sub_path' => 'admin/library',
            'base_domain' => 'https://myapp.university.edu',
            'icon' => 'fas fa-cogs',
            'is_active' => true,
            'created_by' => $createdBy,
        ]);

        // 3. Folder view within same domain
        $folderApp = Application::create([
            'name' => 'Reports Dashboard',
            'description' => 'View and generate various reports including usage statistics and analytics.',
            'url' => null,
            'url_type' => 'folder',
            'sub_path' => 'reports',
            'base_domain' => 'https://myapp.university.edu',
            'icon' => 'fas fa-chart-bar',
            'is_active' => true,
            'created_by' => $createdBy,
        ]);

        // 4. Another external app
        $externalApp2 = Application::create([
            'name' => 'GitHub Repository',
            'description' => 'Version control and collaboration platform for software development projects.',
            'url' => 'https://github.com/university/library-system',
            'url_type' => 'external',
            'sub_path' => null,
            'base_domain' => null,
            'icon' => 'fab fa-github',
            'is_active' => true,
            'created_by' => $createdBy,
        ]);

        // 5. Another sub-application
        $subApp2 = Application::create([
            'name' => 'User Management',
            'description' => 'Manage user accounts, roles, and permissions across the system.',
            'url' => null,
            'url_type' => 'sub-application',
            'sub_path' => 'admin/users',
            'base_domain' => 'https://myapp.university.edu',
            'icon' => 'fas fa-users-cog',
            'is_active' => true,
            'created_by' => $createdBy,
        ]);

        // Assign roles to applications if they exist
        if ($adminRole) {
            $externalApp->roles()->attach($adminRole->id, ['assigned_by' => $createdBy]);
            $subApp->roles()->attach($adminRole->id, ['assigned_by' => $createdBy]);
            $folderApp->roles()->attach($adminRole->id, ['assigned_by' => $createdBy]);
            $subApp2->roles()->attach($adminRole->id, ['assigned_by' => $createdBy]);
        }

        if ($managerRole) {
            $subApp->roles()->attach($managerRole->id, ['assigned_by' => $createdBy]);
            $folderApp->roles()->attach($managerRole->id, ['assigned_by' => $createdBy]);
        }

        $this->command->info('Sample applications created successfully!');
        $this->command->info('- External websites: 2');
        $this->command->info('- Sub-applications: 2');
        $this->command->info('- Folder views: 1');
    }
}
