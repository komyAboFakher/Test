<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\marksController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\Auth\authController;
use App\Http\Controllers\communicationController;
use App\Http\Controllers\classesManagementController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\ComplaintManagementController;
use App\Http\Controllers\libraryController;
use App\Http\Controllers\PrController;
use App\Http\Controllers\SubjectsManagementController;
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
Route::post('/createUser', [authController::class, 'createUser'])->middleware('auth:sanctum', 'dean'); //done w request
Route::post('/createTeacher', [authController::class, 'createTeacher'])->middleware('auth:sanctum', 'dean'); //done w request
Route::post('/createSupervisor', [authController::class, 'createSupervisor'])->middleware('auth:sanctum', 'dean'); //done w request
//and modify the create func to intiate student, teacher, parent and supervisor tables
Route::delete('/logout', [authController::class, 'logout'])->middleware('auth:sanctum'); //done w request
Route::post('/sendForgetPasswordOtp', [authController::class, 'sendForgetPasswordOtp']); //done w request
Route::post('/confirmForgetPasswordOtp', [authController::class, 'confirmForgetPasswordOtp']); //done w request
Route::post('/resetPassword', [authController::class, 'resetPassword']); //done w request


//student attendance management
//1-
Route::get('/studentsAttendanceForm', [StudentAttendanceController::class, 'studentsAttendanceForm'])->middleware('auth:sanctum', 'teacher'); //retrieving student data by class name to the teacher check the attendance //done w request
Route::post('/studentsAttendanceSubmit', [StudentAttendanceController::class, 'studentsAttendanceSubmit'])->middleware('auth:sanctum', 'teacher'); //done w request
//2-for supervisor
Route::get('/checkStudentAbsenceReport', [StudentAttendanceController::class, 'checkStudentAbsenceReport'])->middleware('auth:sanctum', 'supervisor'); //checking attendance report //done w request
Route::get('/checkStudentWarnings', [StudentAttendanceController::class, 'checkStudentWarnings'])->middleware('auth:sanctum', 'supervisor'); //checking student warnings and how many day they did no attend //done w request
Route::post('/submitDailyReports', [StudentAttendanceController::class, 'submitDailyReports'])->middleware('auth:sanctum', 'supervisor'); //giving the supervisor the ability to submit all the daily reports //done w request
Route::post('/incrementStudentAbsence', [StudentAttendanceController::class, 'incrementStudentAbsence'])->middleware('auth:sanctum', 'supervisor'); //giving the supervisor the ability to increment student absence num by one //done w request

Route::get('/searchStudentByName', [StudentAttendanceController::class, 'searchStudentByName'])->middleware('auth:sanctum', 'supervisor'); //giving the supervisor the ability to see all student based on the name and class name //done w request
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
Route::post('/assignTeacherToClass', [classesManagementController::class, 'assignTeacherToClass'])->middleware('auth:sanctum', 'supervisor'); //done w request //dont froget to make it assign a specific tracher to three classes in the maximum
Route::delete('/unassignTeacherToClass', [classesManagementController::class, 'unassignTeacherToClass'])->middleware('auth:sanctum', 'supervisor'); //done w request 
Route::post('/overWriteTeacherToClass', [classesManagementController::class, 'overWriteTeacherToClass'])->middleware('auth:sanctum', 'supervisor'); //done w request

// for gaith, by KOMY 


Route::get('/getAllStudents', [classesManagementController::class, 'getAllStudents'])->middleware('auth:sanctum', 'komy'); // done w request
Route::get('/getPaginateStudents', [classesManagementController::class, 'getPaginateStudents'])->middleware('auth:sanctum', 'komy'); // done w request
Route::get('/getAllTeacherStudents', [classesManagementController::class, 'getAllTeacherStudents'])->middleware('auth:sanctum', 'teacher'); //done w request
Route::get('/getAllTeachers', [classesManagementController::class, 'getAllTeachers'])->middleware('auth:sanctum', 'komy'); // done w request
Route::get('/getAllSupervisors', [classesManagementController::class, 'getAllSupervisors'])->middleware('auth:sanctum', 'dean'); //done w request
Route::post('/getSpecificStudent', [classesManagementController::class, 'getSpecificStudent'])->middleware('auth:sanctum', 'gaith'); //done w request
Route::post('/getSpecificTeacher', [classesManagementController::class, 'getSpecificTeacher'])->middleware('auth:sanctum', 'komy'); //done w request
Route::post('/getSpecificSupervisor', [classesManagementController::class, 'getSpecificSupervisor'])->middleware('auth:sanctum', 'dean'); //done w request
Route::get('/getUserInfo', [classesManagementController::class, 'getUserInfo'])->middleware('auth:sanctum'); //done w request
Route::post('/getClassTeachers', [classesManagementController::class, 'getClassTeachers'])->middleware('auth:sanctum', 'supervisor'); //done w request
Route::delete('/deleteUser', [classesManagementController::class, 'deleteUser'])->middleware('auth:sanctum', 'dean'); //done w request

// subjects management

Route::Put('/createSubject', [SubjectsManagementController::class, 'createSubject'])->middleware('auth:sanctum', 'supervisor'); //done
Route::get('/getAllSubjects', [SubjectsManagementController::class, 'getAllSubjects'])->middleware('auth:sanctum', 'supervisor'); //done
Route::get('/getSubjectById', [SubjectsManagementController::class, 'getSubjectById'])->middleware('auth:sanctum', 'supervisor'); //done
Route::post('/updateSubject', [SubjectsManagementController::class, 'updateSubject'])->middleware('auth:sanctum', 'supervisor'); //done
Route::delete('/deleteSubject', [SubjectsManagementController::class, 'deleteSubject'])->middleware('auth:sanctum', 'supervisor'); //done




//timetables management
route::put('/createWeeklySchedule', [TimetablesManagementController::class, 'createWeeklySchedule'])->middleware('auth:sanctum', 'supervisor'); //done w request
route::put('/uploadExamSchedule', [TimetablesManagementController::class, 'uploadExamSchedule'])->middleware('auth:sanctum', 'supervisor'); //done
route::get('/getStudentWeeklySchedule', [TimetablesManagementController::class, 'getStudentWeeklySchedule'])->middleware('auth:sanctum', 'student'); //done w request
//needs to be done
route::get('/getStudentExamSchedule', [TimetablesManagementController::class, 'getStudentExamSchedule'])->middleware('auth:sanctum', 'student'); //
route::get('/getTeacherWeeklySchedule', [TimetablesManagementController::class, 'getTeacherWeeklySchedule'])->middleware('auth:sanctum', 'teacher'); //pdf ?= true => to give the ability to download the schedule as pdf and if it false i will only return the data
route::get('/getTeacherExamSchedule', [TimetablesManagementController::class, 'getTeacherExamSchedule'])->middleware('auth:sanctum', 'teacher'); //


//////////////////////////////////////////////////////////KOMAY STUFF/////////////////////////////////////////////////////
//marks management
Route::get('/getAllTeacherInfo', [marksController::class, 'getAllTeacherInfo'])->middleware('auth:sanctum', 'teacher');
Route::post('/getEmptyExcelCheatForMarks', [marksController::class, 'getEmptyExcelCheatForMarks'])->middleware('auth:sanctum', 'teacher');
Route::post('/uploadMarkExcelCheat', [marksController::class, 'uploadMarkExcelCheat'])->middleware('auth:sanctum', 'teacher');
Route::post('/studentGetResult', [marksController::class, 'studentGetResult'])->middleware('auth:sanctum', 'student');
//Route::get('/studentGetResult/{studentID}', [marksController::class, 'studentGetResult'])->middleware('auth:sanctum', 'gaith');

//events management

Route::post('/addEvent', [CommunicationController::class, 'addEvent'])->middleware('auth:sanctum', 'supervisor'); // doen with r
Route::post('/editEvent/{eventID}', [CommunicationController::class, 'editEvent'])->middleware('auth:sanctum', 'supervisor'); //done with r
Route::delete('/deleteEvent/{eventID}', [CommunicationController::class, 'deleteEvent'])->middleware('auth:sanctum', 'supervisor'); // done with r
// this api is for the users who made events(mostly supervisors), so they can see their own posts NOTE: look at the controller
Route::get('/getEvents', [CommunicationController::class, 'getEvents'])->middleware('auth:sanctum', 'supervisor'); // done with r
//this api is for the students, so they can see the whole events, i mean here the students get all events
Route::get('/getAllPublishedEvents', [CommunicationController::class, 'getAllPublishedEvents'])->middleware('auth:sanctum'); // done with r


//comments management


Route::post('/addComment', [CommunicationController::class, 'addComment'])->middleware('auth:sanctum');// done with r
Route::post('/editComment/{commentID}', [CommunicationController::class, 'editComment'])->middleware('auth:sanctum');// done with r
Route::delete('/deleteComment/{commentID}', [CommunicationController::class, 'deleteComment'])->middleware('auth:sanctum');// done with r
Route::get('/getEventComments/{eventID}', [CommunicationController::class, 'getEventComments'])->middleware('auth:sanctum');// done wih r
Route::post('/reportComment', [CommunicationController::class, 'reportComment'])->middleware('auth:sanctum');// done wih r
Route::get('/showReportedComments/{eventID}', [CommunicationController::class, 'showReportedComments'])->middleware('auth:sanctum', 'supervisor');// done wih r
Route::delete('/deleteReportedComments', [CommunicationController::class, 'showReportedComments'])->middleware('auth:sanctum', 'supervisor');

//reactions
Route::post('/react',[communicationController::class, 'react']) ->middleware(['auth:sanctum', 'throttle:reactions']);
Route::post('/getReactions',[communicationController::class, 'getReactions']) ->middleware(['auth:sanctum', 'throttle:reactions']);
//Route::post('/react',[communicationController::class, 'react']) ->middleware(['auth:sanctum', 'throttle:reactions']);

//complains managements

Route::post('/addComplaint', [ComplaintManagementController::class, 'addComplaint']);
Route::post('/updateComplaint', [ComplaintManagementController::class, 'updateComplaint']);
Route::delete('/deleteComplaint', [ComplaintManagementController::class, 'deleteComplaint']);
// for the guy who made complaints
Route::get('/getMyComplaints', [ComplaintManagementController::class, 'getMyComplaints']);
// for the complaints reviewer
Route::get('/getAllComplaints', [ComplaintManagementController::class, 'getAllComplaints']);
Route::post('/modifyComplaint', [ComplaintManagementController::class, 'modifyComplaint']);
Route::delete('/softDeleteComplaint', [ComplaintManagementController::class, 'softDeleteComplaint']);

//nursing

Route::post('/addMedicalFile', [NurseController::class, 'addMedicalFile']);
Route::post('/updateMedicalFile', [NurseController::class, 'updateMedicalFile']);
Route::delete('/deleteMedicalFile', [NurseController::class, 'deleteMedicalFile']); // soft delete
// for the nurse, searching among the files (search in flutter)
Route::get('/getMedicalFiles', [NurseController::class, 'getMedicalFiles']);
// for the students, so they can see their medical file
Route::get('/getMyMedicalFiles', [NurseController::class, 'getMyMedicalFiles']);


// library management at the school

Route::post('/createBook', [libraryController::class, 'createBook']);
Route::post('/updateBook/{bookID}', [libraryController::class, 'updateBook']);
Route::delete('/deleteBook/{bookID}', [libraryController::class, 'deleteBook']);
Route::get('/showBook', [libraryController::class, 'showBook']);
//borrow management
Route::post('/borrow', [libraryController::class, 'borrow']);
Route::post('/modifyBorrow', [libraryController::class, 'modifyBorrow']);

// public relations managements
Route::post('/publish', [PrController::class, 'publish']);
Route::post('/updatePublish', [PrController::class, 'updatePublish']);
Route::post('/deletePublish', [PrController::class, 'deletePublish']);
Route::post('/showPublish', [PrController::class, 'showPublish']);





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