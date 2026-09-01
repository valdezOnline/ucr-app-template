<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'net_id' => 'testuser',
        //     'first_name' => 'Test',
        //     'last_name' => 'User',
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        //     'is_admin' => true,
        // ]);

        // Seed roles and applications for the dynamic role system
        $this->call([
            UserSeeder::class,
            RoleSeeder::class,
            ApplicationSeeder::class,
        ]);
    }
}
