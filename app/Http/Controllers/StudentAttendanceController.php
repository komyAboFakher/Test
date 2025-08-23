<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Parents;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Supervisor;
use App\Models\SchoolClass;
use App\Rules\validateDate;
use Illuminate\Http\Request;
use App\Models\AbsenceStudent;
use App\Models\CheckInTeacher;
use App\Models\ScheduleBrief;
use App\Models\Session;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\Catch_;
use Psy\VersionUpdater\Checker;

class StudentAttendanceController extends Controller
{
    public function uploadJustification(Request $request)
    {
        try {
            $request->validate([
                'justification' => 'required|mimes:pdf|max:2048', // max 2MB
            ]);
            //getting the user
            $user = auth()->user();
            //getting the student
            $student = Student::where('user_id', $user->id)->first();
            // Store the PDF in the `justifications/` directory inside `storage/app/public`
            $path = $request->file('justification')->store('justifications', 'public');

            // Save the path to the database
            $student->justification = $path;
            $student->save();

            return response()->json([
                'status' => true,
                'message' => 'Justification uploaded successfully.',
                'file_path' => Storage::url($path),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }

    public function checkJustifications()
    {
        try {
            //getting the all the students
            $students = Student::all();
            $justifications = $students->pluck('justification');
            //returning justifications
            return response()->json([
                'status' => true,
                'data' => $justifications
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }

    public function studentsAttendanceForm(Request $request)
    {
        try {
            // 1. Validate the incoming request
            $validateClass = Validator::make($request->all(), [
                'class' => 'required|regex:/^\d{1,2}-[A-Z]$/',
            ]);

            if ($validateClass->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation error',
                    'errors'  => $validateClass->errors(),
                ], 400); // 400 is more appropriate for validation errors
            }

            // 2. Find the class or fail
            $class = SchoolClass::where('className', $request->class)->first();

            if (!$class) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Class not found',
                ], 404);
            }

            // 3. Get all students from that class and eager load their user data
            $students = Student::with('Users')->where('class_id', $class->id)->where('expelled',0)->get();

            // 4. Transform the student data into the desired format
            $studentList = $students->map(function ($student) {
                // Check if user relation is loaded to prevent errors
                if (!$student->users) {
                    return null;
                }
                return [
                    'studentId'   => $student->id,
                    'studentName' => $student->users->full_name // Assuming 'name' is the column in your 'users' table
                ];
            })->filter(); // Use filter() to remove any students who might not have a user record

            // 5. Return the final, formatted response
            return response()->json([
                'data' => $studentList->values(), // .values() to reset keys and ensure a clean array
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => $th->getMessage(),
            ], 500); // 500 for general server errors
        }
    }

    public function studentsAttendanceSubmit(Request $request)
    {
        try {
            //validating
            $validateSession = Validator::make($request->all(), [
                'className' => 'required|regex:/^\d{1,2}-[A-Z]$/',
                'fullAttendance'=>'required|boolean',
                'session' =>  'required|integer|min:1|max:7',
                'students' => 'sometimes|array',
                'students.*.studentId' => 'sometimes|integer|exists:students,id'
            ]);
            if ($validateSession->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateSession->errors(),
                ], 422);
            }
            //getting user id to get teacher id
            $user = Auth::user();
            $teacher = Teacher::where('user_id', $user->id)->first();
            if (!$teacher) {
                return response()->json([
                    'status' => false,
                    'message' => 'Teacher not found',
                ], 404);
            }
            //first of all we wanna get any student and get their classs name
            $validatedData=$validateSession->validated();
            if(!empty($validatedData['students'])){
                $firstStudentId=$validatedData['students'][0]['studentId'];

                $classId=Student::where('id',$firstStudentId)->value('class_id');
            }else{
                $classId=SchoolClass::where('className',$request->className)->value('id');
            }
            //checking if this teacher has the session for this class
            // $today=now()->format('l');
            // $todaysBrief=ScheduleBrief::where('day',$today)->where('class_id',$classId)->first();
            // if(!$todaysBrief){
            //     return response()->json([
            //         'status'=>false,
            //         'message'=>'There are no schedules for today',
            //     ],422);
            // }
            // $teachersSession=Session::where('schedule_brief_id',$todaysBrief->id)->where('teacher_id',$teacher->id)->where('session',$request->session)->first();
            // if(!$teachersSession){
            //     return response()->json([
            //         'status'=>false,
            //         'message'=>'you are not allowed to take the report of this session IDIOT!',
            //     ],409);
            // }
    
            //checking if the session attendance has been already taken for the same teacher
            $sessionExists = CheckInTeacher::where('sessions', $request->session)->where('class_id', $classId)->whereDate('date', now())->first();
            if ($sessionExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'attendance has been already made!',
                ], 409);
            }
            //checking the order of the session attendance taking
            $temp = $request->session - 1;
            if ($request->session != 1) {
                $sessionOrder = CheckInTeacher::where('sessions', $temp)->where('class_id', $classId)->wheredate('date', now())->first();
                if (!$sessionOrder) {
                    return response()->json([
                        'status' => false,
                        'message' => 'the teacher that had the session before you did not take the report',
                    ], 409);
                }
            }
            //checking if the class has full attendance
            if($request->fullAttendance == true){
                 CheckInTeacher::create([
                    'teacher_id'      => $teacher->id,
                    'student_id'      => null, // <-- This is the main fix
                    'class_id'        => $classId,
                    'full_attendance' => true,
                    'date'            => now(),
                    'checked'         => false, // Full attendance is considered checked
                    'sessions'        => $request->session,
                ]);
            //returning success message
                return response()->json([
                    'status' => true,
                    'message' => 'check in has been logged successfully for session ' . $request->session . '! and if the is any stupid teacher that does any mistakes i will i will get his mothers id from the system',
                ], 200);
            }

            //now we need to track skips
            $lastSessionReport = CheckInTeacher::where('sessions', $temp)  
                ->where('class_id', $classId)
                ->whereDate('created_at', now()) 
                ->pluck('student_id'); 
            //now we wanna check if there is a new absent student in this session
            
            //loging absence
            foreach ($request->students as $student) {
                CheckInTeacher::create([
                    'teacher_id' => $teacher->id,
                    'student_id' => $student['studentId'],
                    'class_id' => $classId,
                    'date' => now(),
                    'checked' => false,
                    'sessions' => $request->session,
                ]);
            }
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'check in has been logged successfully for session ' . $request->session . '! and if the is any stupid teacher that does any mistakes i will get his mothers id from the system',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ], 500);
        }
    }

    public function checkStudentAbsenceReport(Request $request)
    {
        try {
            // 1. Validate the incoming request
            $validation = Validator::make($request->all(), [
                'date'  => 'required|date_format:Y-m-d',
                'class' => 'required|regex:/^\d{1,2}-[A-Z]$/',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation error',
                    'errors'  => $validation->errors(),
                ], 422);
            }

            // 2. Find the class or fail
            $class = SchoolClass::where('className', $request->class)->first();
            if (!$class) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Class not found',
                ], 404);
            }

            // 3. Get all student IDs for the specified class
            $studentIds = Student::where('class_id', $class->id)->pluck('id');

            // 4. Fetch attendance records for those students on the given date
            // Eager load relationships to get student names efficiently
            $absenceRecords = CheckInTeacher::with('student.users')
                ->where('date', $request->date)
                ->whereIn('student_id', $studentIds) // Use whereIn with the student IDs
                ->get();

            // 5. Group the records by session and transform them (this part remains the same)
            $groupedData = $absenceRecords->groupBy('sessions')->map(function ($recordsInSession, $sessionNumber) {
                return [
                    'session'  => $sessionNumber,
                    'students' => $recordsInSession->map(function ($record) {
                        if (!$record->student || !$record->student->users) {
                            return null;
                        }
                        return [
                            'studentId' => $record->student->id,
                            'full_name' => $record->student->users->full_name,
                        ];
                    })->filter()->values(),
                ];
            });

            // 6. Return the final, formatted data
            return response()->json([
                'data' => $groupedData->values(),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function checkStudentWarnings(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'studentId' => 'required|exists:students,id'
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validation->errors(),
                ], 422);
            }
            $studentWarnings = AbsenceStudent::where('student_id', $request->studentId)->first();
            //returning data
            return response()->json([
                'status' => true,
                'data' => $studentWarnings,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function submitDailyReports()
    {
        try {
            //getting all the classes
            $classes=SchoolClass::all();
            foreach($classes as $class){
                $check=CheckInTeacher::where('class_id',$class->id)->where('checked',false)->where('sessions',7)->first();
                if(!$check){
                    return response()->json([
                        'status'=>false,
                        'message'=>'the class ' .$class->className. ' doesnt have all the 7 sessions',
                    ],422);
                }
            }
            // Get all unprocessed absence records
            $allAbsences = CheckInTeacher::where('checked', false)->get();

            // Group by student and date
            $grouped = $allAbsences->groupBy(function ($item) {
                return $item->student_id . '-' . $item->date; // Group by student and date
            });

            foreach ($grouped as $key => $records) {
                if ($records->count() == 7) {
                    $studentId = $records->first()->student_id;

                    // Get the student's absence info
                    $absence = AbsenceStudent::where('student_id', $studentId)->first();


                    if ($absence) {
                        $absence->absence_num -= 1;

                        if ($absence->absence_num <= 0) {
                            $absence->warning += 1;
                            $absence->absence_num = 5;
                            //here we wanna call warning notification
                        }

                        $absence->save();
                    }

                }
                // Mark those 7 records as checked
                foreach ($records as $rec) {
                    $rec->checked = true;
                    $rec->save();
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Daily absence report submitted successfully!',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function checkStudentAttendanceHistory(Request $request)
    {
        try {
            //getting the user
            $user = Auth::user();
            //getting the student
            $student = Student::where('user_id', $user->id)->first();
            //getting data from reports
            $dates = CheckInTeacher::where('student_id', $student->id)->first();
            //getting data from warnings
            $warnings = AbsenceStudent::where('student_id', $student->id)->first();
            //returning data
            return response()->json([
                'status' => true,
                'dates' => $dates,
                'warnings' => $warnings,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function incrementStudentAbsence(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'studentId' => 'required|exists:students,id',
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }
            //getting the absence 
            $absence = absenceStudent::where('student_id', $request->studentId)->first();
            //increasing by one
            if ($absence->absence_num != 5) {
                $absence->absence_num += 1;
            } elseif ($absence->absence_num == 5 && $absence->warning != 0) {
                $absence->warning -= 1;
                $absence->absence_num = 5;
            }else{
                return response()->json([
                    'status'=>false,
                    'message'=>'we cant increase the students absence num due to school policy!',
                ],422);
            }
            $absence->save();
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'the absence num has been increased by one successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }


    public function decrementStudentAbsence(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'studentId' => 'required|exists:students,id',
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }
            //getting the absence 
            $absence = absenceStudent::where('student_id', $request->studentId)->first();
            //increasing by one
            if ($absence->absence_num > 1) {
                $absence->absence_num -= 1;
            } elseif ($absence->absence_num == 1 && $absence->warning != 3) {
                $absence->warning += 1;
                $absence->absence_num = 5;
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'this student already have 3 warnings we cant increase it he has succeeded the limit',
                ], 422);
            }

            $absence->save();
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'the absence num has been descreased by one successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function searchStudentById(Request $request)
    {
        try {
            //validation 
            $validation = Validator::make($request->all(), [
                'studentId' => 'required|integer|exists:students,id',
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ]);
            }
            //we wanna get the student
            $student = Student::where('id', $request->studentId)->first();
            //now we wanna get it as user data
            $user = User::where('id', $student->user_id)->first();
            //returning data
            return response()->json([
                'status' => true,
                'user' => $user,
                'student' => $student,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function showAllStudents()
    {
        try {
            //getting all students
            $students = Student::all();
            //returning data
            return response()->json([
                'status' => true,
                'data' => $students,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function showParentSon()
    {
        try {
            //first of all we wanna get the suer
            $user = Auth::user();
            //now we wanna get the parent
            $parent = Parents::where('user_id', $user->id)->first();
            //now we wanna get the student informations based on his id in $parent
            $student = Student::where('id', $parent->student_id)->first();
            //now we wanna get absence days
            $absences = CheckInTeacher::where('student_id', $parent->student_id)->get();
            //now we wanaa get warnings
            $warnings = AbsenceStudent::where('student_id', $parent->student_id)->first();

            //returning data
            return response()->json([
                'status' => true,
                'student' => $student,
                'absences' => $absences,
                'warnings' => $warnings,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getInfo()
    {
        try {
            //getting user
            $user = Auth::user();
            //getting data based on role
            $dataArray = [];
            if ($user->role == 'student') {
                $roleData = Student::where('user_Id', $user->id)->first();
                $class = schoolClass::where('id', $roleData->class_id)->first();
                $dataArray[0] = $roleData;
                $dataArray[1] = $class;
            } elseif ($user->role == 'teacher') {
                $roleData = Teacher::where('user_Id', $user->id)->first();
            } elseif ($user->role == 'supervisor') {
                $roleData = Supervisor::where('user_Id', $user->id)->first();
            }
            //returning data
            return response()->json([
                'status' => true,
                'user' => $user,
                'roleData' => $dataArray,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    
    }

    public function getStudentAbsenceDates(){
        try{
            //getting the user
            $user=Auth::user();
            //getting the student
            $student=Student::where('user_id',$user->id)->first();
            //getting all the reports from the check_in_teachres table
            $reports=CheckInTeacher::where('student_id',$student->id)->select('student_id','date','sessions')->get()->groupBy('date');
            //we need a array to collect the number of the sessions that this student was absent in (if its 7 then this student was absent in this day)
            $absentDates=[];
            foreach($reports as $date => $dayReport){
                $dayCount=count($dayReport);
                if($dayCount == 7){
                    $absentDates[]=$date;
                }
            }
            //returning success message
            return response()->json([
                'status'=>true,
                'absentDates'=>$absentDates,
            ]);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
                'line'=>$th->getLine(),
            ],500);
        }
    }
}
