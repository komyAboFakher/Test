<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\Student;
use App\Models\Subject;
use App\Models\schoolClass;
use App\Models\ExamSchedule;
use Illuminate\Http\Request;
use App\Models\ScheduleBrief;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;

class TimetablesManagementController extends Controller
{
    public function createWeeklySchedule(Request $request){
        try{
        //validation
        $validation=Validator::make($request->all(),[
            'classId'=>'required|integer|exists:classes,id',
            'day'=>'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'semester'=>'required|string|in:first,second',
            'year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
            'subject' => 'required|string',
            'session'=>'required|string|in:1,2,3,4,5,6,7',
        ]);
        if($validation->fails()){
            return Response()->json([
                'status'=>false,
                'message'=>$validation->errors(),
            ],422);
        }
        //we need to get subject id
            //we want to get the class name
            $class=schoolClass::where('id',$request->classId)->first();
            $classParts=explode('-',$class->className);
            $grade=$classParts[0];
            $subject=Subject::where('grade',$grade)->where('subjectName',$request->subject)->first();
        //we want to create a brief
            //we should make it create only one brief
        $brief=ScheduleBrief::firstOrcreate([
            'class_id'=>$request->classId,
            'day'=>$request->day,
            'semester'=>$request->semester,
            'year'=>$request->year,
        ]);
        //now its time to create schedule sessions
        $sessions=Session::firstOrcreate([
            'class_id'=>$request->classId,
            'schedule_brief_id'=>$brief->id,
            'subject_id'=>$subject->id,
            'cancelled'=>false,
            'session'=>$request->session,
        ]);
        //returning fail message
        if(!$brief && !$sessions){
            return response()->json([
                'status'=>false,
                'message'=>'the schedule is not created',
            ],422);
        }
        //returning success message
        return response()->json([
            'status'=>true,
            'message'=>'schedule has been created successfully!',
        ],200);
    }catch(\Throwable $th){
        return response()->json([
            'status'=>false,
            'message'=>$th->getMessage(),
        ],500);
    }
    }

    public function uploadExamSchedule(Request $request){
        try{
            //validation 
            $validation=Validator::make($request->all(),[
                'classId'=>'required|integer|exists:classes,id',
                'schedule' => 'required|mimes:pdf|max:2048',
            ]);
            if($validation->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validation->errors(),
                ]);
            }
            //creating schedule
            $schedule=ExamSchedule::create([
                'class_id'=>$request->classId,
                'schedule'=>$request->schedule,
            ]);
            //retrun success message 
            return response()->json([
                'status'=>true,
                'message'=>'schedule has been created successfully!',
            ]);
        }catch(\throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ],500);
        }
    }
    
     public function getStudentWeeklySchedule(){
        try{
            //first of all we wanna get the user
            $user=Auth::user();
            //now we wanna get the student
            $student=Student::where('user_id',$user->id)->first();
            //now we wanna get the briefs
            $briefs=ScheduleBrief::where('class_id',$student->class_id)->get();
            //now we wanna get the sessions
            $sessions=Session::where('class_id',$student->class_id)->get();
            //now we wanna get the subjects
            $subjects=[];
            $i=0;
            foreach($sessions as $session){
                //getting the subject by id
                $subjects[$i]=Subject::where('id',$session->subject_id)->first();
                $i+=1;
            }
            //returning data
            return response()->json([
                'status'=>true,
                'briefs'=>$briefs,
                'sessions'=>$sessions,
                'subjects'=>$subjects,
            ]);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ],500);
        }
     }

     public function getStudentExamSchedule(){
        try{
            //first of all we wanna get the user
            $user=Auth::user();
            //now we wanna get the student
            $student=Student::where('user_id',$user->id)->first();
            //now we wanna get the exams
            $exams=ExamSchedule::where('class_id',$student->class_id)->get();
            //returning data
            return response()->json([
                'status'=>true,
                'exams'=>$exams,
            ]);
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ],500);
        }
     }

}
