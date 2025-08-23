<?php

namespace Database\Seeders;

use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherClass;
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


        $semesters = ['First', 'Second'];
        $types = ['final', 'mid-term'];
        
        foreach ($students as $student) {



            foreach ($semesters as $semester) {

                $class = SchoolClass::find($student->class_id);
                if (!$class) continue;

                $grade = explode('-', $class->className)[0];



                $selectedSubjects = Subject::where('grade', $grade)->get();




                foreach ($types as $type) {

                    foreach ($selectedSubjects as $subject) {

                       
                        $teacher = TeacherClass::where('subject_id', $subject->id)->inRandomOrder()->first();
                        if (!$teacher) continue;

                        $markValue = fake()->numberBetween(40, 100);
                        $isSuccessful = $markValue >= $subject->minMark && $markValue <= $subject->maxMark ? 1 : 0;


                        Mark::factory()->create([
                            'mark' => $markValue,
                            'student_id' => $student->id,
                            'class_id' => $student->class_id,
                            'teacher_id' => $teacher->teacher_id,
                            'subject_id' => $subject->id,
                            'semester' => $semester,
                            'success' => $isSuccessful,
                            'type' => $type
                        ]);
                    }
                }
            }
        }
        $this->command->info('📚 Seeding marks for students...'.$students->count());

    }

    
}



