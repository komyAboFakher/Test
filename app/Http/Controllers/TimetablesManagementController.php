<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Academic;
use App\Models\schoolClass;
use App\Models\ExamSchedule;
use App\Models\Pr;
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
                    'users.name as firstName',
                    'users.middleName as middleName',
                    'users.lastName as lastName',
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
                        'teacherName' => $session->firstName . ' ' .$session->middleName. ' '.$session->lastName,
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

    private function performDeleteSchedule(int $classId)
    {
        try{
            // 1. Find all brief IDs associated with this class
            $briefIds = ScheduleBrief::where('class_id', $classId)->pluck('id');

            // 2. If briefs exist, delete the sessions and then the briefs
            if ($briefIds->isNotEmpty()) {
                // CRITICAL: Delete all sessions linked to those briefs first
                Session::whereIn('schedule_brief_id', $briefIds)->delete();

                // Finally, delete the briefs themselves
                ScheduleBrief::whereIn('id', $briefIds)->delete();
            }
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    }

 public function createWeeklySchedule(Request $request)
    {
        try {
            // --- YOUR SPECIFIC VALIDATION RULES ---
            $allowedSubjects = config('subjects.allowed'); // Make sure this config file exists
            $validation = Validator::make($request->all(), [
                'classId' => 'required|integer|exists:classes,id',
                'schedule' => 'required|array',
                'schedule.*.day' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday',
                'schedule.*.session' => 'required|numeric|in:1,2,3,4,5,6,7',
                // Note: The key is 'subjectName' to match the data from your generate function
                'schedule.*.subject' => ['required', 'string', Rule::in($allowedSubjects)],
            ]);

            if ($validation->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validation->errors()], 422);
            }

            // --- PRE-CHECK: Does a schedule already exist? ---
            if (ScheduleBrief::where('class_id', $request->classId)->exists()) {
                return response()->json(['status' => false, 'message' => "The class already has a timetable. Please delete it before generating it!"], 422);
            }

            $class = schoolClass::find($request->classId);
            $grade = explode('-', $class->className)[0];
            $scheduleItems = $request->schedule;
            $errors = [];
            $validatedData = [];

            // --- PHASE 1: PRE-VALIDATION LOOP ---
            // Check all data before starting the database transaction.
            foreach ($scheduleItems as $index => $item) {
                if ($item['subject'] === 'Free Period') {
                    $validatedData[] = $item + ['teacher_id' => null, 'subject_id' => null];
                    continue;
                }

                $subject = Subject::where('grade', $grade)->where('subjectName', $item['subject'])->first();
                if (!$subject) {
                    $errors[] = "Subject '{$item['subject']}' not found for grade {$grade} (at session {$item['session']} on {$item['day']}).";
                    continue;
                }

                $teacherId = TeacherClass::where('class_id', $request->classId)->where('subject_id', $subject->id)->value('teacher_id');

                if (!$teacherId) {
                    $errors[] = "No teacher is assigned to subject '{$item['subject']}' for this class.";
                }
                
                $validatedData[] = $item + ['teacher_id' => $teacherId, 'subject_id' => $subject->id];
            }

            if (!empty($errors)) {
                return response()->json(['status' => false, 'message' => 'Invalid schedule data provided.', 'errors' => array_unique($errors)], 422);
            }

            // --- PHASE 2: CREATION PHASE ---
            // If we reach this point, all data is valid. Now we can safely create records.
            DB::transaction(function () use ($request, $validatedData) {
                $academics = Academic::firstOrFail();
                $briefsCache = [];

                foreach ($validatedData as $item) {
                    if (is_null($item['subject_id'])) {
                        continue;
                    }

                    $day = $item['day'];

                    if (!isset($briefsCache[$day])) {
                        $briefsCache[$day] = ScheduleBrief::create([
                            'class_id' => $request->classId,
                            'day' => $day,
                            'semester' => $academics->academic_semester,
                            'year' => $academics->academic_year,
                        ]);
                    }
                    $brief = $briefsCache[$day];

                    Session::create([
                        'class_id' => $request->classId,
                        'schedule_brief_id' => $brief->id,
                        'subject_id' => $item['subject_id'],
                        'teacher_id' => $item['teacher_id'],
                        'cancelled' => false,
                        'session' => $item['session'],
                    ]);
                }
            });

            return response()->json(['status' => true, 'message' => 'Schedule has been created successfully!'], 200);

        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }

    private function performCreateSchedule(int $classId, array $scheduleItems): void
{
    $class = schoolClass::find($classId);
    $grade = explode('-', $class->className)[0];
    $errors = [];
    $validatedData = [];

    // --- PHASE 1: PRE-VALIDATION LOOP ---
    // Check all data before starting the database transaction.
    foreach ($scheduleItems as $index => $item) {
        // The frontend should send the subject's name, not its ID
        $subjectName = $item['subject']; 
        
        if ($subjectName === 'Free Period') {
            $validatedData[] = $item + ['teacher_id' => null, 'subject_id' => null];
            continue;
        }

        $subject = Subject::where('grade', $grade)->where('subjectName', $subjectName)->first();
        if (!$subject) {
            $errors[] = "Subject '{$subjectName}' not found for grade {$grade}.";
            continue; // Continue checking other items
        }

        $teacherId = TeacherClass::where('class_id', $classId)->where('subject_id', $subject->id)->value('teacher_id');
        if (!$teacherId) {
            $errors[] = "No teacher is assigned to subject '{$subjectName}' for this class.";
        }
        
        $validatedData[] = $item + ['teacher_id' => $teacherId, 'subject_id' => $subject->id];
    }

    // If any errors were found, throw an exception with all the details.
    if (!empty($errors)) {
        // Consolidate unique errors into a single string
        $errorString = implode(' | ', array_unique($errors));
        throw new \Exception($errorString);
    }

    // --- PHASE 2: CREATION PHASE ---
    // If we reach here, all data is valid.
    DB::transaction(function () use ($classId, $validatedData) {
        $academics = Academic::firstOrFail();
        $briefsCache = [];

        foreach ($validatedData as $item) {
            if (is_null($item['subject_id'])) {
                continue;
            }

            $day = $item['day'];
            if (!isset($briefsCache[$day])) {
                $briefsCache[$day] = ScheduleBrief::create([
                    'class_id' => $classId,
                    'day' => $day,
                    'semester' => $academics->academic_semester,
                    'year' => $academics->academic_year,
                ]);
            }
            $brief = $briefsCache[$day];

            Session::create([
                'class_id' => $classId,
                'schedule_brief_id' => $brief->id,
                'subject_id' => $item['subject_id'],
                'teacher_id' => $item['teacher_id'],
                'cancelled' => false,
                'session' => $item['session'],
            ]);
        }
    });
}
public function updateWeeklySchedule(Request $request)
{
    // First, validate the incoming data for the NEW schedule
    $validation = Validator::make($request->all(), [
        'classId' => 'required|integer|exists:classes,id',
        'schedule' => 'required|array',
        'schedule.*.day' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday',
        'schedule.*.session' => 'required|numeric|between:1,7',
        'schedule.*.subject' => 'required|string', 
    ]);

    if ($validation->fails()) {
        return response()->json(['status' => false, 'message' => $validation->errors()], 422);
    }

    try {
        // Use a single transaction to ensure the update is atomic
        DB::transaction(function () use ($request) {
            
            // Step 1: Delete the old schedule using the private helper
            $this->performDeleteSchedule($request->classId);
            
            // Step 2: Create the new schedule using the private helper
            // As you correctly said, we pass the classId and schedule array.
            $this->performCreateSchedule($request->classId, $request->schedule);

        });

        // If the transaction completes without errors, the update was successful
        return response()->json([
            'status' => true,
            'message' => 'The schedule has been updated successfully!',
        ], 200);

    } catch (\Throwable $th) {
        // If anything fails in the transaction, it's rolled back and we catch the error
        return response()->json([
            'status' => false,
            'message' => $th->getMessage(),
        ], 500);
    }
}

public function generateWeeklySchedule(Request $request)
{
    try {
        // --- VALIDATION & PRE-CHECKS ---
        $validation = Validator::make($request->all(), [
            'className' => ['required', 'string', 'regex:/^\d{1,2}-[A-Z]$/', 'exists:classes,className']
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validation->errors()], 422);
        }

        $class = schoolClass::where('className', $request->className)->first();
        if (ScheduleBrief::where('class_id', $class->id)->exists()) {
            return response()->json(['status' => false, 'message' => "The class already has a timetable. Please delete it before generating a new one."], 422);
        }

        // --- 1. PREPARATION: GATHER DATA ---
        $classSubjects = DB::table('teacher_classes as tc')
            ->join('subjects as s', 'tc.subject_id', '=', 's.id')
            ->join('users as u', 'tc.teacher_id', '=', 'u.id')
            ->where('tc.class_id', $class->id)
            ->select('tc.teacher_id', 'tc.subject_id', 's.subjectName as subject', DB::raw("CONCAT(u.name, ' ', u.lastName) as teacher_name"))
            ->get();

        if ($classSubjects->isEmpty()) {
            return response()->json(['status' => false, 'message' => "No subjects or teachers are assigned to this class for the given className."], 422);
        }

        // Fetch ALL existing schedules to build a complete conflict map
        $allExistingSchedules = Session::query()
            ->join('schedule_briefs as sb', 'sessions.schedule_brief_id', '=', 'sb.id')
            ->select('sessions.teacher_id', 'sessions.class_id', 'sessions.session', 'sb.day')
            ->get();

        // **CORRECTION 1: Build a more detailed conflict map**
        $conflictMap = [];
        foreach ($allExistingSchedules as $schedule) {
            $day = strtolower($schedule->day);
            $session = $schedule->session;

            // Initialize the slot if it's the first time we see it
            if (!isset($conflictMap[$day][$session])) {
                $conflictMap[$day][$session] = [
                    'teachers' => [],
                    'classes' => [],
                ];
            }
            // Add the busy teacher and class to the lists for that slot
            $conflictMap[$day][$session]['teachers'][] = $schedule->teacher_id;
            $conflictMap[$day][$session]['classes'][] = $schedule->class_id;
        }

        // return response()->json([
        //     'meow'=>$conflictMap
        // ]);
        // --- 2. GENERATION: BUILD THE SCHEDULE IN MEMORY ---
        $newlyGeneratedSchedule = [];
        $weekDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];
        $sessionsPerDay = 7;
        $subjectsToPlace = $classSubjects->shuffle()->all(); // Create the pool of subjects ONCE

        foreach ($weekDays as $day) {
            for ($sessionNum = 1; $sessionNum <= $sessionsPerDay; $sessionNum++) {
                $assignedSlot = null;
                $subjectsToPlace = $classSubjects->shuffle()->all();
                foreach ($subjectsToPlace as $key => $subject) {
                    
                    // **CORRECTION 2: The new, more accurate conflict check**
                    $isTeacherBusy = isset($conflictMap[$day][$sessionNum]) && in_array($subject->teacher_id, $conflictMap[$day][$sessionNum]['teachers']);
                    
                    // A class cannot have two lessons at the same time. Let's check if this class is already busy.
                    $isClassBusy = isset($conflictMap[$day][$sessionNum]) && in_array($class->id, $conflictMap[$day][$sessionNum]['classes']);

                    if (!$isTeacherBusy && !$isClassBusy) {
                        $assignedSlot = ['session' => $sessionNum, 'day' => $day, 'class_id' => $class->id] + (array)$subject;
                        
                        // Immediately update the conflict map with our new assignment for this run
                        $conflictMap[$day][$sessionNum]['teachers'][] = $subject->teacher_id;
                        $conflictMap[$day][$sessionNum]['classes'][] = $class->id;
                        
                        unset($subjectsToPlace[$key]); // Remove subject from the pool
                        break;
                    }
                }

                if (is_null($assignedSlot)) {
                    $assignedSlot = ['session' => $sessionNum, 'day' => $day, 'class_id' => $class->id, 'teacher_id' => null, 'subject_id' => null, 'subject' => 'Free Period', 'teacher_name' => null];
                }
                
                $newlyGeneratedSchedule[] = $assignedSlot;
            }
        }

        // --- 3. RESPONSE ---
        return response()->json(['status' => true, 'message' => 'Schedule generated successfully.', 'schedule_data' => $newlyGeneratedSchedule], 200);

    } catch (\Throwable $th) {
        return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
    }
}


    public function uploadExamSchedule(Request $request)
    {
        try {
            //validation 
            $validation = Validator::make($request->all(), [
                'grade' => 'required|integer|in:1,2,3,4,5,6,7,8,9,10,11,12',
                'semester'=>'required|in:first,second',
                'schedule' => 'required|mimes:pdf|max:2048',
                'type'=>'required|string|in:final,midTerm'
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ]);
            }
            //intiating pdfs url 
            $pdfUrl=$request->file('schedule')->store('exam_schedule','public');
            //creating schedule
            $schedule = ExamSchedule::firstOrcreate([
                'grade' => $request->grade,
                'semester' => $request->semester,
                'type' => $request->type,
                'schedule' => $pdfUrl,
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

    public function getExamSchedule(Request $request){
        try{
            //validation
            $validation=Validator::make($request->all(),[
                //'className' => ['required', 'string', 'regex:/^\d{1,2}-[A-Z]$/', 'exists:classes,className'],
                'grade' => ['required', 'string' , 'in:1,2,3,4,5,6,7,8,9,10,11,12'],
                'type'=>'required|string|in:final,mid-term',
                'semester'=>'required|string|in:first,second',
            ]);
            if($validation ->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validation->errors(),
                ]);
            }
            //getting the class id
            $classId=schoolClass::where('className',$request->className)->value('id');
            //now getting the schedule
            $schedule=ExamSchedule::where('class_id',$classId)->where('type',$request->type)->where('semester',$request->semester)->first();
        }catch(\Throwable $th){
            return response()->json([
                'status'=>false,
                'message'=>$th->getMessage(),
            ]);
        }
    }
    public function getStudentExamSchedule(Request $request)
    {
        try {
            //validation
            $validaiton=Validator::make($request->all(),[
                'type'=>'required|string|in:final,mid-term'
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
            //getting the semester
            $academic=Academic::firstOrFail();
            //now we wanna get the exams
            $exams = ExamSchedule::where('class_id', $student->class_id)->where('type', $request->semester)->where('semester', $academic->semester)->first();
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
                'subjects.subjectName as subject',
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
