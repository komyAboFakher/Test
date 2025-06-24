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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentAttendanceController extends Controller
{
        public function uploadJustification(Request $request){
        try{
            $request->validate([
                'justification' => 'required|mimes:pdf|max:2048', // max 2MB
            ]);
            //getting the user
            $user = auth()->user();
            //getting the student
            $student=Student::where('user_id',$user->id)->first();
            // Store the PDF in the `justifications/` directory inside `storage/app/public`
            $path = $request->file('justification')->store('justifications', 'public');

            // Save the path to the database
            $student->justification = $path;
            $student->save();

            return response()->json([
                'status' => true,
                'message' => 'Justification uploaded successfully.',
                'file_path' => Storage::url($path),
            ],200);

    }catch(\Throwable $th){
        return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
        ],404);
    }
}

public function checkJustifications(){
    try{
        //getting the all the students
        $students = Student::all();
        $justifications = $students->pluck('justification');
        //returning justifications
        return response()->json([
            'status'=>true,
            'data'=>$justifications
        ]);
    }catch(\Throwable $th){
        return response()->json([
            'status'=>false,
            'message' => $th->getMessage(),
        ],404);
    }
}
public function studentsAttendanceForm(Request $request){
    try{
    //validating
        $validateClass=Validator::make($request->all(),[
            'class' =>  'regex:/^\d{1,2}-[A-Z]$/',
        ]);
        if($validateClass->fails()){
            return response()->json([
                    'status'=>false,
                    'message'=>'validation error',
                    'errors'=>$validateClass->errors(),
            ],404);
        }
        //getting the class id
        $class = SchoolClass::where('className', $request->class)->first();
        if (!$class) {
            return response()->json([
                'status' => false,
                'message' => 'Class not found',
            ], 404);
        }
        //getting students
        $students=Student::where('class_id',$class->id)->get();
        //returning data
        return response()->json([
            'status'=>true,
            'data'=>$students,
        ],200);

    }catch(\Throwable $th){
        return response()->json([
            'status'=>false,
            'message'=>$th->getMessage()
        ],404);
    }
}

public function studentsAttendanceSubmit(Request $request){
    try{
        //validating
        $validateSession=Validator::make($request->all(),[
            'session' =>  'required|integer|min:1|max:7',
            'students' => 'required|array',
            'students.*.studentId' => 'required|integer|exists:students,id',
        ]);
        if($validateSession->fails()){
            return response()->json([
                    'status'=>false,
                    'message'=>'validation error',
                    'errors'=>$validateSession->errors(),
            ],422);
        }
        //getting user id to get teacher id
        $user=Auth::user();
        $teacher=Teacher::where('user_id',$user->id)->first();
        if (!$teacher) {
            return response()->json([
                'status' => false,
                'message' => 'Teacher not found',
            ], 404);
        }
        //checking if the session attendance has been already taken for the same teacher
        $sessionExists=CheckInTeacher::where('sessions',$request->session)->where('teacher_id',$teacher->id)->first();
        if($sessionExists){
            return response()->json([
                'status'=>false,
                'message'=>'attendance has been already made!',
            ],409);
        }
        //checking the order of the session attendance taking
        $temp=$request->session-1;
        if($request->session!=1){
        $sessionOrder=CheckInTeacher::where('sessions',$temp)->first();
            if(!$sessionOrder){
                return response()->json([
                    'status'=>false,
                    'message'=>'you are not following the order!',
                ],409);
            }
        }

        //loging absence
        foreach($request->students as $student){
            CheckInTeacher::create([
                'teacher_id'=>$teacher->id,
                'student_id'=>$student['studentId'],
                'date'=>now(),
                'checked'=>false,
                'sessions'=>$request->session,
            ]);
        }
        //returning success message
        return response()->json([
            'status'=>true,
            'message'=>'check in has been logged successfully for session '.$request->session.'!',
        ],200);
    }catch(\Throwable $th){
        return response()->json([
            'status'=>false,
            'message'=>$th->getMessage(),
            'line'=>$th->getLine(),
            'trace'=>$th->getTraceAsString()
        ],500);
    }
    }

    public function checkStudentAbsenceReport(Request $request){
        try{
        //validation
        $validation=Validator::make($request->all(),[
            'date' => 'required|date_format:Y-m-d',
            'class' => 'regex:/^\d{1,2}-[A-Z]$/',
        ]);

        if($validation->fails()){
            return response()->json([
                    'status'=>false,
                    'message'=>'validation error',
                    'errors'=>$validation->errors(),
            ],422);
        }
        //first of all getting the class id
        $class=SchoolClass::where('className',$request->class)->first();
        if (!$class) {
            return response()->json([
                'status' => false,
                'message' => 'Class not found',
            ], 404);
        }

        //secondly getting students of this class
        $students=Student::where('class_id',$class->id)->pluck('id');
        //now we wanna get the the attendance from the check in teacher table based on date and students that we got earlier
        $absence=CheckInTeacher::where('date',$request->date)->whereIn('student_id',$students)->get();        
        //returning data
        return response()->json([
            'status'=>true,
            'data'=>$absence,
        ],200);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    }

    public function checkStudentWarnings(Request $request){
        try{
            //validation
            $validation=Validator::make($request->all(),[
                'studentId' =>'required|exists:students,id'
            ]);
            
        if($validation->fails()){
            return response()->json([
                    'status'=>false,
                    'message'=>'validation error',
                    'errors'=>$validation->errors(),
            ],422);
        }
        $studentWarnings=AbsenceStudent::where('student_id',$request->studentId)->first();
        //returning data
        return response()->json([
            'status'=>true,
            'data'=>$studentWarnings,
        ],200);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    }

    public function submitDailyReports()
    {
        try {
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

                    // Mark those 7 records as checked
                    foreach ($records as $rec) {
                        $rec->checked = true;
                        $rec->save();
                    }
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

    public function checkStudentAttendanceHistory(Request $request){
        try{
            //getting the user
            $user=Auth::user();
            //getting the student
            $student=Student::where('user_id',$user->id)->first();
            //getting data from reports
            $dates=CheckInTeacher::where('student_id',$student->id)->first();
            //getting data from warnings
            $warnings=AbsenceStudent::where('student_id',$student->id)->first();
        //returning data
        return response()->json([
            'status'=>true,
            'dates'=>$dates,
            'warnings'=>$warnings,
        ],200);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    }

    public function incrementStudentAbsence(Request $request){
        try{
            //validation
            $validation=Validator::make($request->all(),[
                'studentId' =>'required|exists:students,id',
            ]);
            if($validation->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validation->errors(),
                ],422);
            }
            //getting the absence 
            $absence=absenceStudent::where('student_id',$request->studentId)->first();
            //increasing by one
            if($absence->absence_num != 5){
                $absence->absence_num+=1;
            }else{
                return response()->json([
                    'status'=>false,
                    'message'=>'the absence num to this student is 5 already we can increase it due to school policy',
                ],422);
            }
            $absence->save();
            //returning success message
            return response()->json([
                'status'=>true,
                'message'=>'the absence num has been increased by one successfully!',
            ]);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        } 
    }

    public function searchStudentByName(Request $request){
        try{
        //validation 
        $validation=Validator::make($request->all(),[
            'name'=>'Required|string',
            'middleName'=>'Required|string',
            'lastName'=>'Required|string',
            'className'=>'regex:/^\d{1,2}-[A-Z]$/',
        ]);
        if($validation->fails()){
            return response()->json([
                'status'=>false,
                'message'=>$validation->errors(),
            ]);
        }
        //first of all we need to get the class id by its name
        $class=schoolClass::where('className',$request->className)->first();
        //now we wanna get the user id by the student name
        $user=User::where('name',$request->name)->where('middleName',$request->middleName)->where('lastName',$request->lastName)->where('role','student')->first();
        //now wwe can rerturn the student data
        $student=Student::where('user_id',$user->id)->where('class_id',$class->id)->first();
        //returning data
        return response()->json([
            'status'=>true,
            'user'=>$user,
            'student'=>$student,
        ]);
    }catch(\Throwable $th){
        return response()->json([
            'status'=>false,
            'message'=>$th->getMessage(),
        ]);
    }
    }

    public function showAllStudents(){
        try{
            //getting all students
            $students=Student::all();
            //returning data
            return response()->json([
                'status'=>true,
                'data'=>$students,
            ],200);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    }

    public function showParentSon(){
        try{
            //first of all we wanna get the suer
            $user=Auth::user();
            //now we wanna get the parent
            $parent=Parents::where('user_id',$user->id)->first();
            //now we wanna get the student informations based on his id in $parent
            $student=Student::where('id',$parent->student_id)->first();
            //now we wanna get absence days
            $absences=CheckInTeacher::where('student_id',$parent->student_id)->get();
            //now we wanaa get warnings
            $warnings=AbsenceStudent::where('student_id',$parent->student_id)->first();

            //returning data
            return response()->json([
                'status'=>true,
                'student'=>$student,
                'absences'=>$absences,
                'warnings'=>$warnings,
            ]);

        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ],500);
        }
    }

    public function getInfo(){
        try{
            //getting user
            $user=Auth::user();
            //getting data based on role
                $dataArray=[];
            if($user->role=='student'){
                $roleData=Student::where('user_Id',$user->id)->first();
                $class=schoolClass::where('id',$roleData->class_id)->first();
                $dataArray[0]=$roleData;
                $dataArray[1]=$class;
            }elseif($user->role=='teacher'){
                $roleData=Teacher::where('user_Id',$user->id)->first();
            }elseif($user->role=='supervisor'){
                $roleData=Supervisor::where('user_Id',$user->id)->first();
            }
            //returning data
            return response()->json([
                'status'=>true,
                'user'=>$user,
                'roleData'=>$dataArray,
            ]);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    }
}
