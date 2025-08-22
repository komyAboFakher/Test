<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrController;
use App\Http\Controllers\fcmController;
use App\Http\Controllers\marksController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\libraryController;
use App\Http\Controllers\Auth\authController;
use App\Http\Controllers\communicationController;
use App\Http\Controllers\classesManagementController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\SubjectsManagementController;
use App\Http\Controllers\ComplaintManagementController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TimetablesManagementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
//authentication routes:authController
Route::post('/login', [authController::class, 'login'])->middleware('EnsureSingleLogin'); //done w request
Route::post('/createDean', [authController::class, 'createDean']); //done w request
Route::post('/createUser', [authController::class, 'createUser']);//->middleware('auth:sanctum', 'dean'); //done w request
Route::post('/createTeacher', [authController::class, 'createTeacher'])->middleware('auth:sanctum', 'dean'); //done w request
Route::post('/createSupervisor', [authController::class, 'createSupervisor'])->middleware('auth:sanctum', 'dean'); //done w request
Route::post('/createOther', [authController::class, 'createOther'])->middleware('auth:sanctum', 'dean'); //done w request
//and modify the create func to intiate student, teacher, parent and supervisor tables
Route::delete('/logout', [authController::class, 'logout'])->middleware('auth:sanctum'); //done w request
Route::post('/sendForgetPasswordOtp', [authController::class, 'sendForgetPasswordOtp']); //done w request
Route::post('/confirmForgetPasswordOtp', [authController::class, 'confirmForgetPasswordOtp']); //done w request
Route::post('/resetPassword', [authController::class, 'resetPassword']); //done w request

//pin code for users
Route::post('/createOrUpdatePinCode',[authController::class,'createOrUpdatePinCode'])->middleware('auth:sanctum');
Route::post('/checkPinCode',[authController::class,'checkPinCode'])->middleware('auth:sanctum');
Route::delete('/deletePinCode',[authController::class,'deletePinCode'])->middleware('auth:sanctum');

//student attendance management
//1-
Route::post('/studentsAttendanceForm', [StudentAttendanceController::class, 'studentsAttendanceForm'])->middleware('auth:sanctum', 'teacher'); //retrieving student data by class name to the teacher check the attendance //done w request
Route::post('/studentsAttendanceSubmit', [StudentAttendanceController::class, 'studentsAttendanceSubmit'])->middleware('auth:sanctum', 'teacher'); //done w request
//2-for supervisor
Route::post('/checkStudentAbsenceReport', [StudentAttendanceController::class, 'checkStudentAbsenceReport'])->middleware('auth:sanctum', 'supervisor'); //checking attendance report //done w request
Route::post('/checkStudentWarnings', [StudentAttendanceController::class, 'checkStudentWarnings'])->middleware('auth:sanctum', 'supervisor'); //checking student warnings and how many day they did no attend //done w request
Route::post('/submitDailyReports', [StudentAttendanceController::class, 'submitDailyReports'])->middleware('auth:sanctum', 'supervisor'); //giving the supervisor the ability to submit all the daily reports //done w request
Route::post('/incrementStudentAbsence', [StudentAttendanceController::class, 'incrementStudentAbsence'])->middleware('auth:sanctum', 'supervisor'); //giving the supervisor the ability to increment student absence num by one //done w request
Route::post('/decrementStudentAbsence', [StudentAttendanceController::class, 'decrementStudentAbsence'])->middleware('auth:sanctum', 'supervisor'); //giving the supervisor the ability to decrement student absence num by one //done 
Route::get('/getStudentAbsenceDates', [StudentAttendanceController::class, 'getStudentAbsenceDates'])->middleware('auth:sanctum', 'student'); //giving the supervisor the ability to decrement student absence num by one //done 


Route::get('/searchStudentById', [StudentAttendanceController::class, 'searchStudentById'])->middleware('auth:sanctum', 'supervisor'); //giving the supervisor the ability to see all student based on the name and class name //done w request
Route::get('/showAllStudents', [StudentAttendanceController::class, 'showAllStudents'])->middleware('auth:sanctum', 'supervisor'); //giving the supervisor the ability to see all student based on the name and class name //done w request
Route::get('/getInfo', [StudentAttendanceController::class, 'getInfo'])->middleware('auth:sanctum', 'student'); //this api gets the user info based on his role 
//2-for students
Route::get('/checkStudentAttendanceHistory', [StudentAttendanceController::class, 'checkStudentAttendanceHistory'])->middleware('auth:sanctum', 'student'); //giving the atudent the ability to check his attendance history //done w request
Route::get('/showParentSon', [StudentAttendanceController::class, 'showParentSon'])->middleware('auth:sanctum', 'parent'); //giving the parent thee ability to see his son data //done w request


//3-
Route::post('/uploadJustification', [StudentAttendanceController::class, 'uploadJustification'])->middleware('auth:sanctum', 'student'); //done w request
//4-
Route::get('/checkJustifications', [StudentAttendanceController::class, 'checkJustifications'])->middleware('auth:sanctum', 'supervisor'); //done w request


//classes managmenet

Route::Put('/createClasses', [classesManagementController::class, 'createClasses'])->middleware('auth:sanctum', 'supervisor'); //done w request
Route::get('/showClasses', [classesManagementController::class, 'showClasses'])->middleware('auth:sanctum', 'supervisor'); //done w request
Route::post('/editClass', [classesManagementController::class, 'editClass'])->middleware('auth:sanctum', 'supervisor'); //done w request
Route::post('/assignStudentToClass', [classesManagementController::class, 'assignStudentToClass'])->middleware('auth:sanctum', 'supervisor'); //done w request
Route::delete('/deleteClass', [classesManagementController::class, 'deleteClass'])->middleware('auth:sanctum', 'supervisor'); //done
Route::post('/assignTeacherToClass', [classesManagementController::class, 'assignTeacherToClass']);//->middleware('auth:sanctum', 'supervisor'); //done w request //dont froget to make it assign a specific tracher to three classes in the maximum
Route::delete('/unassignTeacherToClass', [classesManagementController::class, 'unassignTeacherToClass'])->middleware('auth:sanctum', 'supervisor'); //done w request 
Route::post('/overWriteTeacherToClass', [classesManagementController::class, 'overWriteTeacherToClass'])->middleware('auth:sanctum', 'supervisor'); //done w request
Route::get('/getStudentTeachersAndMates', [classesManagementController::class,'getStudentTeachersAndMates'])->middleware('auth:sanctum','student');//done
Route::get('/getTeacherClasses', [classesManagementController::class, 'getTeacherClasses'])->middleware('auth:sanctum', 'teacher'); //done

// for gaith, by KOMY 


Route::get('/getAllStudents', [classesManagementController::class, 'getAllStudents'])->middleware('auth:sanctum', 'komy'); // done w request
Route::get('/getPaginateStudents', [classesManagementController::class, 'getPaginateStudents'])->middleware('auth:sanctum', 'komy'); // done w request
Route::get('/getAllTeacherStudents', [classesManagementController::class, 'getAllTeacherStudents'])->middleware('auth:sanctum', 'teacher'); //done w request
Route::get('/getAllTeachers', [classesManagementController::class, 'getAllTeachers'])->middleware('auth:sanctum', 'komy'); // done w request
Route::get('/getAllSupervisors', [classesManagementController::class, 'getAllSupervisors'])->middleware('auth:sanctum', 'dean'); //done w request
Route::get('/getAllOthers', [classesManagementController::class, 'getAllOthers'])->middleware('auth:sanctum', 'dean'); //done w request
Route::post('/getSpecificStudent', [classesManagementController::class, 'getSpecificStudent'])->middleware('auth:sanctum', 'gaith'); //done w request
Route::post('/getSpecificTeacher', [classesManagementController::class, 'getSpecificTeacher'])->middleware('auth:sanctum', 'komy'); //done w request
Route::post('/getSpecificSupervisor', [classesManagementController::class, 'getSpecificSupervisor'])->middleware('auth:sanctum', 'dean'); //done w request
Route::post('/getSpecificOther', [classesManagementController::class, 'getSpecificOther'])->middleware('auth:sanctum', 'dean'); //done w request
Route::get('/getUserInfo', [classesManagementController::class, 'getUserInfo'])->middleware('auth:sanctum'); //done w request
Route::post('/getClassTeachers', [classesManagementController::class, 'getClassTeachers'])->middleware('auth:sanctum', 'supervisor'); //done w request
Route::delete('/deleteUser', [classesManagementController::class, 'deleteUser'])->middleware('auth:sanctum', 'dean'); //done w request

// subjects management

Route::Post('/createSubject', [SubjectsManagementController::class, 'createSubject'])->middleware('auth:sanctum', 'supervisor'); //done
Route::get('/getAllSubjects', [SubjectsManagementController::class, 'getAllSubjects'])->middleware('auth:sanctum', 'supervisor'); //done
Route::get('/getSubjectById', [SubjectsManagementController::class, 'getSubjectById'])->middleware('auth:sanctum', 'supervisor'); //done
Route::put('/updateSubject', [SubjectsManagementController::class, 'updateSubject'])->middleware('auth:sanctum', 'supervisor'); //done
Route::delete('/deleteSubject', [SubjectsManagementController::class, 'deleteSubject'])->middleware('auth:sanctum', 'supervisor'); //done

// the fcm
Route::post('/save-fcm-token', [fcmController::class, 'saveFcmoken']);

//timetables management
route::put('/createWeeklySchedule', [TimetablesManagementController::class, 'createWeeklySchedule']);//->middleware('auth:sanctum', 'supervisor'); //done w request
route::post('/updateWeeklySchedule', [TimetablesManagementController::class, 'updateWeeklySchedule']);//->middleware('auth:sanctum', 'supervisor'); //done w request
route::put('/uploadExamSchedule', [TimetablesManagementController::class, 'uploadExamSchedule'])->middleware('auth:sanctum', 'supervisor'); //done
route::get('/getStudentWeeklySchedule', [TimetablesManagementController::class, 'getStudentWeeklySchedule'])->middleware('auth:sanctum', 'student'); //done w request
route::post('/getClassWeeklySchcedule', [TimetablesManagementController::class, 'getClassWeeklySchcedule']);//->middleware('auth:sanctum', 'teacher ,'supervisor'); //done w request
route::post('/teachersAndTheirSessions', [TimetablesManagementController::class, 'teachersAndTheirSessions']);//->middleware('auth:sanctum', 'supervisor'); //
route::post('/generateWeeklySchedule', [TimetablesManagementController::class, 'generateWeeklySchedule']);//->middleware('auth:sanctum', 'supervisor'); //
route::delete('/deleteWeeklySchecdule', [TimetablesManagementController::class, 'deleteWeeklySchecdule']);//->middleware('auth:sanctum', 'supervisor'); //
route::get('/getStudentExamSchedule', [TimetablesManagementController::class, 'getStudentExamSchedule'])->middleware('auth:sanctum', 'student'); //
route::get('/getTeacherWeeklySchedule', [TimetablesManagementController::class, 'getTeacherWeeklySchedule'])->middleware('auth:sanctum', 'teacher'); //pdf ?= true => to give the ability to download the schedule as pdf and if it false i will only return the data
route::get('/getExamSchedule', [TimetablesManagementController::class, 'getExamSchedule'])->middleware('auth:sanctum', 'teacher', 'dean', 'supervisor'); //pdf ?= true => to give the ability to download the schedule as pdf and if it false i will only return the data
//needs to be done


//////////////////////////////////////////////////////////KOMAY STUFF/////////////////////////////////////////////////////
//marks management
Route::post('/getEmptyExcelCheatForMarks', [marksController::class, 'getEmptyExcelCheatForMarks'])->middleware('auth:sanctum', 'teacher');// done
Route::post('/uploadMarkExcelCheat', [marksController::class, 'upload'])->middleware('auth:sanctum', 'teacher'); // done
Route::post('/browseOldExcelFiles', [marksController::class, 'index'])->middleware('auth:sanctum', 'teacher'); // done
Route::get('/getTeacherClasses', [marksController::class, 'getTeacherClasses'])->middleware('auth:sanctum', 'teacher'); //done
Route::post('/getMarksProfile', [marksController::class, 'getMarksProfile'])->middleware('auth:sanctum'); // done
Route::post('/getClassMarks', [marksController::class, 'getClassMarks'])->middleware('auth:sanctum','teacher');
Route::post('/studentGetResult', [marksController::class, 'studentGetResult'])->middleware('auth:sanctum');


//events management

Route::post('/addEvent', [CommunicationController::class, 'addEvent'])->middleware('auth:sanctum', 'supervisor'); // doen with r
Route::post('/editEvent/{eventID}', [CommunicationController::class, 'editEvent'])->middleware('auth:sanctum', 'supervisor'); //done with r
Route::delete('/deleteEvent/{eventID}', [CommunicationController::class, 'deleteEvent'])->middleware('auth:sanctum', 'supervisor'); // done with r
// this api is for the users who made events(mostly supervisors), so they can see their own posts NOTE: look at the controller
Route::get('/getEvents', [CommunicationController::class, 'getEvents'])->middleware('auth:sanctum', 'supervisor'); // done with r
//this api is for the students, so they can see the whole events, i mean here the students get all events
Route::get('/getAllPublishedEvents', [CommunicationController::class, 'getAllPublishedEvents'])->middleware('auth:sanctum'); // done with r
// get event by id for share
Route::post('/shareEvent', [CommunicationController::class, 'shareEvent'])->middleware('auth:sanctum'); // done with r
// get user's posts or events
Route::post('/getUserEvents', [CommunicationController::class, 'getUserEvents'])->middleware('auth:sanctum'); // done with r


//comments management


Route::post('/addComment', [CommunicationController::class, 'addComment'])->middleware('auth:sanctum'); // done with r
Route::post('/editComment/{commentID}', [CommunicationController::class, 'editComment'])->middleware('auth:sanctum'); // done with r
Route::delete('/deleteComment/{commentID}', [CommunicationController::class, 'deleteComment'])->middleware('auth:sanctum'); // done with r
Route::get('/getEventComments/{eventID}', [CommunicationController::class, 'getEventComments'])->middleware('auth:sanctum'); // done wih r
Route::post('/reportComment', [CommunicationController::class, 'reportComment'])->middleware('auth:sanctum'); // done wih r
Route::get('/showReportedComments', [CommunicationController::class, 'showReportedComments'])->middleware('auth:sanctum', 'supervisor'); // done wih r
Route::delete('/deleteReportedComments', [CommunicationController::class, 'showReportedComments'])->middleware('auth:sanctum', 'supervisor');

//reactions
Route::post('/react', [communicationController::class, 'react'])->middleware(['auth:sanctum', 'throttle:reactions']);
Route::post('/getReactions', [communicationController::class, 'getReactions'])->middleware(['auth:sanctum']);


//complains managements

Route::post('/addComplaint', [ComplaintManagementController::class, 'addComplaint'])->middleware(['auth:sanctum']);
Route::post('/updateComplaint', [ComplaintManagementController::class, 'updateComplaint'])->middleware(['auth:sanctum']);
Route::delete('/deleteComplaint/{complaintID}', [ComplaintManagementController::class, 'deleteComplaint'])->middleware(['auth:sanctum']);
// for the guy who made complaints
Route::get('/getMyComplaints', [ComplaintManagementController::class, 'getMyComplaints'])->middleware(['auth:sanctum']);
// for the complaints reviewer
Route::post('/getAllComplaints', [ComplaintManagementController::class, 'getAllComplaints'])->middleware(['auth:sanctum','dean']);
Route::get('/getUnSeenComplaints', [ComplaintManagementController::class, 'getUnSeenComplaints'])->middleware(['auth:sanctum','dean']);
Route::post('/modifyComplaint', [ComplaintManagementController::class, 'modifyComplaint'])->middleware(['auth:sanctum','dean']);
Route::post('/seenAt', [ComplaintManagementController::class, 'seenAt'])->middleware(['auth:sanctum','dean']);
Route::delete('/softDeleteComplaint', [ComplaintManagementController::class, 'softDeleteComplaint'])->middleware(['auth:sanctum','dean']);
Route::post('/restore', [ComplaintManagementController::class, 'restore'])->middleware(['auth:sanctum','dean']);

//nursing

Route::post('/addMedicalFile', [NurseController::class, 'addMedicalFile']);
Route::post('/updateMedicalFile', [NurseController::class, 'updateMedicalFile']);
Route::delete('/deleteMedicalFile', [NurseController::class, 'deleteMedicalFile']); // soft delete
// for the nurse, searching among the files (search in flutter)
Route::get('/getMedicalFiles', [NurseController::class, 'getMedicalFiles']);
// for the students, so they can see their medical file
Route::get('/getMyMedicalFiles', [NurseController::class, 'getMyMedicalFiles']);

// library management at the school

Route::post('/createBook', [libraryController::class, 'createBook'])->middleware(['auth:sanctum', 'library']);
Route::post('/updateBook/{bookID}', [libraryController::class, 'updateBook'])->middleware(['auth:sanctum', 'library']);
Route::delete('/deleteBook/{bookID}', [libraryController::class, 'deleteBook'])->middleware(['auth:sanctum', 'library']);
Route::get('/getBorrowOrder', [libraryController::class, 'getBorrowOrder'])->middleware(['auth:sanctum', 'library']);
Route::get('/showBooks', [libraryController::class, 'showBooks'])->middleware(['auth:sanctum']);
Route::post('/showBookBySerrialNumber', [libraryController::class, 'showBookBySerrialNumber'])->middleware(['auth:sanctum']);
Route::post('/showBookBorrowers', [libraryController::class, 'showBookBorrowers'])->middleware(['auth:sanctum','library']);
//borrow management
Route::post('/borrow', [libraryController::class, 'borrow'])->middleware(['auth:sanctum']);
Route::post('/modifyBorrow', [libraryController::class, 'modifyBorrow'])->middleware(['auth:sanctum','library']);
// books 

// permissions managements

Route::post('assignPermission',[PermissionController::class,'assignPermission'])->middleware(['auth:sanctum','dean']);
// show the whole permissions we have in the system
Route::get('showPermissions',[PermissionController::class,'showPermissions'])->middleware(['auth:sanctum','dean']);
// show all the users group by there permissions
Route::get('showAllUserPermissions',[PermissionController::class,'showAllUserPermissions'])->middleware(['auth:sanctum','dean']);
// show some user permissions
Route::get('showUserPermissions',[PermissionController::class,'showUserPermissions'])->middleware(['auth:sanctum','dean']);
Route::post('updateAssignPermission',[PermissionController::class,'updateAssignPermission'])->middleware(['auth:sanctum','dean']);
Route::delete('deleteAssignPermission',[PermissionController::class,'deleteAssignPermission'])->middleware(['auth:sanctum','dean']);






//komy
/*
php artisan db:seed
php artisan db:seed --class=ClassSeeder
php artisan db:seed --class=TeacherSeeder
php artisan db:seed --class=SupervisorSeeder
php artisan db:seed --class=SubjectSeeder
php artisan db:seed --class=StudentSeeder

//
// don't try this seeder, it is not important, just try to assign teacher to class (classManagementController)
//
php artisan db:seed --class=TeacherClassSeeder

*/