<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class ComplaintManagementController extends Controller
{
        public function addComplaint(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'complaint' => 'required|string',
                'category' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $complaint = Complaint::create([
                'user_id' => $request->user_id,
                'complaint' => $request->complaint,
                'category' => $request->status,
            ]);

            return response()->json([

                'status' => true,
                'message' => 'complaint uploaded successfully !!',
                'data' => $complaint->load('user')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //__________________________________________________________________________
    public function updateComplaint(Request $request, $complaintID)
    {

        try {
            $complaint = Complaint::findOrFail($complaintID);

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'complaint' => 'string',
                'category' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }


            $updateData = $request->only(['complaint', 'category']);
            $complaint->update($updateData);

            return response()->json([
                'status' => true,
                'message' => 'the complaint updated successfully !!',
                'data' => $complaint->load('user')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //__________________________________________________________________________
    public function deleteComplaint($complaintID)
    {

        try {
            $complaint = Complaint::findOrFail($complaintID);
            $complaint->forceDelete();

            return response()->json([
                'status' => true,
                'message' => 'complaint deleted successfully !!'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //_________________________________________________________________________
    // for the student or the one who made complaint whoever he was.
    public function getMyComplaints($userID)
    {

        try {
            if (!is_numeric($userID)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid user ID format'
                ], 400);
            }


            $complaints = Complaint::with(['User'])
                ->where('user_id', $userID)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'the complaints : ',
                'data' => $complaints->load('user')
            ]);

            if ($complaints->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No complaints found for this user',
                    'events' => []
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //_________________________________________________________________________
    // for the reviwer.
    public function getAllComplaints()
    {


        try {


            $complaints = Complaint::with(['User'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'status' => true,
                'message' => 'the complaints : ',
                'data' => $complaints->load('user')
            ]);

            if ($complaints->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No complaints found ',
                    'events' => []
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //________________________________________________________________
    /*for the reviewer to modify the complaint status from pending(default) to ('processing', 'resolved', 'rejected']), and
    to manage the complaint priority form medium(default) to ('low','high'), in addition to some Notes might 
    the reviewer write down for the complainer, like the reason of rejection ETC...
    */
    public function modifyComplaint(Request $request, $complaintID)
    {


        try {
            $complaint = Complaint::findOrFail($complaintID);

            $validator = Validator::make($request->all(), [
                'status' => 'required|string',
                'priority' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }


            $updateData = $request->only(['status', 'priority', 'notes']);
            $complaint->update($updateData);

            return response()->json([
                'status' => true,
                'message' => 'the complaint modified successfully !!',
                'data' => $complaint->load('user')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //____________________________________________________________________________________

    public function softDeleteComplaint(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'complaint_ids' => 'required|array',
                'complaint_ids.*' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $softDeletedIds = [];

            foreach ($request->complaint_ids as $complaint_id) {
                $complaint = Complaint::findOrFail($complaint_id);
                $complaint->delete();
                $softDeletedIds[] = $complaint_id;
            }

            return response()->json([
                'status' => true,
                'message' => 'Complaints deleted successfully',
                'deleted_ids' => $softDeletedIds
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to soft-delete complaints: ' . $th->getMessage(),
            ], 500);
        }
    }
}
