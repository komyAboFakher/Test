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
          // no duplicate names here, as long as you seed only 26 class, the number of letters in the
          // english alphabet (A->Z)
        static $index = 0;
        $letters = range('A', 'Z'); // Up to 26 classes (10-A to 10-Z)

        return [
            'className' => '10-' . $letters[$index++],
            'studentsNum' => $this->faker->numberBetween(30,40),
        ];
    }
}
