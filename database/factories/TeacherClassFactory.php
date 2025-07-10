<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\SchoolClass;
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


        $teacher = Teacher::inRandomOrder()->first();

        // Try to find a subject that matches the teacher's specialization
        $subject = Subject::where('subjectName', $teacher->subject)->first();

        // If no match found, default to any subject (or skip assignment depending on your logic)
        if (!$subject) {
            $subject = Subject::inRandomOrder()->first();
        }


        return [
            'teacher_id' => $teacher->id,
            //'class_id' => SchoolClass::inRandomOrder()->first()->id,
            'subject_id' => $subject->id,


        ];
    }
}
