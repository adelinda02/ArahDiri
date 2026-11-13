<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $u = fake()->unique()->userName();

        return [
            'name' => $u,
            'username' => strtolower(preg_replace('/[^a-z0-9._]/i', '', $u)).fake()->unique()->numberBetween(10, 9999),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '08'.fake()->unique()->numerify('##########'),
            'password' => 'rahasia-panjang-123',
            'level' => User::LEVEL_SMA_SMK,
        ];
    }
}
