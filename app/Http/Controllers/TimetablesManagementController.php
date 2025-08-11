<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Academic;
use App\Models\schoolClass;
use App\Models\ExamSchedule;
use Illuminate\Http\Request;
use App\Models\ScheduleBrief;
use App\Models\TeacherClass;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;

class TimetablesManagementController extends Controller
{
    // public function createWeeklySchedule(Request $request)
    // {
    //     try {
    //         //validation
    //         $validation = Validator::make($request->all(), [
    //             'classId' => 'required|integer|exists:classes,id',
    //             'day' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
    //             'semester' => 'required|string|in:first,second',
    //             'year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
    //             'subject' => 'required|string',
    //             'session' => 'required|string|in:1,2,3,4,5,6,7',
    //         ]);
    //         if ($validation->fails()) {
    //             return Response()->json([
    //                 'status' => false,
    //                 'message' => $validation->errors(),
    //             ], 422);
    //         }
    //         //we need to get subject id
    //         //we want to get the class name
    //         $class = schoolClass::where('id', $request->classId)->first();
    //         $classParts = explode('-', $class->className);
    //         $grade = $classParts[0];
    //         $subject = Subject::where('grade', $grade)->where('subjectName', $request->subject)->first();
    //         //we want to create a brief
    //         //we should make it create only one brief
    //         $brief = ScheduleBrief::firstOrcreate([
    //             'class_id' => $request->classId,
    //             'day' => $request->day,
    //             'semester' => $request->semester,
    //             'year' => $request->year,
    //         ]);
    //         //now its time to create schedule sessions
    //         $sessions = Session::firstOrcreate([
    //             'class_id' => $request->classId,
    //             'schedule_brief_id' => $brief->id,
    //             'subject_id' => $subject->id,
    //             'cancelled' => false,
    //             'session' => $request->session,
    //         ]);
    //         //returning fail message
    //         if (!$brief && !$sessions) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'the schedule is not created',
    //             ], 422);
    //         }
    //         //returning success message
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'schedule has been created successfully!',
    //         ], 200);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $th->getMessage(),
    //         ], 500);
    //     }
    // }
    public function teachersAndTheirSessions(Request $request){
        try{
            //validation
            $validation=Validator::make($request->all(),[
                'className'=>['regex:/^\d{1,2}-[A-Z]$/', 'exists:classes,className']
            ]);
            if($validation->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validation->errors(),
                ],422);
            }
            //now we will get the teacherIds of this class
            $teachersIds = DB::table('teacher_Classes')
                ->where('class_id', function ($query) use ($request) {
                    $query->select('id')
                        ->from('classes')
                        ->where('className', $request->className)
                        ->limit(1);
                })
                ->pluck('teacher_id');

            $schedules = Session::query()
                ->join('schedule_briefs', 'sessions.schedule_brief_id', '=', 'schedule_briefs.id') 
                ->whereIn('sessions.teacher_id', $teachersIds)
                ->select('sessions.teacher_id as teacher_id', 'sessions.Session as session', 'schedule_briefs.day as day')
                ->get()
                ->all();

            return response()->json([
                'status'=>true,
                'schedules'=>$schedules
            ],200);

        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage()
            ],500);
        }
    }

    public function deleteWeeklySchecdule(Request $request){
        try{
            //validation
            $validation=Validator::make($request->all(),[
                'className'=>['regex:/^\d{1,2}-[A-Z]$/', 'exists:classes,className']
            ]);

            if($validation->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validation->errors(),
                ],422);
            }

            //we wanna check if there is already a schedule for this spesefic class
                //first of all we wanna get the class id
                $classId=schoolClass::where('className',$request->className)->first();
                //now we wanna get their briefs
                $brief=ScheduleBrief::where('class_id',$classId)->delete();

            //returning success message
            return response()->json([
                'staus'=>true,
                'message'=>'schedule of class '. $request->className .' has been deleted succefully!',
            ]);
            }catch(\Throwable $th){
                return response()->json([
                    'status'=>false,
                    'message'=>$th->getMessage(),
                ],500);
            }
    }

    public function generateWeeklySchedule(Request $request){
        try{
            //validation
            $validation=Validator::make($request->all(),[
                'className'=>['regex:/^\d{1,2}-[A-Z]$/', 'exists:classes,className']
            ]);

            if($validation->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validation->errors(),
                ],422);
            }

            //we wanna check if there is already a schedule for this spesefic class
                //first of all we wanna get the class id
                $classId=schoolClass::where('className',$request->className)->value('id');
                //now we wanna get their briefs
                $existingbrief=ScheduleBrief::where('class_id',$classId)->first();
                //now there is the check
                if($existingbrief){
                    return response()->json([
                        'status'=>false,
                        'message'=>"the class already has a timetable delete it before genrating it!",
                    ],422);
                }
            //now we will get the teacherIds of this class
            $teachersIds = DB::table('teacher_Classes')
                ->where('class_id', function ($query) use ($request) {
                    $query->select('id')
                        ->from('classes')
                        ->where('className', $request->className)
                        ->limit(1);
                })
                ->pluck('teacher_id');

            $schedules = Session::query()
                ->join('schedule_briefs', 'sessions.schedule_brief_id', '=', 'schedule_briefs.id') 
                ->whereIn('sessions.teacher_id', $teachersIds)
                ->select('sessions.teacher_id as teacher_id', 'sessions.Session as session', 'schedule_briefs.day as day')
                ->get()
                ->all();

            $weekDays=['sunday','monday','tuesday','wednesday','thursday'];
            $academicYear=Academic::value('academicYear');
            $academicSemester=Academic::value('academicSemester');
            for($i = 0 ; $i < 5; $i++){
                //first of all we wanna create a schedule brief
                $brief=ScheduleBrief::create([
                    'class_id'=>$classId,
                    'day'=>$weekDays[$i],
                    'semester'=>$academicSemester,
                    'year'=>$academicYear,
                ]);
                //now we wwant to create 7 sessions for this day brief
                for($i = 0; $i < 7; $i++){

                    $session=Session::firstOrCreate([
                        'class_id'=>$classId,        
                        'teacher_id',        
                        'schedule_brief_id'=>$brief->id,        
                        'subject_id',        
                        'cancelled'=>false,        
                        'session'=>$i,        
                    ]);
                }
            }
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage()
            ],500);
        }
    }

    public function createWeeklySchedule(Request $request)
    {
        $allowedSubjects = config('subjects.allowed');
        //validation
        $validation = Validator::make($request->all(), [
            'classId' => 'required|integer|exists:classes,id',
            // 'semester' => 'required|string|in:first,second',
            // 'year' => 'required|string|regex:/^\d{4}\/\d{4}$/',
            'schedule' => 'required|array',
            'schedule.*.day' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday',
            'schedule.*.session' => 'required|numeric|in:1,2,3,4,5,6,7',
            'schedule.*.subject' => ['required', 'string', Rule::in($allowedSubjects)],
        ]);
        if($validation->fails()){
            return response()->json([
                'status'=>false,
                'message'=>$validation->errors(),
            ],422);
        }
        //now we wanna get their briefs
        $existingbrief=ScheduleBrief::where('class_id',$request->classId)->first();
        //now there is the check
        if($existingbrief){
            return response()->json([
                'status'=>false,
                'message'=>"the class already has a timetable delete it before genrating it!",
            ],422);
        }
        try{
            DB::transaction(function() use($request){
                $academics=Academic::firstOrFail();
                $class=schoolClass::find($request->classId);
                $classParts=explode('-',$class->className);
                $grade=$classParts[0];
                //use chache to avoid reading the same brief or subject repeadetly
                $briefsCache=[];
                $subjectsCache=[];

                foreach($request->schedule as $item){
                    $day=$item['day'];

                    //create orfind the schedule brief effeciently
                    if(!isset($briefsCache[$day])){
                        $briefsCache[$day] = ScheduleBrief::firstOrCreate([
                        'class_id' => $request->classId,
                        'day' => $day,
                        'semester' => $academics->academic_semester,
                        'year' => $academics->academic_year,
                        ]);
                    }

                    $brief=$briefsCache[$day];

                    //find the subject id effeciently
                    $subjectName=$item['subject'];
                    if(!isset($subjectsCache[$subjectName])){
                        $subject = Subject::where('grade', $grade)->where('subjectName', $subjectName)->firstOrFail();
                        $subjectsCache[$subjectName] = $subject->id;
                    }
                    $subjectId=$subjectsCache[$subjectName];
                    //we need to get the teacher id
                    $teacherId=TeacherClass::where('class_id',$request->classId)->where('subject_id',$subjectId)->value('teacher_id');
                    // 5. Create the session
                    Session::create([
                        'class_id' => $request->classId,
                        'schedule_brief_id' => $brief->id,
                        'subject_id' => $subjectId,
                        'teacher_id' => $teacherId,
                        'cancelled' => false,
                        'session' => $item['session'],
                    ]);
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Schedule has been created successfully!',
            ], 200);

        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ],500);
        }
    }

    public function uploadExamSchedule(Request $request)
    {
        try {
            //validation 
            $validation = Validator::make($request->all(), [
                'classId' => 'required|integer|exists:classes,id',
                'schedule' => 'required|mimes:pdf|max:2048',
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ]);
            }
            //creating schedule
            $schedule = ExamSchedule::create([
                'class_id' => $request->classId,
                'schedule' => $request->schedule,
            ]);
            //retrun success message 
            return response()->json([
                'status' => true,
                'message' => 'schedule has been created successfully!',
            ]);
        } catch (\throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function getStudentWeeklySchedule()
    {
        try {
            // 1. Get the authenticated user and their student profile
            $user = Auth::user();
            $student = Student::where('user_id', $user->id)->firstOrFail();

            // 2. Fetch all sessions for the student's class
            // Eager load the related 'subject' and 'scheduleBrief' data to avoid N+1 issues
            $sessions = Session::with(['subject', 'scheduleBrief'])
                ->where('class_id', $student->class_id)
                ->get();

            // 3. Transform the collection into the desired structure
            $schedule = $sessions->map(function ($session) {
                // Check if relations are loaded to prevent errors
                if (!$session->subject || !$session->scheduleBrief) {
                    return null; // or handle this case as needed
                }

                return [
                    'subject'   => $session->subject->subjectName,
                    'session'   => $session->session,
                    'cancelled' => $session->cancelled,
                    'day'       => $session->scheduleBrief->day,
                    'semester'  => $session->scheduleBrief->semester,
                    'year'      => $session->scheduleBrief->year,
                ];
            })->filter()->values(); // ->filter() removes any nulls, ->values() resets array keys

            // 4. Return the formatted data
            //return response()->json($schedule);
            return response()->json([
                'status' => true,
                'schedule' => $schedule,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function getStudentExamSchedule()
    {
        try {
            //first of all we wanna get the user
            $user = Auth::user();
            //now we wanna get the student
            $student = Student::where('user_id', $user->id)->first();
            //now we wanna get the exams
            $exams = ExamSchedule::where('class_id', $student->class_id)->get();
            //returning data
            return response()->json([
                'status' => true,
                'exams' => $exams,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
