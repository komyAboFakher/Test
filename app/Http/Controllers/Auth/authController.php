<?php

namespace App\Http\Controllers\Auth;

use Throwable;
use Carbon\Carbon;
use App\Models\Clas;
use App\Models\User;
use App\Models\Other;
use App\Models\Parents;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Supervisor;
use App\Models\PasswordOtp;
use App\Models\schoolClass;
use Jenssegers\Agent\Agent;
use App\Models\TeacherClass;
use Illuminate\Http\Request;
use App\Models\AbsenceStudent;
use App\Mail\LoginNotification;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Jobs\sendUserNotification;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendLoginNotification;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Controllers\fcmController;
use App\Models\Permission;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;




class authController extends Controller
{
    public function setUsers2FA()
    {
        try {
            //getting the user
            $authUser = Auth::user();
            $user = User::where('id', $authUser->id)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated.',
                ], 401);
            }
            //setting the 2FA
            $user->TFA = true;
            //saving the suer
            $user->save();
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'the 2FA has been set successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function unSetUsers2FA()
    {
        try {
            //getting the user
            $authUser = Auth::user();
            $user = User::where('id', $authUser->id)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated.',
                ], 401);
            }
            //setting the 2FA
            $user->TFA = false;
            //saving the suer
            $user->save();
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'the 2FA has been unset successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function createUser(Request $request)
    {
        try {
            //validation
            $validateUser = Validator::make(
                $request->all(),
                [
                    //student validation
                    'name' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'middleName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'lastName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'phoneNumber' => 'required|string|regex:/^\+?[0-9\s\-]{10,15}$/|unique:users,phoneNumber',
                    'email' => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i|unique:users,email',
                    'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
                    'unique:users,email',
                    'password' => 'required|string|min:8',
                    'role' => 'required|string|in:parent,student,teacher,supervisor,dean',
                    'previousCertification' => 'required|mimes:pdf|max:2048',
                    'photo' => 'mimes:png|max:2048',
                    'class' =>  'regex:/^\d{1,2}-[A-Z]$/',
                    //parent validation
                    'parentName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'parentMiddleName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'parentLastName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'parentPhoneNumber' => 'required|string|regex:/^\+?[0-9\s\-]{10,15}$/|unique:users,phoneNumber',
                    'parentEmail' => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i|unique:users,email',
                    'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
                    'unique:users,email',
                    'parentPassword' => 'required|string|min:8',
                    'parentJob' => 'required|string|max:30',
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors(),
                ], 404);
            }

            DB::transaction(function () use ($request) {
                // Store uploaded files
                $photoPath = $request->file('photo')->store('photos', 'public');
                $certificationPath = $request->file('previousCertification')->store('certifications', 'public');

                // Create parent user
                $parentUser = User::create([
                    'name' => $request->parentName,
                    'middleName' => $request->parentMiddleName,
                    'lastName' => $request->parentLastName,
                    'phoneNumber' => $request->parentPhoneNumber,
                    'email' => $request->parentEmail,
                    'role' => 'parent',
                    'password' => Hash::make($request->parentPassword),
                ]);

                // Create parent record
                $parent = Parents::create([
                    'user_id' => $parentUser->id,
                    'name' => $request->parentName,
                    'middle_name' => $request->parentMiddleName,
                    'last_name' => $request->parentLastName,
                    'job' => $request->parentJob,
                ]);

                // Create student user
                $user = User::create([
                    'name' => $request->name,
                    'middleName' => $request->middleName,
                    'lastName' => $request->lastName,
                    'phoneNumber' => $request->phoneNumber,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'student',
                ]);

                Mail::to($user->email)->send(
                    new \App\Mail\TeacherWelcomeMail($request->password, $user->email)
                );

                // Get class
                $class = schoolClass::where('className', $request->class)->first();

                // Create student record with parent_id
                $student = Student::create([
                    'user_id' => $user->id,
                    'class_id' => $class->id,
                    'schoolGraduatedFrom' => $certificationPath,
                    'photo' => $photoPath,
                    'parent_id' => $parent->id,
                ]);

                // Link student to parent (if needed)
                $parent->update(['student_id' => $student->id]);

                // Update class student count
                if ($student->class_id) {
                    $studentClass = schoolClass::findOrFail($student->class_id);

                    if ($studentClass->studentsNum == $studentClass->currentStudentNumber) {
                        throw new \Exception("The current class has reached its maximum size.");
                    }

                    $studentClass->currentStudentNumber = $studentClass->currentStudentNumber ? $studentClass->currentStudentNumber + 1 : 1;
                    $studentClass->save();
                }

                // Create absence record
                AbsenceStudent::create([
                    'student_id' => $student->id,
                    'absence_num' => 5,
                    'warning' => 0,
                ]);

                // Dispatch notification
                sendUserNotification::dispatch($user, $parentUser->email, $request->password);
            });


            //success message
            return response()->json([
                'status' => true,
                'message' => 'user created successfully',
                //'photoUrl' => asset('storage/' . $photoPath),
                //'certificationUrl' => asset('storage/' . $certificationPath),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //___________________________________________________________________________________________________

    public function createTeacher(Request $request)
    {
        try {
            $allowedSubjects = config('subjects.allowed');
            //validation
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'middleName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'lastName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'phoneNumber' => 'required|string|regex:/^\+?[0-9\s\-]{10,15}$/|unique:users,phoneNumber',
                    'email' => 'required',
                    'email',
                    'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
                    'unique:users,email',
                    'password' => 'required|string|min:8',
                    'role' => 'required|string|in:teacher',
                    'certification' => 'required|mimes:pdf|max:2048',
                    'photo' => 'mimes:png|max:2048',
                    'subject' => ['required', 'string', Rule::in($allowedSubjects)],
                    'salary' => 'numeric|min:0',
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors(),
                ], 422);
            }
            DB::transaction(function () use ($request) {
                //intiating photo URL
                $photoPath = $request->file('photo')->store('photos', 'public');
                //intiating certification URL
                $certificationPath = $request->file('certification')->store('certifications', 'public');

                //create user
                $user = User::create([
                    'name' => $request->name,
                    'middleName' => $request->middleName,
                    'lastName' => $request->lastName,
                    'phoneNumber' => $request->phoneNumber,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => $request->role,
                ]);

                Mail::to($user->email)->send(
                    new \App\Mail\TeacherWelcomeMail($request->password, $user->email)
                );
                //creating a row in the role table
                if ($request->role == 'teacher') {
                    Teacher::create([
                        'user_id' => $user->id,
                        'certification' => $certificationPath,
                        'photo' => $photoPath,
                        'subject' => $request->subject,
                        'salary' => $request->salary,
                    ]);
                }
            });


            //success message
            return response()->json([
                'status' => true,
                'message' => 'user created successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function createSupervisor(Request $request)
    {
        try {
            //validation
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'middleName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'lastName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'phoneNumber' => 'required|string|regex:/^\+?[0-9\s\-]{10,15}$/|unique:users,phoneNumber',
                    'email' => 'required',
                    'email',
                    'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
                    'unique:users,email',
                    'password' => 'required|string|min:8',
                    'role' => 'required|string|in:supervisor',
                    'certification' => 'required|mimes:pdf|max:2048',
                    'photo' => 'mimes:png|max:2048',
                    'salary' => 'numeric|min:0',
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors(),
                ], 422);
            }
            DB::transaction(function () use ($request) {
                //intiating photo URL
                $photoPath = $request->file('photo')->store('photos', 'public');
                //intiating certification URL
                $certificationPath = $request->file('certification')->store('certifications', 'public');

                //create user
                $user = User::create([
                    'name' => $request->name,
                    'middleName' => $request->middleName,
                    'lastName' => $request->lastName,
                    'phoneNumber' => $request->phoneNumber,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => $request->role,
                ]);

                Mail::to($user->email)->send(
                    new \App\Mail\TeacherWelcomeMail($request->password, $user->email)
                );
                //creating a row in the role table
                if ($request->role == 'supervisor') {
                    Supervisor::create([
                        'user_id' => $user->id,
                        'certification' => $certificationPath,
                        'photo' => $photoPath,
                        'salary' => $request->salary,
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'the role you have input is not right!',
                    ]);
                }
            });
            //success message
            return response()->json([
                'status' => true,
                'message' => 'user created successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function createDean(Request $request)
    {
        try {
            //validation
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'middleName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'lastName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
                    'phoneNumber' => 'required|string|regex:/^\+?[0-9\s\-]{10,15}$/|unique:users,phoneNumber',
                    'email' => 'required|string',
                    'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
                    'unique:users,email',
                    'password' => 'required|string|min:8',
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors(),
                ], 404);
            }
            //create user
            $user = User::create([
                'name' => $request->name,
                'middleName' => $request->middleName,
                'lastName' => $request->lastName,
                'phoneNumber' => $request->phoneNumber,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'dean',
            ]);

            //success message
            return response()->json([
                'status' => true,
                'message' => 'dean created successfully',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function login(Request $request)
    {
        try {
            // --- BONUS: Corrected Validation ---
            $validateUser = Validator::make($request->all(), [
                'email' => ['required','email','exists:users,email'], // Correct for login: user must exist
                // 'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i' // Optional: if you only allow gmail
                'password' => 'required|string|min:8',
                'deviceType' => 'required|string|in:web,mobile',
                'fcmToken' => 'sometimes|string'
            ]);

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors(),
                ], 400); // 400 is more appropriate for validation errors
            }

            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            $user = User::with('UserPermission.permission')->where('email', $request->email)->first();
           
            

            if ($user->TFA == true) {
                $verificationCode = rand(10000, 99999);
            }
            // --- Dispatch the Job for Background Processing ---
            $agent = new Agent();
            $deviceDetails = "{$agent->platform()} - {$agent->browser()}";
            $loginTime = now()->format('Y-m-d H:i:s');
            $ip = $request->ip();

            // Dispatch the job to the queue
            SendLoginNotification::dispatch($user, $deviceDetails, $loginTime, $ip);

            //saving the fcm token
            if ($request->fcmToken != null) {
                $fcmSave = fcmController::saveFcmToken($request->fcmToken, $user->id);
                if (!$fcmSave) {
                    return response()->json([
                        'status' => false,
                        'message' => 'fcm is not saved!'
                    ], 422);
                }
            }
            // --- Return Response Immediately ---
            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $user->createToken($request->deviceType)->plainTextToken,
                'data' => [
                    'user' => $user,
                    
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Authenticate the user using the guard 
            $user = Auth::user();
            //destroying the token
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                $user = $token->tokenable;
                $user->tokens()->delete();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Token not found'
                ], 404);
            }
            //success message
            return response()->json([
                'status' => true,
                'message' => 'Logged out successfully'
            ], 200);
        } catch (Throwable $th) {
            return response()->json([
                'status' => false,
                'messsage' => $th->getMessage()
            ], 500);
        }
    }


    public function sendForgetPasswordOtp(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'email' => 'required'
            ]);

            // Find user
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'We sent a code to your email. If you do not receive a code, the email may not match an existing account.',
                    'status' => 404,
                    'data' => []
                ], 404);
            }

            // Generate OTP
            $verificationCode = rand(10000, 99999);
            //echo Carbon::now();
            //deleting the old otp to same person
            $oldOtp = PasswordOtp::where('user_id', $user->id)->delete();
            //save the otp in the migration
            PasswordOtp::create([
                'user_id' => $user->id,
                'otp' => $verificationCode,
                'expires_at' => Carbon::now()->addminutes(10),
            ]);

            // Send email with OTP
            Mail::raw("Your verification code is: {$verificationCode}", function ($message) use ($request) {
                $message->from('majdsalit76@gmail.com', 'Tutors')
                    ->to($request->email)
                    ->subject('Verification Code');
            });

            // Prepare response data
            return response()->json([
                'status' => 200,
                'message' => 'OTP sent successfully.',
                'data' => ['verificationCode' => $verificationCode]
            ], 200);
        } catch (Throwable $exception) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred.',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    public function confirmForgetPasswordOtp(Request $request)
    {
        try {
            //validating input
            $validateOtp = Validator::make($request->all(), [
                'otp' => 'required|digits:5|string|min:5|max:5',
                'email' => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
            ]);

            if ($validateOtp->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateOtp->errors(),
                ], 404);
            }
            //getting the user
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'user not found',
                ], 404);
            }
            //getting the otp that is linked to this user id
            $otp = PasswordOtp::where('user_id', $user->id)->first();
            if (!$otp) {
                return response()->json([
                    'status' => false,
                    'message' => 'otp not found',
                ], 404);
            }
            //checking if the otp is expired
            if (now()->greaterThan($otp->expires_at)) {
                return response()->json([
                    'status' => false,
                    'message' => 'this otp is expired',
                ]);
            }
            //checking if the sent otp is right
            if ($otp->OTP == $request->otp) {
                PasswordOtp::where('user_id', $user->id)->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'the otp is right redirect to the reset password page'
                ], 200);
            }
            $otp->attempts -= 1;
            if ($otp->attempts == 0) {
                PasswordOtp::where('user_id', $user->id)->delete();
                return response()->json([
                    'status' => false,
                    'message' => 'you have reached your attempts limit, request a new otp',
                ], 400);
            }
            $otp->save();
            //$this->confirmForgetPasswordOtp($request);
            return response()->json([
                'status' => false,
                'message' => 'the otp doesnt match our record!'
            ], 400);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }
    public function resetPassword(Request $request)
    {
        //validate
        try {
            $validatePassword = Validator::make($request->all(), [
                'password' => 'required|string|min:8',
                'email' => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
            ]);
            if ($validatePassword->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validatePassword->errors(),
                ]);
            }
            //getting the user
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }
            //checking if the password is already user
            hash::make($request->password);
            if (Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'this password is already used',
                ], 422);
            }
            //changig that password and saving
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'password updated successfully'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }


    public function createOrUpdatePinCode(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'pinCode' => 'required|string|digits:4|same:confirmedPinCode',
                'confirmedPinCode' => 'required|string'
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }
            //getting the user id
            $authUser = Auth::user();
            $user = User::where('id', $authUser->id)->first();
            $user->pinCode = Hash::make($request->pinCode);
            $user->save();
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'PIN Code created successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function checkPinCode(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'pinCode' => 'required|string|digits:4'
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }
            //getting the user id
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'unauthenticated',
                ], 401);
            }

            if (Hash::check($request->pinCode, $user->pinCode)) {
                return response()->json([
                    'status' => true,
                    'message' => 'the PIN CODE is verfied successfully!'
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'the PIN CODE you have provided doesnt match our record!',
                ], 401);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function deletePinCode()
    {
        try {
            //getting the suer
            $authUser = Auth::user();

            if (!$authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'unauthenticated',
                ]);
            }
            $user = User::where('id', $authUser->id)->first();

            //deleting the pin code
            if ($user->pinCode != null) {
                $user->pinCode = null;
                $user->save();

                //returnin success message
                return response()->json([
                    'status' => true,
                    'message' => 'your pin code has been deleted successfully!',
                ]);
            } else {
                return response()->json([
                    'status' => true,
                    'message' => 'you already doesnt have a pin code',
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }


    public function createOther(Request $request)
    {
        // Step 1: Validate input
        $validateUser = Validator::make($request->all(), [
            'name' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
            'middleName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
            'lastName' => 'required|string|max:255|regex:/^[a-zA-Z]+$/',
            'phoneNumber' => 'required|string|regex:/^\+?[0-9\s\-]{10,15}$/|unique:users,phoneNumber',
            'email' => [
                'required',
                'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i',
                'unique:users,email',
            ],
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:other',
            'certification' => 'required|mimes:pdf|max:2048',
            'photo' => 'required|mimes:png|max:4096',
            'salary' => 'numeric|min:0',
        ]);

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validateUser->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Step 2: Store files
            $photoPath = $request->hasFile('photo')
                ? $request->file('photo')->store('photos', 'public')
                : null;

            $certificationPath = $request->file('certification')->store('certifications', 'public');

            // Step 3: Create user
            $user = User::create([
                'name' => $request->name,
                'middleName' => $request->middleName,
                'lastName' => $request->lastName,
                'phoneNumber' => $request->phoneNumber,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);


            Mail::to($user->email)->send(
                new \App\Mail\TeacherWelcomeMail($request->password, $user->email)
            );

            // Step 5: Create role-specific record
            Other::create([
                'user_id' => $user->id,
                'certification' => $certificationPath,
                'photo' => $photoPath,
                'salary' => $request->salary,
            ]);

            DB::commit();

            // Step 6: Return success
            return response()->json([
                'status' => true,
                'message' => 'user created successfully',
                'photoUrl' => asset('storage/' . $photoPath),
                'certificationUrl' => asset('storage/' . $certificationPath),
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();


            if (isset($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            if (isset($certificationPath)) {
                Storage::disk('public')->delete($certificationPath);
            }

            return response()->json([
                'status' => false,
                'message' => 'Transaction failed: ' . $th->getMessage(),
            ], 500);
        }
    }
}
