<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\schoolClass;
use App\Models\SchoolClass as ModelsSchoolClass;
use App\Models\TeacherClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class classesManagementController extends Controller
{
    public function createClasses(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'className' => 'regex:/^\d{1,2}-[A-Z]$/',
                'studentsNum' => 'required|integer|min:1',  // Added min:1 to ensure positive numbers
                'currentStudentNumber' => 'nullable|integer|min:0|lte:studentsNum', // Added lte (less than or equal) rule
            ], [
                'className.regex' => 'Class name must be in format like 10-A or 2-B with capital letter',
                'currentStudentNumber.lte' => 'The current student number must be less than or equal to total students number.',
                'studentsNum.min' => 'Total students must be at least 1.'
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ]);
            }

            //create the class
            $class = schoolClass::create([
                'className' => $request->className,
                'studentsNum' => $request->studentsNum,
                'currentStudentNumber' => $request->currentStudentNumber ?? 0, // Default to 0 if null
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

    public function editClass(Request $request)
    {
        try {
            //validation
            $validation = Validator::make(
                $request->all(),
                [
                    'classId' => 'required|integer|exists:classes,id',
                    'className' => 'required|regex:/^\d{1,2}-[A-Z]$/',
                    'studentsNum' => 'required|integer|min:1',
                    'currentStudentNumber' => 'nullable|integer|min:0|lte:studentsNum',
                ],
                [
                    'className.regex' => 'Class name must be in format like 10-A or 2-B with capital letter',
                    'currentStudentNumber.lte' => 'The current student number must be less than or equal to total students number.',
                    'studentsNum.min' => 'Total students must be at least 1.'
                ]
            );
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ]);
            }
            //getting the class
            $class = schoolClass::where('id', $request->classId)->first();
            //editting the class students number and current student number and name
            $class->className = $request->className;
            $class->studentsNum = $request->studentsNum;
            $class->currentStudentNumber = $request->currentStudentNumber;
            //saving the class after editing
            $class->save();
            //returning success message
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

    public function assignStudentToClass(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'studentId' => 'required|integer|exists:students,id',
                'className' => 'regex:/^\d{1,2}-[A-Z]$/',
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ]);
            }
            //getting the student
            $student = Student::where('id', $request->studentId)->first();
            //getting the class
            $class = schoolClass::where('className', $request->className)->first();
            //we need to check if the class has capacity
            $classStudents = Student::where('class_id', $class->id)->get();
            if (count($classStudents) < $class->studentsNum) {
                return response()->json([
                    'status' => false,
                    'message' => 'the class is full',
                ], 422);
            }
            //assigning the class to the student
            $student->class_id = $class->id;
            //return success message
            return Response()->json([
                'status' => true,
                'message' => 'student has been assigned to class successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

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

    public function showAllTeachers()
    {
        try {
            //getting all teachers
            $teachers = Teacher::all();
            //returning data
            return response()->json([
                'status' => true,
                'data' => $teachers,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

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
            ]);
        }
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////////

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
                                'gpa' => $student->Gpa
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
                        'class_id' => $teacherClass->class_id,
                        'class_name' => $teacherClass->SchoolClasses->className,
                        'students' => $teacherClass->SchoolClasses->students->map(function ($student) {
                            return [
                                'student_id' => $student->user_id,
                                'full_name' => trim("{$student->users->name} {$student->users->middleName} {$student->users->lastName}"),
                                'email' => $student->users->email,
                                'phone' => $student->users->phoneNumber,
                                'photo' => $student->photo,
                                'gpa' => $student->Gpa
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
                    if (!$user->teacher) {
                        return null;
                    }

                    return [
                        'teacher_id' => $user->id,
                        'full_name' => trim($user->name . ' ' . $user->middleName . ' ' . $user->lastName),
                        'email' => $user->email,
                        'phone' => $user->phoneNumber,
                        'certification' => $user->teacher->certification ?? null,
                        'photo' => $user->teacher->photo ?? null,
                        'salary' => $user->teacher->salary ?? null,
                        'classes' => $user->teacher->schoolClasses->map(function ($schoolClass) {
                            return [
                                'class_id' => $schoolClass->id,
                                'class_name' => $schoolClass->className,
                                //'subject_id' => $schoolClass->pivot->subject_id ?? null didn't work like this

                            ];
                        }),
                        'subject' => $user->teacher->subject->map(function ($subject) {
                            return [
                                'subject_id' => $subject->id,
                                'subject_name' => $subject->subjectName
                            ];
                        })


                    ];
                })
                ->filter()
                ->values();

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
                        'supervisor_id' => $user->id,
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
                'subject' => $user->teacher->subject->subjectName ?? null,
                'photo' => $teacher->teacher->photo ?? null,
                'certification' => $teacher->teacher->certification ?? null,
                'salary' => $teacher->teacher->salary ?? null,


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
    public function getUserInfo(Request $request)
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

            $user = User::with(['student', 'teacher', 'supervisor'])
                ->where('id', $request->user_id)
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
                    $response['profile_data'] = [
                        'photo' => $user->student->photo ?? null,
                        'shool-graduated-from' => $user->student->schoolGraduatedFrom ?? null,
                        'gpa' => $user->student->Gpa ?? null,
                        'class_id' => $user->student->class_id ?? null
                    ];
                    break;

                case 'teacher':
                    $response['profile_data'] = [
                        'photo' => $user->teacher->photo ?? null,
                        'certification' => $user->teacher->certification ?? null,
                        'salary' => $user->teacher->salary ?? null
                    ];
                    break;

                case 'supervisor':
                    $response['profile_data'] = [
                        'photo' => $user->supervisor->photo ?? null,
                        'certification' => $user->supervisor->certification ?? null,
                        'salary' => $user->supervisor->salary ?? null,
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
}
