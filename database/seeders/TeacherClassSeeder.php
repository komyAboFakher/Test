<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\SchoolClass;
use App\Models\TeacherClass;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeacherClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $teachers = Teacher::all();
        $classes = SchoolClass::all();
        $subjects = Subject::all();

        foreach ($teachers as $teacher) {
            // Find the subject object that matches the teacher's specialization
            $subject = $subjects->firstWhere('subjectName', $teacher->subject);

            // Skip if no matching subject exists
            if (!$subject) continue;
            // Assign teacher to 3 random classes with their subject
            $assignedClasses = $classes->random(min(3, $classes->count()));

            foreach ($assignedClasses as $class) {
                TeacherClass::create([
                    'teacher_id' => $teacher->id,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id, // must match teacher's subject
                ]);
            }
        }
    }
}
