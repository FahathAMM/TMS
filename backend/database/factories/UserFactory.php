<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'password'  => 'password',
            'is_active' => true,
            'phone'     => fake()->phoneNumber(),
        ];
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $user->syncRoles([$role]);
        });
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
