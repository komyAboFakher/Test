<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Validator;
use PDO;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class SubjectsManagementController extends Controller
{
    public function createSubject(Request $request)
    {
        try {
            //getting allowed subjects from config
            $allowedSubjects = config('subjects.allowed');
            //valdiation
            $validation = Validator::make($request->all(), [
                'subjectName' => ['required', 'string', Rule::in($allowedSubjects)],
                'minMark' => 'required|integer|min:0|max:100',
                'maxMark' => 'required|integer|min:0|max:100',
                'grade' => 'required|string|in:1,2,3,4,5,6,7,8,9,10,11,12',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }
            //checking if min mark is bigger than max mark
            if ($request->minMark > $request->maxMark) {
                return response()->json([
                    'status' => false,
                    'message' => 'minMark cannot be greater than maxMark',
                ], 422);
            }
            //we wanna check if there is already a row defined for the same subject and same grade
            $alreadyExistingSubject=Subject::where('subjectName',$request->subjectName)->where('grade',$request->grade)->first();
            if($alreadyExistingSubject){
                return Response()->json([
                    'status'=>false,
                    'messsage'=>'you have already defined this subject',
                ],422);
            }
            //now we wanna create the subject
            $subject = Subject::firstOrCreate([
                'subjectName' => $request->subjectName,
                'minMark' => $request->minMark,
                'maxMark' => $request->maxMark,
                'grade' => $request->grade,
            ]);
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'subject has been created successfully!',
                'data' => $subject,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }
    public function getAllSubjects()
    {
        try {
            //getting the subjects
            $subjects = Subject::all();
            //returning data
            return response()->json([
                'status' => true,
                'data' => $subjects,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getmessage()
            ]);
        }
    }

    public function getSubjectById(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'subjectId' => 'required|integer|exists:subjects,id',
            ]);

            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->erros(),
                ], 422);
            }

            //getting the subject
            $subject = Subject::where('id', $request->subjectId)->first();
            //returniong data
            return response()->json([
                'status' => true,
                'data' => $subject,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }


    public function updateSubject(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'subjectId' => 'required|integer|exists:subjects,id',
                'minMark' => 'required_without:maxMark|integer|min:0|max:100',
                'maxMark' => 'required_without:minMark|integer|min:0|max:100',
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }
            if ($request->has('minMark') && $request->has('maxMark') && $request->minMark > $request->maxMark) {
                return response()->json([
                    'status' => false,
                    'message' => 'minMark cannot be greater than maxMark',
                ], 422);
            }

            //first of all we wanna get the subject from the db
            $subject = Subject::where('id', $request->subjectId)->first();
            if(!$subject){
                return response()->json([
                    'status'=>false,
                    'message'=>'the subject has not been found',
                ]);
            }
            
            if ($request->has('minMark')) {
                $subject->minMark = $request->minMark;
            }

            if ($request->has('maxMark')) {
                $subject->maxMark = $request->maxMark;
            }

            $subject->save();
            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'the subject has been updated successfully!',
                'data' => $subject,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function deleteSubject(Request $request)
    {
        try {
            //validation
            $validation = Validator::make($request->all(), [
                'subjectId' => 'required|integer|exists:subjects,id',
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validation->errors(),
                ], 422);
            }

            //deleting the subject
            Subject::where('id', $request->subjectId)->delete();

            //returning success message
            return response()->json([
                'status' => true,
                'message' => 'subject has been deleted successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),

            ]);
        }
    }
}
