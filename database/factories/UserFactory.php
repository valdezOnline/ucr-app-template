<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Core\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = \Modules\Core\Models\User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'net_id' => fake()->unique()->userName(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $firstName . ' ' . $lastName,
            'email' => fake()->unique()->safeEmail(),
            'department' => fake()->optional()->randomElement([
                'Library Services',
                'Information Technology',
                'Administration',
                'Technical Services',
                'Special Collections',
                'Student Services'
            ]),
            'title' => fake()->optional()->randomElement([
                'Librarian',
                'Senior Librarian',
                'IT Specialist',
                'Library Assistant',
                'Department Head',
                'Student Worker',
                'Administrative Assistant'
            ]),
            'supervisor_net_id' => fake()->optional()->userName(),
            'supervisor_name' => fake()->optional()->name(),
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Give the user the admin system role after creation.
     */
    public function admin(): static
    {
        return $this->afterCreating(fn($user) => $user->assignRole('admin'));
    }

    /**
     * Give the user the manager system role after creation.
     */
    public function manager(): static
    {
        return $this->afterCreating(fn($user) => $user->assignRole('manager'));
    }

    /**
     * Give the user the supervisor system role after creation.
     */
    public function supervisor(): static
    {
        return $this->afterCreating(fn($user) => $user->assignRole('supervisor'));
    }
}
