<?php

namespace Database\Factories;

use App\Models\InfoUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InfoUserFactory extends Factory
{
    protected $model = InfoUser::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'age' => $this->faker->numberBetween(18, 65),
            'genre' => $this->faker->randomElement(['Male', 'Female', 'Other']),
            'nickname' => $this->faker->userName(),
            'bio' => $this->faker->optional()->sentence(20),
            'photo' => $this->faker->optional()->imageUrl(),
            'share_email' => $this->faker->boolean(),
            'available_for_online' => $this->faker->boolean(80), // 80% true
        ];
    }
}
