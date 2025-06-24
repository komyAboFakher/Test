<?php

namespace Database\Factories;

use App\Models\schoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'user_id' => User::where('role', 'student')->inRandomOrder()->first()->id,
        'class_id' => schoolClass::inRandomOrder()->first()->id,
        'schoolGraduatedFrom' => $this->faker->company,
        'photo' => $this->faker->imageUrl(200, 200, 'people', true),
        'Gpa' => $this->faker->randomFloat(2, 0, 4),
        'expelled' => $this->faker->boolean,
        'justification' => $this->faker->sentence,

        ];
    }
}
