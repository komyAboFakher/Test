<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherClass>
 */
class TeacherClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       


        return [
        'teacher_id' => \App\Models\Teacher::inRandomOrder()->first()->id,
        'class_id' => \App\Models\SchoolClass::inRandomOrder()->first()->id,
        'subject_id' =>\App\Models\Subject::inRandomOrder()->first()->id ,

        ];
    }
}
