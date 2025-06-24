<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        static $numbers = null;

        if ($numbers === null) {
            $numbers = range(1, 10); // Creates [1,2,3,4,...,10]
            shuffle($numbers); // Shuffles the numbers randomly
        }


        return [
        'className' => '10-' . array_pop($numbers),
        'studentsNum' => $this->faker->numberBetween(10, 50),
        ];
    }
}
