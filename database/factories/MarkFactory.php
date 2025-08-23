<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mark>
 */
class MarkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mark'    => $this->faker->numberBetween(40, 100),
            'success' => 1, 
            'semester' => $this->faker->randomElement(['First', 'Second']),
            'type'    => $this->faker->randomElement(['final', 'mid-term', 'quizz']),
            'class_id'   => null,
            'student_id' => null,
            'teacher_id' => null,
            'subject_id' => null,
        ];
    }
}
