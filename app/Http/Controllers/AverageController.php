<?php

namespace App\Http\Controllers;

use App\Models\Mark;
use App\Models\Average;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Academic;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class AverageController extends Controller
{
    protected function calculateSemesterAverage(Collection $marks): ?float
    {
        if ($marks->isEmpty()) return null;

        $total = $marks->sum('mark');
        return round($total / $marks->count(), 2);
    }
    //_________________________________________________________________________________

    protected function calculateFinalGPA(?float $average1, ?float $average2): ?float
    {
        if (is_null($average1) || is_null($average2)) return null;

        return round(($average1 + $average2) / 2, 2);
    }
    //_________________________________________________________________________________

    protected function currentAcademicYear(): string
    {
        return now()->format('Y') . '-' . now()->addYear()->format('Y');
    }

    //_________________________________________________________________________________
    public static function Average(string $semester)
    {
        // $validator = Validator::make(
        //     $request->all(),
        //     ['semester' => 'required|string|in:First,Second'],
        //     ['semester.in' => 'Semester must be First or Second']
        // );

        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => $validator->errors(),
        //     ], 422);
        // }

        try {


            $grades = range(1, 12);
            $missingSubjectsNames = [];
            $academicYear = Academic::where('currentAcademic', true)->first();
            //$semester = $request->semester;

            foreach ($grades as $grade) {
                $subjectIds = Subject::where('grade', $grade)->pluck('id')->toArray();
                $classes = SchoolClass::where('className', 'LIKE', "{$grade}-%")->get();

                foreach ($classes as $class) {
                    $existingSubjectIds = Mark::where('class_id', $class->id)
                        ->where('semester', $semester)
                        ->pluck('subject_id')
                        ->unique()
                        ->toArray();
                    $missingSubjectIds = array_diff($subjectIds, $existingSubjectIds);

                    if ($missingSubjectIds) {

                        foreach ($missingSubjectIds as $missingId) {
                            $subjectName = Subject::find($missingId)?->subjectName;
                            if ($subjectName) {
                                $missingSubjectsNames[$class->className][] = "{$subjectName} ({$missingId})";
                            }
                        }

                        return response()->json([
                            "status" => false,
                            "message" => "there has been missing subjects, you can't calculate the GPA's",
                            'missing_subjects' => $missingSubjectsNames,
                        ], 422);
                    }




                    $students = Student::where('class_id', $class->id)->get();

                    foreach ($students as $student) {
                        $komy = 1;

                        // check if the first semester avergaes not calculated yet !!

                        if ($semester === 'Second') {
                            $existingAverage = Average::where('student_id', $student->id)
                                ->where('academic_year', $academicYear)
                                ->first();

                            if (is_null($existingAverage?->average_1)) {
                                return response()->json([
                                    "message" => "the first semester averages not calculated yet, you can't calculate the second semester !!!"
                                ], 422);
                            }
                        }


                        $marks = Mark::where('student_id', $student->id)
                            ->where('semester', $semester)
                            ->get();


                        foreach ($marks as $mark) {
                            if ($mark->success == 0) {
                                $komy = 0;
                                break;
                            }
                        }

                        $semesterAverage = AverageController::calculateSemesterAverage($marks);

                        $averageRecord = Average::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'academic_year' => $academicYear,
                            ],
                            $semester === 'First'
                                ? ['average_1' => $semesterAverage, 'success' => $komy,]
                                : ['average_2' => $semesterAverage, 'success' => $komy,]
                        );

                        //$averageRecord->refresh();

                        if ($semester === 'Second') {
                            $finalGPA = AverageController::calculateFinalGPA($averageRecord->average_1, $averageRecord->average_2);
                            if (!is_null($finalGPA)) {
                                $averageRecord->update(['average_final' => $finalGPA]);
                            }
                        }
                    }
                }
            }
            return response()->json([
                'status' => true,
                'message' => "Averages calculated for all grades in semester {$semester}.",
                //'missing_subjects' => $missingSubjectsNames,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
