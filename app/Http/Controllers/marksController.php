<?php

namespace App\Http\Controllers;

use App\Models\Mark;
use App\Models\Subject;
use App\Models\schoolClass;
use App\Models\TeacherClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Storage;

class marksController extends Controller
{
        // MAJD //get the teacher's classses based on teacher id

    public function getAllTeacherInfo($teacherID)
    {


        try {






            $teacherClasses = TeacherClass::select(
                'teacher_classes.teacher_id',
                'teacher_classes.class_id',
                'classes.className',
                'teacher_classes.subject_id',
                'subjects.subjectName'
            )
                ->join('classes', 'teacher_classes.class_id', '=', 'classes.id')
                ->join('subjects', 'teacher_classes.subject_id', '=', 'subjects.id')
                ->where('teacher_id', $teacherID)
                ->get();

            return response()->json([
                'status' => true,
                'data' => $teacherClasses
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }


    // MAJD // generating epmty excel cheat that has the students info for the teacher and download it to the device.

    public function getEmptyExcelCheatForMarks($teacherID, $classID, $subjectID, $semester, $type)
    {


        try {



            // get the students with the specific class with their needed info.

            $SchoolClass = schoolClass::with([
                'Students.Users' => function ($query) {
                    $query->select('id', 'name', 'middleName', 'lastName');
                }
            ])->findOrFail($classID);

            //get the the subject name for the title of the excel file

            $teacherClass = TeacherClass::where('teacher_id', $teacherID)
                ->where('class_id', $classID)
                ->where('subject_id', $subjectID)
                ->with('Subject')
                ->firstOrFail();

            $subjectName = $teacherClass->Subject->subjectName;


            //making the excel rows, each field in one row, then vlaue in the same row but next column

            $data = collect([]);

            // the main headers in the excel cheat

            $data->push(
                [
                    'student id' => 'student id',
                    'first name' => 'first name',
                    'middle name' => 'middle name',
                    'last name' => 'last name',
                    'mark' => 'mark',
                    'semester' => 'semester',
                    'type' => 'type'
                ]
            );


            // Add student rows 

            foreach ($SchoolClass->students as $student) {
                $data->push([
                    'student id' => $student->id,
                    'first name'  => $student->Users->name,
                    'middle name' => $student->Users->middleName,
                    'last name'   => $student->Users->lastName,
                    'mark' => '', // Empty for the teacher
                    'semester' => $semester,
                    'type' => $type
                ]);
            }

            // Return FastExcel-generated Excel file for download

            // Generate the file name 
            $fileName = "{$subjectName}_{$SchoolClass->className}_{$type}_{$semester}.xlsx";

            // replace special characters

            $fileName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $fileName);


            // Define storage paths

            // Define paths
            $directory = 'marks_excels';
            $relativePath = "$directory/$fileName";  // Relative to storage disk
            $fullPath = storage_path("app/public/$relativePath");

            // Ensure directory exists
            Storage::disk('public')->makeDirectory($directory);

            // Generate and save Excel file
            (new FastExcel($data))
                ->withoutHeaders()
                ->export($fullPath);

            // Verify file was created
            if (!file_exists($fullPath)) {
                throw new \Exception("Failed to create Excel file");
            }

            // Generate correct URL (MUST match storage link structure)
            $fileUrl = asset("storage/$relativePath");




            return response()->json([
                'status' => true,
                'file_url' => $fileUrl,
                'message' => 'marks file  generated successfully',

            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }










    //////////////////////////////////////////////////////////////////////////////////////////////////////




    public function uploadMarkExcelCheat(Request $request)
    {
        try {
            // Validate that an Excel_file/ class_id/ teacher_id
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls',
                'teacher_id' => 'required|integer',
                'class_id'   => 'required|integer',
                'subject_id' => 'required|integer',

            ]);


            // Get form values.
            $teacherID = $request->input('teacher_id');
            $classID   = $request->input('class_id');
            $subjectID   = $request->input('subject_id');

            // creating a path for the uploaded file !!!





            // check the min mark in the subjects table to determine the success mark

            $minMark = Subject::where('id', $subjectID)->value('minMark');

            // Define allowed values from database enums
            $allowedSemesters = ['First', 'Second'];
            $allowedExamTypes = ['final', 'mid-term', 'quizz'];
            $anyChangesMade = false;



            // Read the uploaded Excel file.

            $file = $request->file('excel_file');
            $rows = (new \Rap2hpoutre\FastExcel\FastExcel())->import($file->getRealPath());




            // Convert each row to an array and make keys lowercase for consistency.


            $rowsArr = [];
            foreach ($rows as $row) {
                $row = (array)$row;
                $row = array_change_key_case($row, CASE_LOWER);
                $rowsArr[] = $row;
            }

            $dataRows = [];
            $isFirstRow = true;


            // The first row is the header row 

            foreach ($rowsArr as $row) {



                if ($isFirstRow) {

                    // read the header row then skip it!!!

                    if (isset($row['student id']) && strtolower(trim($row['student id'])) === 'student id') {
                        $isFirstRow = false;
                        continue;
                    }
                }
                $isFirstRow = false;

                // from now on, every row should be student.

                if (!isset($row['student id']) || !is_numeric($row['student id'])) {
                    throw new \Exception("Invalid student ID in row.");
                }
                $dataRows[] = $row;
            }

            // just for debugging, deepseek gave it to me!!

            if (empty($dataRows)) {
                $firstFewRows = array_slice($rowsArr, 0, 5);
                throw new \Exception('No student records found. First few rows: ' . json_encode($firstFewRows));
            }

            // inserting the students record into the marks table

            DB::beginTransaction();

            foreach ($dataRows as $row) {
                $studentID = $row['student id'];

                // Convert mark to string as it stored in our table

                $mark = isset($row['mark']) ? (string)$row['mark'] : '';


                // Validate and format semester
                $semester = ucfirst(strtolower($row['semester'] ?? ''));
                if (!in_array($semester, $allowedSemesters)) {
                    throw new \Exception("Invalid semester value: {$row['semester']}. Allowed values: " . implode(', ', $allowedSemesters));
                }

                // Validate and format exam type
                $type = strtolower($row['type'] ?? '');
                if (!in_array($type, $allowedExamTypes)) {
                    throw new \Exception("Invalid exam type: {$row['type']}. Allowed values: " . implode(', ', $allowedExamTypes));
                }

                //based on the min mark in the subjects table
                $success = ($mark >= $minMark);


                $existing = Mark::where([
                    'class_id' => $classID,
                    'teacher_id' => $teacherID,
                    'student_id' => $studentID,
                    'subject_id' => $subjectID
                ])->first();

                if ($existing) {
                    $needsUpdate = ($existing->mark != $mark) || ($existing->semester != $semester) || ($existing->type != $type);

                    if ($needsUpdate) {
                        $anyChangesMade = true;
                        $existing->update([
                            'mark' => $mark,
                            'success' => $success,
                            'semester' => $semester,
                            'type' => $type
                        ]);
                    }
                } else {
                    $anyChangesMade = true;
                    Mark::create([
                        'class_id' => $classID,
                        'teacher_id' => $teacherID,
                        'student_id' => $studentID,
                        'subject_id' => $subjectID,
                        'mark' => $mark,
                        'success' => $success,
                        'semester' => $semester,
                        'type' => $type
                    ]);
                }
                $changedStudentIds[] = $studentID;
            }

            $directory = 'full_mark_excels';
            Storage::disk('public')->makeDirectory($directory);

            // Store the file and get full storage path
            $fileName =  $request->file('excel_file')->getClientOriginalName();
            $filePath = $request->file('excel_file')->storeAs(
                $directory,
                $fileName,
                'public'
            );

            // Get absolute storage path
            $fileUrl = asset("storage/{$directory}/{$fileName}");



            DB::commit();
            ////////////////////////////////////////////////////////////
            return response()->json([
                'status'         => true,
                'message'        => 'Marks inserted successfully !!!',
                'file_url' => $fileUrl,
                'imported_count' => count($dataRows),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////



    public function studentGetResult($studentID)
    {


        try {



            // $marks = Mark::where('student_id', $studentID)->get();

            $marks = Mark::where('student_id', $studentID)
                ->with('subject:id,subjectName,minMark,maxMark') // assuming 'subject' is the relationship name
                ->get(['id', 'mark', 'subject_id', 'student_id']); // specify fields you need



            $formattedResults = $marks->map(function ($mark) {
                return [

                    'subject_id' => $mark->subject_id,
                    'subject_name' => $mark->subject->subjectName ?? 'N/A',
                    'min-mark' => $mark->subject->minMark,
                    'max-mark' => $mark->subject->maxMark,
                    'mark_id' => $mark->id,
                    'result' => $mark->mark,


                    // include any other fields you need
                ];
            });


            if ($marks->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No results found for you',
                ], 404);
            }

            return response()->json([
                'status'         => true,
                'message'        => 'your results my lord',
                'imported_count' => $formattedResults
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }
}
