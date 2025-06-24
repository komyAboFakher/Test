<?php

namespace App\Http\Controllers;

use App\Models\Nursing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NurseController extends Controller
{
    
    public function addMedicalFile(Request $request)
    {


        try {


            $validator = Validator::make($request->all(), [
                'student_id' => 'required|integer',
                'nurse_id' => 'required|integer',
                'record_date' => 'required|string',
                'record_type' => 'required|string',
                'description' => 'nullable|string',
                'treatment' => 'nullable|string',
                'notes' => 'nullable|string',
                'follow_up' => 'required|boolean',
                'follow_up_date' => 'nullable|string',
                'severity' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $medical = Nursing::create([
                'student_id' => $request->student_id,
                'nurse_id' => $request->nurse_id,
                'recored_date' => $request->record_date,
                'record_type' => $request->record_type,
                'description' => $request->description,
                'treatment' => $request->treatment,
                'notes' => $request->notes,
                'follow_up' => $request->follow_up->default(false),
                'follow_up_date' => $request->follow_up_date,
                'severity' => $request->severity,
            ]);

            return response()->json([

                'status' => true,
                'message' => 'medical file uploaded successfully !!',
                'data' => $medical->load('user')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //_______________________________________________________
    public function updateMedicalFile(Request $request, $medicalFileID)
    {

        try {

            $medicalFile = Nursing::findOrFail($medicalFileID);
            $validator = Validator::make($request->all(), [
                'record_date' => 'string',
                'record_type' => 'string',
                'description' => 'string',
                'treatment' => 'string',
                'notes' => 'string',
                'follow_up' => 'boolean',
                'follow_up_date' => 'string',
                'severity' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->all();
            $medicalFile->update($updateData);

            return response()->json([

                'status' => true,
                'message' => 'medical file updated successfully !!',
                'data' => $medicalFile->load('user')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //_______________________________________________________
    public function deleteMedicalFile(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'medical_file_ids' => 'required|array',
                'medical_file_ids.*' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $softDeletedIds = [];

            foreach ($request->medical_file_ids as $medical_file_id) {
                $medical_file_id = Nursing::findOrFail($medical_file_id);
                $medical_file_id->delete();
                $softDeletedIds[] = $medical_file_id;
            }

            return response()->json([
                'status' => true,
                'message' => 'medical files deleted successfully',
                'deleted_ids' => $softDeletedIds
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //_______________________________________________________
    public function getMedicalFiles()
    {


        try {

            $medicalFiles = Nursing::with(['User'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            if ($medicalFiles->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No reords found ',
                    'events' => []
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'the files : ',
                'data' => $medicalFiles->load('user')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //_______________________________________________________
    public function getMyMedicalFiles($studentID)
    {

        try {
            if (!is_numeric($studentID)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid user ID format'
                ], 400);
            }

            $medicalFile = Nursing::with('user')
                ->where('user_id', $studentID)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            if ($medicalFile->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No medical records for you !!!',
                    'events' => []
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'your files : ',
                'data' => $medicalFile->load('user')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
