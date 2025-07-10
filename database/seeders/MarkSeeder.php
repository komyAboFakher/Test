<?php

namespace Database\Seeders;

use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::all();
        $subjects = Subject::all();
        $teachers = Teacher::all(); // Assuming any teacher can teach any subject for now

        $semesters = ['First', 'Second'];
        $types = ['final', 'mid-term', 'quizz'];
        foreach ($students as $student) {
            foreach ($semesters as $semester) {
                // Pick 6 random subjects per semester
                $selectedSubjects = $subjects->random(6);

                foreach ($selectedSubjects as $subject) {
                    // Assign random teacher to subject
                    $teacher = $teachers->random();
                    //$markValue = fake()->numberBetween($subject->minMark, $subject->maxMark);
                    $markValue = fake()->numberBetween(40, 100);
                    $isSuccessful = $markValue >= $subject->minMark && $markValue <= $subject->maxMark ? 1 : 0;


                    Mark::factory()->create([
                        'Mark' => $markValue,
                        'student_id' => $student->id,
                        'class_id' => $student->class_id,
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'semester' => $semester,
                        'success' => $isSuccessful,
                        'type' => fake()->randomElement($types),
                    ]);
                }
            }
        }
    }
}
