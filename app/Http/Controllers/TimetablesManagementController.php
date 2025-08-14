<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Academic;
use App\Models\schoolClass;
use App\Models\ExamSchedule;
use App\Models\TeacherClass;
use Illuminate\Http\Request;
use App\Models\ScheduleBrief;
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
    /*
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
    }*/
public function teachersAndTheirSessions(Request $request)
    {
        try {
            // 1. Validate the incoming request.
            $validation = Validator::make($request->all(), [
                'className' => ['required', 'string', 'regex:/^\d{1,2}-[A-Z]$/', 'exists:classes,className']
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }

            // 2. Get the Class ID.
            $classId = DB::table('classes')->where('className', $request->className)->value('id');

            // 3. Get all teacher IDs associated with the class.
            $teachersIds = DB::table('teacher_classes')
                ->where('class_id', $classId)
                ->pluck('teacher_id');

            if ($teachersIds->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'schedules' => []
                ], 200);
            }

            // 4. Get the schedule data for the found teachers.
            $schedulesData = Session::query()
                ->join('schedule_briefs', 'sessions.schedule_brief_id', '=', 'schedule_briefs.id')
                ->join('teachers', 'sessions.teacher_id', '=', 'teachers.id')
                ->join('users', 'teachers.user_id', '=', 'users.id')
                ->join('subjects', 'sessions.subject_id', '=', 'subjects.id')
                ->join('classes', 'sessions.class_id', '=', 'classes.id')
                ->whereIn('sessions.teacher_id', $teachersIds)
                // Also fixed the select statement to be explicit and use aliases.
                ->select(
                    'users.fullName as teacherName',
                    'subjects.subjectName as subjectName', // Assuming the column is 'name' in your subjects table
                    'sessions.Session as session',
                    'schedule_briefs.day as day',
                    'classes.className as className',
                )
                ->get();

            // 5. Group the flat collection by the subject's name.
            $groupedBySubject = $schedulesData->groupBy('subjectName');

            // 6. Format the grouped data to match the exact output structure you requested.
            $formattedSchedules = $groupedBySubject->map(function ($sessions) {
                return $sessions->map(function ($session) {
                    return [
                        'teacherName' => $session->teacherName,
                        'day' => $session->day,
                        'className' => $session->className,
                        'session' => $session->session,
                    ];
                })->values();
            });

            // 7. Wrap the final object in an array as per your requested format.
            $finalResponse = [$formattedSchedules];

            // 8. Return the successful response.
            return response()->json([
                'status' => true,
                'schedules' => $finalResponse,
            ], 200);

        } catch (\Throwable $th) {
            // 9. Catch any potential exceptions and return a server error response.
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
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
                $classId=schoolClass::where('className',$request->className)->value('id');
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

 public function generateWeeklySchedule(Request $request)
    {
        try {
            // --- VALIDATION (Assuming 'classes' table) ---
            $validation = Validator::make($request->all(), [
                'className' => ['required', 'string', 'regex:/^\d{1,2}-[A-Z]$/', 'exists:classes,className']
            ]);

            if ($validation->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validation->errors()], 422);
            }

            // --- PRE-CHECK ---
            $class = schoolClass::where('className', $request->className)->first();

            // Use dd($class->id); here to see what ID is being found for your className
            // For example: dd($class->id);

            $scheduleExists = ScheduleBrief::where('class_id', $class->id)->exists();

            if ($scheduleExists) {
                return response()->json(['status' => false, 'message' => "The class already has a timetable. Please delete it before generating a new one."], 422);
            }

            // --- 1. PREPARATION: GATHER DATA (Using snake_case conventions) ---

            $classSubjects = DB::table('teacher_classes as tc')
                ->join('subjects as s', 'tc.subject_id', '=', 's.id')
                ->join('users as u', 'tc.teacher_id', '=', 'u.id')
                ->where('tc.class_id', $class->id)
                ->select(
                    'tc.teacher_id',
                    'tc.subject_id',
                    's.subjectName', // CORRECTED: Assumes column is 'subject_name'
                    DB::raw("CONCAT(u.name, ' ', u.lastName) as teacher_name") // CORRECTED: Assumes 'first_name', 'last_name'
                )
                ->get()
                ->shuffle();

            if ($classSubjects->isEmpty()) {
                return response()->json(['status' => false, 'message' => "No subjects or teachers are assigned to this class for the given className."], 422);
            }

            $teacherIds = $classSubjects->pluck('teacher_id')->unique();

            // Build a fast "Conflict Map"
            $existingSchedules = Session::query()
                ->join('schedule_briefs as sb', 'sessions.schedule_brief_id', '=', 'sb.id') // CORRECTED: Join key is 'brief_id'
                ->whereIn('sessions.teacher_id', $teacherIds)
                ->select('sessions.teacher_id', 'sessions.session', 'sb.day')
                ->get();

            $conflictMap = [];
            foreach ($existingSchedules as $schedule) {
                // Ensure array keys are consistent
                $conflictMap[strtolower($schedule->day)][$schedule->session] = $schedule->teacher_id;
            }

            // --- 2. GENERATION: BUILD THE SCHEDULE IN MEMORY ---

            $newlyGeneratedSchedule = [];
            $weekDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday']; // Use lowercase to match conflict map keys
            $sessionsPerDay = 7;
            $subjectsToPlace = $classSubjects->all();

            foreach ($weekDays as $day) {
                $dailySessions = [];
                for ($sessionNum = 1; $sessionNum <= $sessionsPerDay; $sessionNum++) {
                    $assignedSlot = null;
                    foreach ($subjectsToPlace as $key => $subject) {
                        $isTeacherBusy = isset($conflictMap[$day][$sessionNum]) && $conflictMap[$day][$sessionNum] == $subject->teacher_id;

                        if (!$isTeacherBusy) {
                            $assignedSlot = ['session' => $sessionNum, 'day' => $day, 'class_id' => $class->id] + (array)$subject;
                            unset($subjectsToPlace[$key]);
                            break;
                        }
                    }

                    if (is_null($assignedSlot)) {
                        $assignedSlot = ['session' => $sessionNum, 'day' => $day, 'class_id' => $class->id, 'teacher_id' => null, 'subject_name' => 'Free Period', 'teacher_name' => null];
                    }
                    $dailySessions[] = $assignedSlot;
                }
                $newlyGeneratedSchedule[] = ['day' => $day, 'sessions' => $dailySessions];
            }

            // --- 3. RESPONSE ---
            return response()->json(['status' => true, 'message' => 'Schedule generated successfully.', 'schedule_data' => $newlyGeneratedSchedule], 200);

        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
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
                    if(!$teacherId){
                        return response()->json([
                            'status'=>false,
                            'message'=>'the class doesnt have any assigned teacher to the subject '.$subjectName.'!',
                        ],422);
                    }
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

    public function getClassWeeklySchcedule(Request $request)
    {
        try {
            //validation 
            $validation = Validator::make($request->all(), [
                'className' => ['required', 'string', 'regex:/^\d{1,2}-[A-Z]$/', 'exists:classes,className']
            ]);
            if($validation->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validation->errors(),
                ],422);
            }
            $classId=schoolClass::where('className',$request->className)->value('id');
            $schedule=Session::query()
            ->join('schedule_briefs','sessions.schedule_brief_id','=','schedule_briefs.id')
            ->join('teachers','sessions.teacher_id','=','teachers.id')
            ->join('users','teachers.user_id','=','users.id')
            ->join('subjects','sessions.subject_id','=','subjects.id')
            ->where('sessions.class_id',$classId)
            ->select(
                'subjects.subjectName as subject',     // Assumes your column is literally 'subjectName'
                'users.name as teacher_name', // Get name from users table, call it 'teacher_name'
                'sessions.cancelled',
                'sessions.session',
                'schedule_briefs.day'
            )
            ->get()
            ->all();

            //returning success message
            return response()->json([
                'status'=>true,
                'schedule'=>$schedule,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
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


    public function getStudentExamSchedule(Request $request)
    {
        try {
            //validation
            $validaiton=Validator::make($request->all(),[
                'type'=>'required|string|in:final,quiz'
            ]);
            if($validaiton->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validaiton->errors(),
                ],422);
            }
            //first of all we wanna get the user
            $user = Auth::user();
            //now we wanna get the student
            $student = Student::where('user_id', $user->id)->first();
            //now we wanna get the exams
            $exams = ExamSchedule::where('class_id', $student->class_id)->where('type', $request->type)->first();
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

    public function getTeacherWeeklySchedule(){
        try{
            //getting the user
            $user=Auth::user();
            $teacher=Teacher::where('user_id',$user->id)->first();
            if(!$teacher){
                return response()->json([
                    'status'=>false,
                    'message'=>'teacher not found',
                ],422);
            }
            // //now we will get his schedules
            $schedule=Session::query()
            ->join('schedule_briefs','schedule_briefs.id','=','sessions.schedule_brief_id')
            ->join('classes','classes.id','=','sessions.class_id')
            ->join('subjects','subjects.id','=','sessions.subject_id')
            ->where('teacher_id',$teacher->id)
            ->select(
                'sessions.session as session',
                'sessions.cancelled as cancelled',
                'subjects.subjectName as subjectName',
                'schedule_briefs.day as day',
                'classes.className as className',
            )
            ->get()
            ->all();
            if(!$schedule){
                return response()->json([
                    'status'=>false,
                    'message'=>'schedule not found',
                ],422);
            }
            //success message
            return response()->json([
                'status'=>true,
                'schedule'=>$schedule,
            ]);
        }catch(\throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    }
}
