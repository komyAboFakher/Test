<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\Academic;
use Illuminate\Http\Request;
use App\Models\ScheduleBrief;
use App\Models\SchoolClass;
use App\Models\Student;

class academicController extends Controller
{
    public function endOfTheFirstSemester()
    {
        try {
            $academic = Academic::where('currentAcademic', true)->first();
            if (!$academic) {
                return response()->json([
                    'status' => false,
                    'message' => 'the academic is not found',
                ], 404);
            }
            $semester = 'first';
            $avg = AverageController::Average($semester);
            $date = now()->format('Y:m:d');
            $academic->endOfTheFirstSemester = $date;
            $academic->save();
            return response()->json([
                'status' => true,
                'message' => 'the first semester has been ended',
                'avg' => $avg,
                //'req'=>$req,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function startOfTheSecondSemester()
    {
        try {
            //getting the academic
            $academic = Academic::where('currentAcademic', true)->first();
            if (!$academic) {
                return response()->json([
                    'status' => false,
                    'message' => 'the academic is not found',
                ], 404);
            }
            //getting the date and the semester
            $date = now()->format('Y:m:d');
            $academic->academic_semester = 'second';
            $academic->startOfTheSecondSemester = $date;
            $academic->save();

            return response()->json([
                'status' => true,
                'message' => 'the second semester has been started'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function endOfTheYear()
    {
        try {
            //getting the academic
            $academic = Academic::where('currentAcademic', true)->first();
            if (!$academic) {
                return response()->json([
                    'status' => false,
                    'message' => 'the academic is not found',
                ], 404);
            }
            //getting the date and the semester
            $semester = 'second';
            // $avg = AverageController::Average($semester);

            // if ($avg->getData()->status == false) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'we cannot complete thte process',
            //         'missing_subjects' => $avg->getData()->missing_subjects,
            //     ], 422);
            // }
            //now we should transfare the students here

            $students = Student::query()
            ->join('averages as a','students.id','=','a.student_id')
            ->select('students.id','a.success','students.class_id')
            ->get();
            // return response()->json([
            //     'meow'=>$students
            // ]);
            foreach ($students as $student) {
                if ($student->success == 1) {
                    $class = SchoolClass::findOrFail($student->class_id);
                    // return response()->json([
                    //     'class'=>$class
                    // ]);
                    if ($class) {
                        [$grade, $section] = explode('-', $class->className);

                        // Promote grade
                        $newGrade = intval($grade) + 1;
                        $newClassName = $newGrade . '-' . $section;
                        // return response()->json([
                        //     'meow'=>$newGrade
                        // ]);
                        // Find or create the new class
                        $newClass = SchoolClass::where('className' , $newClassName)->first();
                        if(!$newClass){
                            $randomClass=SchoolClass::where('className', 'LIKE', "{$grade}-%")->whereColumn('currentStudentNumber','<','studentsNum')->first();    
                            // Update student
                            $student->class_id = $randomClass->id;
                            $student->save();
                        }
                        // Update student
                        $student->class_id = $newClass->id;
                        $student->save();
                    }
                }
            }

            ///////////////////////////////////////////

            //now we wanna set the current values for the schedules to false
            $scheduleBriefs = ScheduleBrief::where('current', true)->get();
            if (!$scheduleBriefs) {
                return response()->json([
                    'status' => false,
                    'message' => 'briefs Not Found',
                ], 404);
            }
            foreach ($scheduleBriefs as $brief) {
                $brief->current = false;
                $brief->save();
            }

            $sessions = Session::where('current', true)->get();
            foreach ($sessions as $session) {
                $session->current = false;
                $session->save();
            }
            if (!$sessions) {
                return response()->json([
                    'status' => false,
                    'message' => 'sessions Not Found',
                ], 404);
            }

            $date = now()->format('Y:m:d');
            $academic->academic_semester = 'first';
            $academic->endOfTheSecondSemester = $date;
            $academic->save();

            return response()->json([
                'status' => true,
                'message' => 'the second semester has been ended',
                //'student'=>$student
                //'avg' => $avg
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function startOfTheYear()
    {
        try {
            //getting the academic
            $academic = Academic::where('currentAcademic', true)->first();
            if (!$academic) {
                return response()->json([
                    'status' => false,
                    'message' => 'the academic is not found',
                ], 404);
            }
            $date = now()->format('Y:m:d');
            $academic->startOfTheFirstSemester = $date;
            $academic->save();

            return response()->json([
                'status' => true,
                'message' => 'the first semester has been started',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }
}
