<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'subjectName' => $this->faker->unique()->randomElement(['physics','math','chemistry','history','biology','computer']),
        'minMark' => $this->faker->randomElement([50, 60]), 
        'maxMark' => 100, 
        'grade' => $this->faker->numberBetween(1, 12), 
        ];
    }
}
