<?php

namespace App\Http\Controllers;

use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\schoolClass;
use App\Models\FullMarkFile;
use App\Models\TeacherClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Events\TransactionRolledBack;

class marksController extends Controller
{

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

    //____________________________________________________________________________________________________

    public function upload(Request $request)
    {
        try {
            $currentUser = auth()->user()->teacher;

            //validation the excel file
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls',
            ], [
                'excel_file' => 'Only xlsx and xls files are accepted !!'
            ]);

            // Allowed values for semester
            $allowedSemesters = ['First', 'Second'];
            // Allowed values for exam type
            $allowedExamTypes = ['final', 'mid-term', 'quizz'];
            // count for the missing students management
            $count = 0;
            $dataRows = [];
            $missingStudents = [];
            $updatedStudents = [];
            $updatedStudentsNames = [];


            // open the excel and read it
            $file = $request->file('excel_file');
            $rows = (new \Rap2hpoutre\FastExcel\FastExcel())->import($file->getRealPath());

            // store the excel rows into array 
            $rowsArr = [];
            foreach ($rows as $row) {
                $row = array_change_key_case((array)$row, CASE_LOWER);
                $rowsArr[] = $row;
            }

            // Extract and validate data then skip the header row
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

                // dataRows array to use in the missing student management
                $studentID = (int)$row['student id'];
                $student = Student::select('class_id')->where('id', $studentID)->first();
                $classID = $student->class_id;
                $dataRows[] = $row;
                $count++;
            }

            // if the excel is empty
            if (empty($dataRows)) {
                $firstFewRows = array_slice($rowsArr, 0, 5);
                throw new \Exception('No student records found. First few rows: ' . json_encode($firstFewRows));
            }

            // missing students management
            $expectedStudentIDs = Student::where('class_id', $classID)->pluck('id')->toArray();
            $excelStudentIDs = array_map(fn($row) => (int)$row['student id'], $dataRows);
            $missingIDs = array_diff($expectedStudentIDs, $excelStudentIDs);

            // getting the missing students names
            foreach ($missingIDs as $missingID) {
                $student = Student::findOrFail($missingID);
                $fullName = trim($student->Users->name . ' ' . $student->Users->middleName . ' ' . $student->Users->lastName . ' with the student id: ' . $student->id . ' belongs to class name ' . $student->SchoolClass->className);
                $missingStudents[] = $fullName;
            }

            if ($missingStudents) {
                return response()->json([
                    "message" => "those students are missied from your excel, you need to return them to upload the marks: ",
                    "missing_students" => array_values($missingStudents)
                ]);
            }



            // Insert marks
            DB::beginTransaction();

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
                $maxMark = Subject::where('id', $teacherClass->subject_id)->value('maxMark');

                // check if the mark is empty
                $mark = isset($row['mark']) ? (string)$row['mark'] : '';
                if (!$mark) {
                    return response()->json([
                        'status' => false,
                        'message' => "Mark cannot be empty for student ID {$studentID} !!",
                    ], 422);
                }

                // check if the mark is negative number
                if ($mark < 0) {
                    return response()->json([
                        "message" => "mark can not be a negative number !!"
                    ], 422);
                }
                // check if the mark is greater than max mark
                if ($mark > $maxMark) {
                    return response()->json([
                        "message" => "mark can not be greater than " . $maxMark
                    ], 422);
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
                    'type' => $type,
                    'semester' => $semester,
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
                        $studentID = $existing->student_id;
                        $updatedStudents[] = $studentID;
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
            }

            // getting the updated students names
            foreach ($updatedStudents as $updatedStudent) {
                $student = Student::findOrfail($updatedStudent);
                $fullName = trim($student->Users->name . ' ' . $student->Users->middleName . ' ' . $student->Users->lastName . ' with the student id: ' . $student->id . ' belongs to class name ' . $student->SchoolClass->className);
                $updatedStudentsNames[] = $fullName;
            }


            DB::commit();

            // Store file locally 
            $directory = 'full_mark_excels';
            Storage::disk('public')->makeDirectory($directory);
            $fileName = $file->getClientOriginalName();
            $filePath = $file->storeAs($directory, $fileName, 'public');
            $fileUrl = asset("storage/{$directory}/{$fileName}");


            // store the path for the teacher
            FullMarkFile::firstOrCreate(
                [
                    'teacher_id' => $currentUser->id,
                    'class_id' => $classID,
                    'subject_id' => $teacherClass->subject_id,
                    'file_name' => $fileName,
                    'file_path' => $filePath,

                ]
            );


            // Final response
            return response()->json([
                'status' => true,
                'message' => 'Marks uploaded successfully!',
                'file_url' => $fileUrl,
                'imported_count' => count($dataRows),
                'updated_students' => count($updatedStudents),
                'updated_students_names' => array_values($updatedStudentsNames)
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    //______________________________________________________________________________________
    public function index(Request $request)
    {
        try {

            $currentUser = auth()->user()->teacher->id;
            $request->validate([
                'class_id' => 'required|exists:classes,id',
            ]);

            $subjectID = TeacherClass::where('teacher_id', $currentUser)->where('class_id', $request->class_id)->firstOrFail();

            if (!$subjectID) {
                return response()->json([
                    "message" => "teacher is not assigned to this class "
                ], 422);
            }

           


            $files = FullMarkFile::where('teacher_id', $currentUser)
                ->where('class_id', $request->class_id)
                ->where('subject_id', $subjectID->subject_id)
                ->latest()
                ->get()
                ->map(function ($file) {
                    return [
                        'file_name' => $file->file_name,
                        'url' => asset("storage/{$file->file_path}"),
                        'uploaded_at' => $file->created_at->toDateTimeString(),
                    ];
                });

            if (!$files) {
                return response()->json([
                    "message" => "there are no uploaded files lately !!"
                ], 422);
            }

            return response()->json([
                "message" => $files
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    //______________________________________________________________________________________________-

    public function getTeacherClasses()
    {
        try {
            $user = Auth::user();
            $teacher = Teacher::where('user_id', $user->id)->first();
            $teacherClasses = TeacherClass::select(
                'teacher_classes.teacher_id',
                'teacher_classes.class_id',
                'classes.className',
                'teacher_classes.subject_id',
                'subjects.subjectName'
            )
                ->join('classes', 'teacher_classes.class_id', '=', 'classes.id')
                ->join('subjects', 'teacher_classes.subject_id', '=', 'subjects.id')
                ->where('teacher_id', $teacher->id)
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

    //___________________________________________________________________________________________

    public function getMarksProfile(Request $request)
    {
        try {
            $validator = Validator::Make(
                $request->all(),
                [

                    'student_id' => 'required|integer|exists:students,id',
                    'semester' => 'required|string|in:First,Second'

                ],
                [
                    'semester.in' => 'the semester must be First or Second!!'
                ]
            );


            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $marks = Mark::where('student_id', $request->student_id)
                ->where('semester', $request->semester)
                ->get()
                ->groupBy('type')  // Group by mark type
                ->map(function ($group) {
                    return $group->map(function ($mark) {
                        return [
                            //'id' => $mark->id,
                            //'class_id' => $mark->class_id,
                            //'teacher_id' => $mark->teacher_id,
                            //'student_id' => $mark->student_id,
                            //'subject_id' => $mark->subject_id,
                            'subject_name' => $mark->Subject->subjectName,
                            'min_mark' => $mark->Subject->minMark,
                            'max_mark' => $mark->Subject->maxMark,
                            'mark' => $mark->mark,
                            'success' => $mark->success,
                            'created_at' => $mark->created_at,
                            'updated_at' => $mark->updated_at,
                        ];
                    });
                });

            return response()->json([
                'status' => true,
                'message' => $marks
            ]);



            return response()->json([
                "status" => true,
                "message" => $marks
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }
    //___________________________________________________________________________________________
    public function getClassMarks(Request $request)
    {
        try {

            $currentUser = auth()->user()->teacher->id;
            $validator = Validator::make($request->all(), [
                'class_id' => 'required|integer|exists:classes,id',
                'semester' => 'required|string|in: First,Secnod'
            ], [
                'semester.in' => 'semester must be First or Second'
            ]);

            $subjectID = TeacherClass::where('teacher_id', $currentUser)->where('class_id', $request->class_id)->firstOrFail();

            $marks = mark::with('Students.Users')
                ->where('class_id', $request->class_id)
                ->where('semester', $request->semester)
                ->where('subject_id', $subjectID->subject_id)
                ->get()
                ->groupBy('type')
                ->map(function ($group) {
                    return $group->map(function ($mark) {
                        return [

                            'student_id' => $mark->student_id,
                            'teacher_id' => $mark->teacher_id,
                            'full_name' => trim($mark->Students->Users->name . ' ' . $mark->Students->Users->middleName . ' ' . $mark->Students->Users->lastName),
                            'subject_name' => $mark->Subject->subjectName,
                            'min_mark' => $mark->Subject->minMark,
                            'max_mark' => $mark->Subject->maxMark,
                            'mark' => $mark->mark,
                            'success' => $mark->success,
                            'created_at' => $mark->created_at,
                            'updated_at' => $mark->updated_at,
                        ];
                    });
                });

            return response()->json([
                'status' => true,
                'message' => $marks
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }















    //___________________________________________________________________________________________
    // this is the last version of the api upload marks, written by copilot, clean, Readable, maintainable
    /* 
    private function validateExcelFile(Request $request)
    {
        return $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ], [
            'excel_file' => 'Only xlsx and xls files are accepted !!'
        ])['excel_file'];
    }

    private function parseExcelFile($file)
    {
        $rows = (new \Rap2hpoutre\FastExcel\FastExcel())->import($file->getRealPath());
        return array_map(fn($row) => array_change_key_case((array) $row, CASE_LOWER), $rows->toArray());
    }

    private function extractValidDataRows(array $rowsArr)
    {
        $dataRows = [];
        foreach ($rowsArr as $row) {
            if (isset($row['student id']) && is_numeric($row['student id'])) {
                $dataRows[] = $row;
            }
        }
        return $dataRows;
    }

    private function getClassIdFromFirstRow(array $dataRows)
    {
        return Student::where('id', $dataRows[0]['student id'])->value('class_id');
    }

    private function detectMissingStudents($classID, array $dataRows)
    {
        $expectedIDs = Student::where('class_id', $classID)->pluck('id')->toArray();
        $excelIDs = array_map(fn($row) => (int)$row['student id'], $dataRows);
        $missingIDs = array_diff($expectedIDs, $excelIDs);

        return array_map(function ($id) {
            $student = Student::findOrFail($id);
            return $this->formatStudentName($student);
        }, $missingIDs);
    }

    private function processMarkUpdates($currentUser, array $dataRows)
    {
        $updatedStudents = [];

        foreach ($dataRows as $row) {
            $studentID = (int)$row['student id'];
            $student = Student::findOrFail($studentID);
            $classID = $student->class_id;

            $teacherClass = TeacherClass::where([
                'teacher_id' => $currentUser->id,
                'class_id' => $classID
            ])->firstOrFail();

            $mark = trim((string)($row['mark'] ?? ''));
            if ($mark === '') {
                throw new \Exception("Mark cannot be empty for student ID {$studentID}.");
            }

            $semester = ucfirst(strtolower($row['semester'] ?? ''));
            $type = strtolower($row['type'] ?? '');

            $allowedSemesters = ['First', 'Second'];
            $allowedExamTypes = ['final', 'mid-term', 'quizz'];

            if (!in_array($semester, $allowedSemesters)) {
                throw new \Exception("Invalid semester: {$semester}");
            }
            if (!in_array($type, $allowedExamTypes)) {
                throw new \Exception("Invalid exam type: {$type}");
            }

            $minMark = Subject::findOrFail($teacherClass->subject_id)->minMark;
            $success = ($mark >= $minMark);

            $existing = Mark::where([
                'class_id' => $classID,
                'teacher_id' => $currentUser->id,
                'student_id' => $studentID,
                'subject_id' => $teacherClass->subject_id,
                'semester' => $semester,
                'type' => $type
            ])->first();

            if ($existing) {
                $needsUpdate = $existing->mark != $mark;
                if ($needsUpdate) {
                    $existing->update([
                        'mark' => $mark,
                        'success' => $success,
                        'semester' => $semester,
                        'type' => $type
                    ]);
                    $updatedStudents[] = $studentID;
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
        }

        return [array_unique($updatedStudents), count($dataRows)];
    }

    private function mapUpdatedStudentNames(array $updatedStudents)
    {
        return array_map(function ($id) {
            $student = Student::findOrFail($id);
            return $this->formatStudentName($student);
        }, $updatedStudents);
    }

    private function formatStudentName($student)
    {
        $user = $student->Users;
        return trim("{$user->name} {$user->middleName} {$user->lastName}") .
            " with the student id: {$student->id} belongs to class name {$student->SchoolClass->className}";
    }

    private function storeUploadedFile($file)
    {
        $directory = 'full_mark_excels';
        Storage::disk('public')->makeDirectory($directory);

        $fileName = $file->getClientOriginalName();
        $filePath = $file->storeAs($directory, $fileName, 'public');

        return asset("storage/{$directory}/{$fileName}");
    }
  

    // main function
    
    public function uploads(Request $request)
    {
        try {
            DB::beginTransaction();

            $currentUser = auth()->user()->teacher;

            $validatedFile = $this->validateExcelFile($request);

            $rowsArr = $this->parseExcelFile($validatedFile);

            $dataRows = $this->extractValidDataRows($rowsArr);

            if (empty($dataRows)) {
                throw new \Exception('No student records found. First few rows: ' . json_encode(array_slice($rowsArr, 0, 5)));
            }

            $classID = $this->getClassIdFromFirstRow($dataRows);
            $missingStudents = $this->detectMissingStudents($classID, $dataRows);

            if ($missingStudents) {
                return response()->json([
                    'message' => 'Those students are missing from your Excel file:',
                    'missing_students' => $missingStudents,
                ]);
            }

            [$updatedStudents, $importedCount] = $this->processMarkUpdates($currentUser, $dataRows);

            $updatedStudentsNames = $this->mapUpdatedStudentNames($updatedStudents);

            DB::commit();

            $fileUrl = $this->storeUploadedFile($validatedFile);

            return response()->json([
                'status' => true,
                'message' => 'Marks uploaded successfully!',
                'file_url' => $fileUrl,
                'imported_count' => $importedCount,
                'updated_students' => count($updatedStudents),
                'updated_students_names' => $updatedStudentsNames
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    
    */


    // older versions of upload marks by me

    /*

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

    public function uploadMarkExcelCheat2(Request $request)
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

            // just for debugging

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
                    'type' => $type,
                    'semester' => $semester,
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
    
    */
}


// MY LIFE IS WORTHLESS, DAY IN AND DAY OUT I LOOK FOR REASON OF MY LIVING, AND NEVER FIND, SUCH SHAME!!
//K0M@YY