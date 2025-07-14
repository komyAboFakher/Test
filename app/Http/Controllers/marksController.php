<?php

namespace App\Http\Controllers;

use App\Models\Mark;
use App\Models\Subject;
use App\Models\schoolClass;
use App\Models\Student;
use App\Models\TeacherClass;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class marksController extends Controller
{
    // MAJD //get the teacher's classses based on teacher id

    public function getAllTeacherInfo()
    {


        try {

            $currentUser = auth()->user()->teacher->id;
            $teacherClasses = TeacherClass::select(
                'teacher_classes.teacher_id',
                'teacher_classes.class_id',
                'classes.className',
                'teacher_classes.subject_id',
                'subjects.subjectName'
            )
                ->join('classes', 'teacher_classes.class_id', '=', 'classes.id')
                ->join('subjects', 'teacher_classes.subject_id', '=', 'subjects.id')
                ->where('teacher_id', $currentUser)
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

    public function getEmptyExcelCheatForMarks(Request $request)
    {


        try {



            $currentUser = auth()->user()->teacher;


            $validator = Validator::make(
                $request->all(),
                [
                    'classID' => 'required|integer|exists:classes,id',
                    'semester' => 'required|string|in:First,Second',
                    'type' => 'required|string|in:final,mid-term,quizz',
                ],
                [
                    'type' => 'type must be final or mid-term or quizz !!',
                    'semester' => 'semster must be First or Second only !! ',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors(),
                ], 422);
            }

            // get the students with the specific class with their needed info.

            $SchoolClass = SchoolClass::with([
                'students.Users' => function ($query) {
                    $query->select('id', 'name', 'middleName', 'lastName');
                }
            ])->findOrFail($request->classID);


            //get the the subject name for the title of the excel file

            $teacherClass = TeacherClass::where('teacher_id', $currentUser->id)
                ->where('class_id', $request->classID)
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
                    'semester' => $request->semester,
                    'type' => $request->type
                ]);
            }

            // Return FastExcel-generated Excel file for download

            // Generate the file name 
            $fileName = "{$subjectName}_{$SchoolClass->className}_{$request->type}_{$request->semester}.xlsx";

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

            
            $fileUrl = asset("storage/$relativePath");




            return response()->json([
                'status' => true,
                'file_url' => $fileUrl,
                'message' => 'marks file  generated successfully !!',

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

            $currentUser = auth()->user()->teacher;

            $request->validate(
                [
                    'excel_file' => 'required|file|mimes:xlsx,xls',
                    'class_id'   => 'required|integer|exists:classes,id',
                ],
                [
                    'excel_file' => 'only xlsx and xls wil be accepted !!'
                ]
            );

            $classID   = $request->input('class_id');

            // creating a path for the uploaded file !!!





            // check the min mark in the subjects table to determine the success mark
            $teacherClass = TeacherClass::where('teacher_id', $currentUser->id)
                ->where('class_id', $classID)
                ->first();

            $minMark = null;

            if ($teacherClass) {
                $minMark = Subject::where('id', $teacherClass->subject_id)->value('minMark');
            }

            //$minMark = Subject::where('id', $subjectID)->value('minMark');

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
                //check if the mark is empty, with no value
                //if (!$mark) {
                //    return response()->json([
                //        'status' => false,
                //        'message' => 'the mark cannot be empty !!',
                //    ]);
                //}


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
                    'teacher_id' => $currentUser->id,
                    'student_id' => $studentID,
                    'subject_id' => $teacherClass->subject_id
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
                        'teacher_id' => $currentUser->id,
                        'student_id' => $studentID,
                        'subject_id' => $teacherClass->subject_id,
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

    //____________________________________________________________________________________________________

    public function upload(Request $request)
    {
        try {
            $currentUser = auth()->user()->teacher;

            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls',
            ], [
                'excel_file' => 'Only xlsx and xls files are accepted !!'
            ]);

            // Allowed values from DB logic
            $allowedSemesters = ['First', 'Second'];
            $allowedExamTypes = ['final', 'mid-term', 'quizz'];
            $count = 0;
            $dataRows = [];

            // open the excel and read it
            $file = $request->file('excel_file');
            $rows = (new \Rap2hpoutre\FastExcel\FastExcel())->import($file->getRealPath());

            $rowsArr = [];
            foreach ($rows as $row) {
                $row = array_change_key_case((array)$row, CASE_LOWER);
                $rowsArr[] = $row;
            }

            // Extract and validate data
            $isFirstRow = true;
            foreach ($rowsArr as $row) {
                if ($isFirstRow && isset($row['student id']) && strtolower(trim($row['student id'])) === 'student id') {
                    $isFirstRow = false;
                    continue;
                }

                $isFirstRow = false;
                if (!isset($row['student id']) || !is_numeric($row['student id'])) {
                    throw new \Exception("Invalid student ID in row.");
                }

                $dataRows[] = $row;
                $count++;
            }

            if (empty($dataRows)) {
                $firstFewRows = array_slice($rowsArr, 0, 5);
                throw new \Exception('No student records found. First few rows: ' . json_encode($firstFewRows));
            }

            // Find expected students from teacher's assigned classes
            $teacherClassIDs = TeacherClass::where('teacher_id', $currentUser->id)->pluck('class_id');
            $expectedStudentIDs = Student::whereIn('class_id', $teacherClassIDs)->pluck('id')->toArray();
            $excelStudentIDs = array_map(fn($row) => (int)$row['student id'], $dataRows);
            $missingIDs = array_diff($expectedStudentIDs, $excelStudentIDs);

            // Insert marks
            DB::beginTransaction();
            $changedStudentIds = [];

            foreach ($dataRows as $row) {
                $studentID = (int)$row['student id'];

                $student = Student::select('class_id')->where('id', $studentID)->first();
                if (!$student || !$student->class_id) {
                    throw new \Exception("Missing or invalid class ID for student ID: {$studentID}");
                }

                $classID = $student->class_id;

                $teacherClass = TeacherClass::where('teacher_id', $currentUser->id)
                    ->where('class_id', $classID)
                    ->first();

                if (!$teacherClass) {
                    throw new \Exception("Teacher not assigned to class ID: {$classID} (student ID: {$studentID})");
                }

                $minMark = Subject::where('id', $teacherClass->subject_id)->value('minMark');

                $mark = isset($row['mark']) ? (string)$row['mark'] : '';
                if (!$mark) {
                    return response()->json([
                        'status' => false,
                        'message' => "Mark cannot be empty for student ID {$studentID} !!",
                    ]);
                }

                $semester = ucfirst(strtolower($row['semester'] ?? ''));
                if (!in_array($semester, $allowedSemesters)) {
                    throw new \Exception("Invalid semester: {$row['semester']}. Allowed: " . implode(', ', $allowedSemesters));
                }

                $type = strtolower($row['type'] ?? '');
                if (!in_array($type, $allowedExamTypes)) {
                    throw new \Exception("Invalid exam type: {$row['type']}. Allowed: " . implode(', ', $allowedExamTypes));
                }

                $success = ($mark >= $minMark);

                $existing = Mark::where([
                    'class_id' => $classID,
                    'teacher_id' => $currentUser->id,
                    'student_id' => $studentID,
                    'subject_id' => $teacherClass->subject_id
                ])->first();

                if ($existing) {
                    $needsUpdate = ($existing->mark != $mark || $existing->semester != $semester || $existing->type != $type);
                    if ($needsUpdate) {
                        $existing->update([
                            'mark' => $mark,
                            'success' => $success,
                            'semester' => $semester,
                            'type' => $type
                        ]);
                    }
                } else {
                    Mark::create([
                        'class_id' => $classID,
                        'teacher_id' => $currentUser->id,
                        'student_id' => $studentID,
                        'subject_id' => $teacherClass->subject_id,
                        'mark' => $mark,
                        'success' => $success,
                        'semester' => $semester,
                        'type' => $type
                    ]);
                }

                $changedStudentIds[] = $studentID;
            }

            DB::commit();

            // Store file
            $directory = 'full_mark_excels';
            Storage::disk('public')->makeDirectory($directory);
            $fileName = $file->getClientOriginalName();
            $filePath = $file->storeAs($directory, $fileName, 'public');
            $fileUrl = asset("storage/{$directory}/{$fileName}");

            // Final response
            return response()->json([
                'status' => true,
                'message' => 'Marks uploaded successfully!',
                'file_url' => $fileUrl,
                'imported_count' => count($dataRows),
                'missing_student_ids' => array_values($missingIDs), // Clean numeric array
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////



    public function studentGetResult(Request $request)
    {


        try {

            $currentUser = auth()->user()->Student->id;
            //$studentID = $request->student->id;

            $validator = Validator::make($request->all(), [
                //'student_id' => 'required|integer',
                'semester' => 'required|string|in:First,Second',
                'type' => 'required|string|in:final,mid-term,quizz',
                'year' => 'required|digits:4|integer|min:2000|max:' . now()->year,

            ], [
                'semester.in' => 'semester must be First or Second !!',
                'type.in' => 'type must be final or mid-term or quizz  !!',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }



            $marks = Mark::where('student_id', $currentUser)
                ->where('type', $request->type)
                ->where('semester', $request->semester)
                ->whereYear('created_at', $request->year)
                ->with('subject:id,subjectName,minMark,maxMark')
                ->get(['id', 'mark', 'subject_id', 'student_id', 'type', 'semester']);



            $formattedResults = $marks->map(function ($mark) {
                return [

                    //'subject_id' => $mark->subject_id,
                    'subject_name' => $mark->subject->subjectName,
                    //'semester' => $mark->semester,
                    //'type' => $mark->type,
                    'min-mark' => $mark->subject->minMark,
                    'max-mark' => $mark->subject->maxMark,
                    //'mark_id' => $mark->id,
                    'result' => $mark->mark,
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
                'message'        => 'your results my lord :',
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


// MY LIFE IS WORTHLESS, DAY IN AND DAY OUT I LOOK FOR REASON OF MY LIVING, AND NEVER FIND, SUCH SHAME!!
//K0M@YY