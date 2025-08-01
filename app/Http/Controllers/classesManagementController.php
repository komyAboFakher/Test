<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\schoolClass;
use App\Models\TeacherClass;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\FuncCall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\SchoolClass as ModelsSchoolClass;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class classesManagementController extends Controller
{
    public function createClasses(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'className' => ['regex:/^\d{1,2}-[A-Z]$/', 'unique:classes,className'],
                'studentsNum' => 'required|integer|min:1',
                //'currentStudentNumber' => 'required|integer|min:0|lte:studentsNum', // Added lte (less than or equal) rule
            ], [
                'className.regex' => 'Class name must be in format like 10-A or 2-B with capital letter',
                'studentsNum.min' => 'Total students must be at least 1 '
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }

            //create the class
            $class = schoolClass::create([
                'className' => $request->className,
                'studentsNum' => $request->studentsNum,
            ]);

            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'class has been created successfully!',
                'data' => $class
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    //__________________________________________________________________________________________

    public function showClasses(Request $request)
    {
        try {
            //getting all classes
            $classes = schoolClass::all();
            //returning data
            return response()->json([
                'status' => false,
                'data' => $classes,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    //___________________________________________________________________________________

    public function editClass(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'class_id' => 'required|integer|exists:classes,id',
                'className' => 'regex:/^\d{1,2}-[A-Z]$/',
                'studentsNum' => 'required|integer|min:1',

            ], [
                'studentsNum.min' => 'Total capacity must be at least 1.',
                'className.regex' => 'the class name must be like 10-A with capital letter'
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }


            $class = SchoolClass::findOrFail($request->class_id);

            // check for duplicated names, but the class can keep his own name
            if (SchoolClass::where('className', $request->className)
                ->where('id', '!=', $request->class_id)
                ->exists()
            ) {
                return response()->json([
                    'message' => "The class name is already in use!"
                ], 409);
            }


            if ($request->studentsNum < $class->currentStudentNumber) {
                return response()->json([
                    'message' => "the max size can't be less than the current student number !!!"
                ]);
            }




            $class->fill($request->only([
                'class_id',
                'className',
                'studentsNum'
            ]))->save();

            return response()->json([
                'status' => true,
                'message' => 'class has been edited succesfully!',
                'class' => $class,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //_________________________________________________________________________________

    public function assignStudentToClass(Request $request)
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'studentId' => 'required|integer|exists:students,id',
                'new_class_id' => 'required|integer|exists:classes,id',
            ], [
                'studentId.exists' => 'The specified student does not exist',
                'new_class_id.exists' => 'The specified class does not exist'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }


            DB::beginTransaction();

            //getting the student, then the current student number in the new class

            $student = Student::findOrFail($request->studentId);
            $newClass = SchoolClass::withCount('students')->findOrFail($request->new_class_id);

            //getting the old class_id

            $oldClass = $student->class_id ? SchoolClass::find($student->class_id) : null;

            // Check if moving to same class

            if ($student->class_id == $request->new_class_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Student is already in this class'
                ], 409);
            }

            // Check new class capacity

            if ($newClass->students_count >= $newClass->studentsNum) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'this class has reached maximum capacity',
                    'current_count :' => $newClass->students_count,
                    'max_capacity :' => $newClass->studentsNum
                ], 422);
            }

            // Update student's class
            $student->class_id = $request->new_class_id;
            // $student->save();

            $student->fill($request->only([
                'new_class_id'
            ]))->save();
            // Increment new class count
            if (is_null($newClass->currentStudentNumber)) {
                $newClass->currentStudentNumber = 1;
            } else {
                $newClass->increment('currentStudentNumber');
            }
            $newClass->save();

            // Decrement old class if exists
            if ($oldClass) {
                $oldClass->decrement('currentStudentNumber');
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Student transferred successfully',
                'data' => [
                    'student' => $student->users->only(['id', 'name', 'middleName', 'lastName']),
                    'old_class' => $oldClass ? $oldClass->only(['id', 'className', 'currentStudentNumber']) : null,
                    'new_class' => $newClass->only(['id', 'className', 'currentStudentNumber'])
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Resource not found: ' . $e->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Transfer failed: ' . $th->getMessage()
            ], 500);
        }
    }
    //_____________________________________________________________________________________

    public function deleteClass(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'classId' => 'required|integer|exists:classes,id',
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors()
                ]);
            }
            //resetting students in the deleted class
            $students = Student::where('class_id', $request->classId)->get();
            foreach ($students as $student) {
                $student->class_id = null;
            }
            //deleting the classes
            schoolClass::where('id', $request->classId)->delete();
            //returning success message
            return Response()->json([
                'status' => true,
                'message' => 'class has been deleted successfully!',
            ]);
        } catch (\Throwable $th) {
            return Response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function assignTeacherToClass(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'teacherId' => 'required|integer|exists:teachers,id',
                'className' => 'regex:/^\d{1,2}-[A-Z]$/',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }
            //first off all we need to gather the data about the subject to get the right subject id
            //we wiil get the subject that this teacher teaches
            $theacherSubject = Teacher::where('id', $request->teacherId)->value('subject');
            //now we need to get class id by its name
            $class = schoolClass::where('className', $request->className)->first();
            if (!$class) {
                return response()->json([
                    'status' => false,
                    'message' => 'Class not found',
                ], 404);
            }
            //now we need to know which grade this class is
            $classParts = explode('-', $request->className);
            $grade = $classParts[0];
            //now we will get the subject from subjects table
            $subject = Subject::where('subjectName', $theacherSubject)->where('grade', $grade)->first();
            if (!$subject) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subject not found for this grade',
                ], 404);
            }

            //checking existing assign
            $existingAssign = TeacherClass::where('teacher_id', $request->teacherId)->where('class_id', $class->id)->where('subject_id', $subject->id)->first();
            $alreadyAssigned = TeacherClass::where('class_id', $class->id)->where('subject_id', $subject->id)->first();

            if ($existingAssign) {
                return response()->json([
                    'status' => false,
                    'message' => 'the assign has been made already',
                ], 422);
            }

            if ($alreadyAssigned) {
                return response()->json([
                    'status' => false,
                    'message' => 'there is already another teacher assigned for the same class with the same subject but if you want we can overwrite it',
                ], 422);
            }
            //now we will create a teacher-classes row  
            $classesTeacherAssignedTO = TeacherClass::where('teacher_id', $request->teacherId)->count();
            if ($classesTeacherAssignedTO < 3) {
                $teacherClass = TeacherClass::firstOrCreate([
                    'teacher_id' => $request->teacherId,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'messgae' => 'the teacher has been assigned to enough classes',
                ], 422);
            }
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'assignment has been done successfully!',
                'data' => $teacherClass,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //________________________________________________________________________________

    public function overWriteTeacherToClass(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'teacherId' => 'required|integer|exists:teachers,id',
                'className' => 'regex:/^\d{1,2}-[A-Z]$/',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }
            //first off all we need to gather the data about the subject to get the right subject id
            //we wiil get the subject that this teacher teaches
            $theacherSubject = Teacher::where('id', $request->teacherId)->value('subject');
            //now we need to get class id by its name
            $class = schoolClass::where('className', $request->className)->first();
            if (!$class) {
                return response()->json([
                    'status' => false,
                    'message' => 'Class not found',
                ], 404);
            }
            //now we need to know which grade this class is
            $classParts = explode('-', $request->className);
            $grade = $classParts[0];
            //now we will get the subject from subjects table
            $subject = Subject::where('subjectName', $theacherSubject)->where('grade', $grade)->first();
            if (!$subject) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subject not found for this grade',
                ], 404);
            }

            //deleting the old record
            TeacherClass::where('class_id', $class->id)->where('subject_id', $subject->id)->delete();

            //now we will create a teacher-classes row  
            $classesTeacherAssignedTO = TeacherClass::where('teacher_id', $request->teacherId)->count();
            if ($classesTeacherAssignedTO < 3) {
                $teacherClass = TeacherClass::firstOrCreate([
                    'teacher_id' => $request->teacherId,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'messgae' => 'the teacher has been assigned to enough classes',
                ], 422);
            }
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'assignment has been done successfully!',
                'data' => $teacherClass,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //__________________________________________________________________________

    public function unassignTeacherToClass(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'teacherId' => 'required|integer|exists:teachers,id',
                'className' => 'regex:/^\d{1,2}-[A-Z]$/',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->erros(),
                ], 422);
            }
            //first of all we wanna get the claass id based on the classname
            $class = SchoolClass::where('className', $request->className)->first();
            //now we wanna delete the records on the db
            TeacherClass::where('teacher_id', $request->teacherId)->where('class_id', $class->id)->delete();
            //return success message
            return response()->json([
                'status' => true,
                'message' => 'the teacher has been unassigned successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ]);
        }
    }


    //////////////////////////////////////////////////////////////////////////////////////////////////////////

    // for gaith, by komy abo fakher

    public function getAllStudents()
    {

        try {
            //getting all classes with their students

            $classes = SchoolClass::with(['students.users:id,name,middleName,lastName,email,phoneNumber'])
                ->get()
                ->map(function ($class) {
                    return [
                        'class_id' => $class->id,
                        'class_name' => $class->className,
                        'students' => $class->students->map(function ($student) {
                            return [
                                'student_id' => $student->user_id,
                                'full_name' => trim($student->users->name . ' ' .
                                    $student->users->middleName . ' ' .
                                    $student->users->lastName),
                                'email' => $student->users->email,
                                'phone' => $student->users->phoneNumber,
                                'photo' => $student->photo,
                                'gpa' => $student->Gpa,
                                'absences number' => $student->AbsenceStudent->absence_num ?? ''
                            ];
                        })
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $classes
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    //________________________________________________________________

    public function getPaginateStudents()
    {
        try {

            //get the students from the student table, then User() relation to get the info
            $students = User::with(['student.SchoolClass:id,className'])
                ->where('role', 'student')
                ->get()
                ->map(function ($user) {
                    return [
                        'user_id' => $user->id,
                        'student_id' => $user->student->id,
                        'full_name' => trim(implode(' ', array_filter([$user->name, $user->middleName, $user->lastName]))),
                        'email' => $user->email,
                        'phone' => $user->phoneNumber,
                        'photo' => $user->student->photo,
                        'gpa' => $user->student->Gpa,
                        'class_id' => $user->student->class_id ?? '',
                        'class_name' => $user->student->schoolClass->className ?? '',
                        'absences number' => $user->student->AbsenceStudent->absence_num ?? ''
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $students
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //________________________________________________________________
    public function getAllTeacherStudents()
    {


        try {
            $teacher = auth()->user()->teacher; // Get authenticated teacher

            $classes = TeacherClass::with([

                'SchoolClasses.students.users:id,name,middleName,lastName,email,phoneNumber',
                'SchoolClasses:id,className'
            ])
                ->where('teacher_id', $teacher->id)
                ->get()
                ->map(function ($teacherClass) {
                    return [

                        'students' => $teacherClass->SchoolClasses->students->map(function ($student) {
                            return [
                                'user_id' => $student->user_id,
                                //'user_id' => $student->students->id,
                                'full_name' => trim("{$student->users->name} {$student->users->middleName} {$student->users->lastName}"),
                                'email' => $student->users->email,
                                'phone' => $student->users->phoneNumber,
                                'photo' => $student->photo,
                                'gpa' => $student->Gpa,
                                'class_id' => $student->class_id,
                                'class_name' => $student->schoolClass->className
                            ];
                        })
                    ];
                });

            return response()->json([
                'status' => true,
                'message:' => 'your students !!',
                'data' => $classes
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________
    public function getAllTeachers()
    {


        try {
            $teachers = User::with([
                'teacher.schoolClasses:id,className',
                'teacher.subject:id,subjectName',
            ])
                ->where('role', 'teacher')
                ->get()
                ->map(function ($user) {


                    return [
                        'user_id' => $user->id,
                        'teacher_id' => $user->teacher->id,
                        'full_name' => trim($user->name . ' ' . $user->middleName . ' ' . $user->lastName),
                        'email' => $user->email,
                        'phone' => $user->phoneNumber,
                        'certification' => $user->teacher->certification ?? null,
                        'photo' => $user->teacher->photo ?? null,
                        'salary' => $user->teacher->salary ?? null,
                        'subject' => $user->teacher->subject ?? null,
                        'classes' => $user->teacher->schoolClasses->map(function ($schoolClass) {
                            return [
                                'class_id' => $schoolClass->id,
                                'class_name' => $schoolClass->className,
                            ];
                        }),
                        //'subject' => $user->teacher->subject->map(function ($subject) {
                        //    return [
                        //        'subject_id' => $subject->id,
                        //        'subject_name' => $subject->subjectName
                        //    ];
                        //})


                    ];
                });
            //->filter()
            //->values();

            return response()->json([
                'status' => true,
                'data' => $teachers
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________
    public function getAllSupervisors()
    {

        try {
            $supervisors = User::with(['supervisor'])
                ->where('role', 'supervisor')
                ->get()
                ->map(function ($user) {
                    return [
                        'user_id' => $user->id,
                        'supervisor_id' => $user->supervisor->id,
                        'full_name' => trim("{$user->name} {$user->middleName} {$user->lastName}"),
                        'email' => $user->email,
                        'phone' => $user->phoneNumber,
                        'photo' => $user->supervisor->photo ?? null,
                        'certification' => $user->supervisor->certification ?? null,
                        'salary' => $user->supervisor->salary ?? null,
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $supervisors
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________
    public function getSpecificStudent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $student = User::with('student')
                ->where('id', $request->student_id)
                ->where('role', 'student')
                ->first();

            if (!$student) {
                return response()->json([
                    'status' => false,
                    'message' => 'No student found with this ID or the user is not a student'
                ], 404);
            }

            $response = [
                'student_id' => $student->id,
                'full_name' => trim("{$student->name} {$student->middleName} {$student->lastName}"),
                'class_name' => $student->student->SchoolClass->className,
                'email' => $student->email,
                'phone' => $student->phoneNumber,
                'photo' => $student->student->photo ?? null,
                'school_graduated_from' => $student->student->schoolGraduatedFrom ?? null,
                'gpa' => $student->student->Gpa ?? null,

            ];

            return response()->json([
                'status' => true,
                'message' => 'Student information retrieved successfully',
                'data' => $response
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________
    public function getSpecificTeacher(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $classes = Teacher::where('user_id', $request->teacher_id)->with('SchoolClasses')->firstOrFail();




            $teacher = User::with('teacher')
                ->where('id', $request->teacher_id)
                ->where('role', 'teacher')
                ->first();

            if (!$teacher) {
                return response()->json([
                    'status' => false,
                    'message' => 'No teacher found with this ID or the user is not a teacher'
                ], 404);
            }

            $response = [
                'teacher_id' => $teacher->id,
                'full_name' => trim("{$teacher->name} {$teacher->middleName} {$teacher->lastName}"),
                'email' => $teacher->email,
                'phone' => $teacher->phoneNumber,
                'photo' => $teacher->teacher->photo ?? null,
                'certification' => $teacher->teacher->certification ?? null,
                'salary' => $teacher->teacher->salary ?? null,
                'subject' => $teacher->teacher->subject ?? null,
                'classes' => $classes->SchoolClasses->pluck('className')->toArray(),



            ];

            return response()->json([
                'status' => true,
                'message' => 'teacher information retrieved successfully',
                'data' => $response
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________
    public function getSpecificSupervisor(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'supervisor_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $supervisor = User::with('supervisor')
                ->where('id', $request->supervisor_id)
                ->where('role', 'supervisor')
                ->first();

            if (!$supervisor) {
                return response()->json([
                    'status' => false,
                    'message' => 'No supervisor found with this ID or the user is not a supervisor'
                ], 404);
            }

            $response = [
                'supervisor_id' => $supervisor->id,
                'full_name' => trim("{$supervisor->name} {$supervisor->middleName} {$supervisor->lastName}"),
                'email' => $supervisor->email,
                'phone' => $supervisor->phoneNumber,
                'photo' => $supervisor->supervisor->photo ?? null,
                'certification' => $supervisor->supervisor->certification ?? null,
                'salary' => $supervisor->supervisor->salary ?? null,


            ];

            return response()->json([
                'status' => true,
                'message' => 'sueprvisor information retrieved successfully',
                'data' => $response
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________
    private function calculateAttendanceYears($createdAt): int
    {
        $createdYear = \Carbon\Carbon::parse($createdAt)->year;
        $currentYear = now()->year;

        return max(1, $currentYear - $createdYear + 1); // Adding +1 to include current year
    }

    //________________________________________________________________
    public function getUserInfo(Request $request)
    {
        try {

            $currentUser = auth()->user();

            $user = User::with(['student', 'teacher', 'supervisor'])
                ->where('id', $currentUser->id)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $response = [
                'user_id' => $user->id,
                'full_name' => trim("{$user->name} {$user->middleName} {$user->lastName}"),
                'email' => $user->email,
                'phone' => $user->phoneNumber,
                'role' => $user->role,
                'profile_data' => []
            ];

            // Add role-specific data
            switch ($user->role) {
                case 'student':
                    $attendanceYears = $this->calculateAttendanceYears($user->created_at);
                    $response['profile_data'] = [
                        'photo' => $user->student->photo ?? '',
                        'shool-graduated-from' => $user->student->schoolGraduatedFrom ?? '',
                        'gpa' => $user->student->Gpa ?? '',
                        'class_id' => $user->student->class_id ?? ' ',
                        'class_name' => $user->student->schoolClass->className ?? ' ',
                        'number of attendance years' => $attendanceYears ?? ' ',
                    ];
                    break;

                case 'teacher':
                    $response['profile_data'] = [
                        'photo' => $user->teacher->photo ?? '',
                        'certification' => $user->teacher->certification ?? '',
                        'subject' => $user->teacher->subject ?? '',
                        'salary' => $user->teacher->salary ?? ''
                    ];
                    break;

                case 'supervisor':
                    $response['profile_data'] = [
                        'photo' => $user->supervisor->photo ?? '',
                        'certification' => $user->supervisor->certification ?? '',
                        'salary' => $user->supervisor->salary ?? '',
                    ];
                    break;
            }

            return response()->json([
                'status' => true,
                'data' => $response
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________
    public function getClassTeachers(Request $request)
    {

        try {
            //$currentUser = auth()->user();
            $validator = Validator::make($request->all(), [
                'class_id' => 'required|integer|exists:classes,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }


            $teachers = TeacherClass::with(['teachers.user:id,name,middleName,lastName,email'])  // Only load needed fields
                ->where('class_id', $request->class_id)
                ->get()
                ->map(function ($teacherClass) {
                    return [
                        'teacher_id' => $teacherClass->teacher_id,
                        'user_info' => trim($teacherClass->teachers->user->name . ' ' . $teacherClass->teachers->user->middleName . ' ' . $teacherClass->teachers->user->lastName),
                        'subject' => $teacherClass->teachers->subject  // Assuming you have subject field
                    ];
                });


            if ($teachers->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No teachers assigned to this class yet',
                    'data' => []
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'the assigned teachers to this class :',
                'data' => $teachers
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________

    public function deleteUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $currentUser = auth()->user();

            // ما تضحك عليي, هيك خطر عبالي !!!!

            if ($currentUser && $currentUser->id == $request->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot delete your own account!'
                ], 403);
            }

            $user = User::find($request->user_id);
            $user->delete();




            return response()->json([
                'status' => true,
                'message' => "user deleted !!!"
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //_________________________________________________________________
    
public function getStudentTeachersAndMates(){
    try{
            // 1. Get the authenticated user.
            $user = Auth::user();

            // 2. Find the student record associated with the user.
            // A middleware should already confirm the user is a student.
            $student = Student::where('user_id', $user->id)->first();
            $classId = $student->class_id;

            // 3. Get the Classmates.
            // Find all students in the same class, but exclude the current student's ID.
            // We use with('user') to also load the user details (like name, email) for each student.
            $classmates = Student::where('class_id', $classId)
                                 ->where('id', '!=', $student->id)
                                 ->with('user') // Assumes a 'user' relationship is defined on the Student model
                                 ->get();

            // 4. Get the Teachers and their Subjects for the class.
            // We fetch the 'TeacherClass' entries and eager load the related teacher (with their user) and the subject.
            $teacherClassEntries = TeacherClass::where('class_id', $classId)
                                     ->with(['teacher.user', 'subject'])
                                     ->get();

            // We now transform this collection to create our desired output.
            // We want a list of teachers, with the subject name injected into each teacher object.
            $teachers = $teacherClassEntries->map(function ($entry) {
                // Check if the relationships loaded correctly to prevent errors
                if ($entry->teacher && $entry->subject) {
                    // Get the teacher model instance
                    $teacherData = $entry->teacher;
                    // Add the subject name to the teacher object. 'Subject' is the column name from your ERD.
                    $teacherData->subject_name = $entry->subject->Subject;
                    return $teacherData;
                }
                return null;
            })->filter()->values(); // ->filter() removes any nulls, ->values() re-indexes the array.


            // 5. Return a successful response with the requested structure.
            return response()->json([
                'status' => true,
                'message' => 'Data retrieved successfully!',
                'teachers' => $teachers,
                'students' => $classmates,
            ]);
    }catch(\Throwable $th){
        return response()->json([
            'status'=>false,
            'message'=>$th->getMessage(),
        ]);
    }
}


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

}
