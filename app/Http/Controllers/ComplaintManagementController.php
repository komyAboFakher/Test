<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;
use Blaspsoft\Blasp\Facades\Blasp;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class ComplaintManagementController extends Controller
{
    public function addComplaint(Request $request)
    {

        try {

            $currentUser = auth()->user()->id;
            $validator = Validator::make($request->all(), [
                //'user_id' => 'required|integer',
                'complaint' => 'required|string',
                'category' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $blaspResult = Blasp::check($request->complaint);

            if ($blaspResult->hasProfanity()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please avoid using inappropriate language ',
                ], 403);
            }

            $complaint = Complaint::create([
                'user_id' => $currentUser,
                'complaint' => $request->complaint,
                'category' => $request->category,
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
    public function updateComplaint(Request $request)
    {

        try {


            $validator = Validator::make($request->all(), [
                //'user_id' => 'required|integer',
                'complaint_id' => 'required|integer|exists:complaints,id',
                'complaint' => 'string',
                'category' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }


            $complaint = Complaint::findOrFail($request->complaint_id);

            $blaspResult = Blasp::check($request->complaint);

            if ($blaspResult->hasProfanity()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please avoid using inappropriate language ',
                ], 403);
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
            $currentUser = auth()->user()->id;
            $complaint = Complaint::findOrFail($complaintID);
            if ($complaint->user_id != $currentUser) {
                return response()->json([
                    "message" => "you can not delete complaints other than yours !!!"
                ]);
            } else {
                $complaint->forceDelete();
            }


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
    public function getMyComplaints(Request $request)
    {

        try {



            $currentUser = auth()->user()->id;

            if (!is_numeric($currentUser)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid user ID format'
                ], 400);
            }


            $complaints = Complaint::where('user_id', $currentUser)->get();


            return response()->json([
                'status' => true,
                'message' => 'the complaints : ',
                'data' => $complaints
            ]);

            if ($complaints->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No complaints found for this user',
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
    public function getAllComplaints(Request $request)
    {


        try {

            $validator = Validator::make($request->all(), [
                "withTrash" => "required|string|in:yes,no"
            ]);

            // withTrashed() and onlyTrashed()

            if ($request->withTrash == 'yes') {

                $complaints = Complaint::onlyTrashed()->with(['User'])

                    //->orderBy('created_at', 'desc')
                    ->get()
                    ->groupBy('priority')
                    ->map(function ($group) {
                        return $group->map(function ($complaint) {
                            return [
                                'complaint_id' => $complaint->id,
                                'complaint' => $complaint->complaint,
                                'category' => $complaint->category ?? null,
                                'status' => $complaint->status,
                                'priority' => $complaint->priority,
                                'notes' => $complaint->notes ?? null,
                                'created_at' => $complaint->created_at,
                                'updated_at' => $complaint->updated_at,
                                'full_name' => trim($complaint->user->name . ' ' . $complaint->user->middleName . ' ' . $complaint->user->lastName),
                                'email' => $complaint->user->email
                            ];
                        });
                    });
            } else {

                $complaints = Complaint::with(['User'])

                    //->orderBy('created_at', 'desc')
                    ->get()
                    ->groupBy('priority')
                    ->map(function ($group) {
                        return $group->map(function ($complaint) {
                            return [
                                'complaint_id' => $complaint->id,
                                'complaint' => $complaint->complaint,
                                'category' => $complaint->category ?? null,
                                'status' => $complaint->status,
                                'priority' => $complaint->priority,
                                'notes' => $complaint->notes ?? null,
                                'created_at' => $complaint->created_at,
                                'updated_at' => $complaint->updated_at,
                                'full_name' => trim($complaint->user->name . ' ' . $complaint->user->middleName . ' ' . $complaint->user->lastName),
                                'email' => $complaint->user->email
                            ];
                        });
                    });
            }


            return response()->json([
                'status' => true,
                'message' => 'the complaints : ',
                'data' => $complaints
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
    public function modifyComplaint(Request $request)
    {


        try {


            $validator = Validator::make($request->all(), [
                'complaint_id' => 'required|integer|exists:complaints,id',
                'status' => 'required|string|in:processing,resolved,rejected',
                'priority' => 'nullable|string|in:low,high,medium',
                'notes' => 'nullable|string'
            ], [
                'status.in' => 'the status must be processing, resolved or rejected',
                'priority.in' => 'the priority must be low, high or medium',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $complaint = Complaint::findOrFail($request->complaint_id);

            $updateData = $request->only(['status', 'priority', 'notes']);
            $complaint->update($updateData);

            return response()->json([
                'status' => true,
                'message' => 'the complaint modified successfully !!',
                'data' => $complaint
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
