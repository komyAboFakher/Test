<?php

namespace Database\Seeders;

use App\Models\Parents;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all student users
        $studentUsers = User::where('role', 'student')->get();
        $parents = Parents::all();


        $availableStudents = $studentUsers;

        // Get all classes with student limits
        $classes = SchoolClass::all();

        foreach ($classes as $class) {
            $slots = $class->studentsNum;

            // If we've run out of students to assign, break
            if ($availableStudents->isEmpty()) {
                break;
            }

            // Take up to $slots students
            $assigned = $availableStudents->splice(0, $slots);

            foreach ($assigned as $user) {
                $randomParent = $parents->random();

                Student::factory()->create([
                    'user_id' => $user->id,
                    'class_id' => $class->id,
                    'parent_id' => $randomParent->id,
                ]);
            }

            //  update `currentStudentNumber` in the classes 
            $class->currentStudentNumber = $assigned->count();
            $class->save();
        }
    }
}
