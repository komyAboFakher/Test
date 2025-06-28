<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supervisor>
 */
class SupervisorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'certification' => $this->faker->randomElement(['Certified Teacher', 'Masters Degree',  'Diploma']),
            'photo' => $this->faker->imageUrl(200, 200, 'people'),
            'salary' => $this->faker->randomFloat(2, 100, 999),
        ];
    }
}
