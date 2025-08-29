<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Rules\PermissionRule;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    public function assignPermission(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'permission' => ['required', 'string', new PermissionRule]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }



            $permissionId = Permission::where('permission', $request->permission)->firstOrFail();
            $user = User::findOrFail($request->user_id);

            $alreadyHasPermission = UserPermission::where('user_id', $request->user_id)
                ->where('permission_id', $permissionId->id)
                ->exists();


            if ($alreadyHasPermission) {
                return response()->json([
                    'status' => false,
                    'message' => trim($user->name . ' ' . $user->middleName . ' ' . $user->lastName) . ' already has this permission !!'
                ], 422);
            }


            $permission = UserPermission::create([
                'permission_id' => $permissionId->id,
                'user_id' => $request->user_id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'the permission assigned to ' .  trim($user->name . ' ' .
                    $user->middleName . ' ' .
                    $user->lastName),
                'permission' => $permissionId->permission,
                'description' => $permissionId->description
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //___________________________________________________________
    public function updateAssignPermission(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_permission_id' => 'required|integer|exists:user_permissions,id',
                'user_id' => 'required|integer|exists:users,id',
                'permission' => ['required', 'string', new PermissionRule]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }



            $userPermission = UserPermission::findOrFail($request->user_permission_id);

            $permissionId = Permission::where('permission', $request->permission)->firstOrFail();

            $user = User::findOrFail($request->user_id);


            $alreadyHasPermission = UserPermission::where('user_id', $request->user_id)
                ->where('permission_id', $permissionId->id)
                ->exists();

            if ($alreadyHasPermission) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mr ' . trim($user->name . ' ' . $user->middleName . ' ' . $user->lastName) . 'is assigned already to this permission '
                ], 422);
            }

            $userPermission->user_id = $request->user_id;
            $userPermission->permission_id = $permissionId->id;
            $userPermission->save();






            //$userPermission->fill([
            //    'user_id',
            //    'permission_id' => $permissionId->id,
            //])->save();

            return response()->json([
                'status' => true,
                'message' => 'the permission assigned to ' .  trim($user->name . ' ' .
                    $user->middleName . ' ' .
                    $user->lastName),
                'permission' => $permissionId->permission,
                'description' => $permissionId->description
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //_____________________________________________________________________________-

    public function deleteAssignPermission(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'user_id' => 'required|integer|exists:user_permissions',
                    'permission' => 'required|string|in:Library,Nurse,Oversee',
                ],
                [
                    'user_id' => 'not found',
                    'permission.in' => 'permission must be Library or Nurse or Oversee'
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $koko = Permission::where('permission', $request->permission)->get();

            $userPermission = UserPermission::where('user_id', $request->user_id)->where('permission_id', $koko->id)->first();
            $userPermission->delete();

            return response()->json([
                'status' => true,
                'message' => 'the user permission is deleted'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //__________________________________________________________________
    public function showPermissions()
    {
        try {

            $permissions = Permission::all();
            return response()->json([
                "status" => true,
                "permissions" => $permissions
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //___________________________________________________________________

    public function showAllUserPermissions()
    {
        try {

            //$userPermissions = UserPermission::with('User')
            //    ->with('permission')
            //    ->get()
            //    ->groupBy(function ($item) {
            //        return $item->permission->permission; // Just the name, like "Library"
            //    })
            //
            //    ->map(function ($group) {
            //        return $group->map(function ($userPermission) {
            //            return [
            //                'user_id' => $userPermission->user_id,
            //                'full_name' => trim($userPermission->user->name . ' ' . $userPermission->user->middleName . ' ' . $userPermission->user->lastName),
            //                'email' => $userPermission->user->email,
            //                'permission' => $userPermission->permission->permission,
            //                'permission_description' => $userPermission->permission->description,
            //
            //            ];
            //        });
            //    });

            $userPermissions = Permission::with('userPermission:id,user_id,permission_id')
                ->get()
                ->groupBy('permission')
                ->map(function ($group) {
                    return $group->map(function ($userPermission) {
                        return [
                            'permission_holders' => $userPermission->userPermission->map(function ($user) {

                                return [
                                    'user_id' => $user->user_id,
                                    'full_name' => trim($user->user->name . ' ' . $user->user->middleName . ' ' . $user->user->lastName),
                                    'email' => $user->user->email,
                                    'role' => $user->user->role,
                                ];
                            })


                        ];
                    });
                });

            return response()->json([
                'status' => true,
                'message' => $userPermissions
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //__________________________________________________________________________________________


    public function showUserPermissions(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $permission = UserPermission::where('user_id', $request->user_id)->with(['permission', 'user'])
                ->get()
                ->map(function ($per) {
                    return [
                        'user_id' => $per->user_id,
                        'full_name' => trim($per->user->name . ' ' . $per->user->middleName . ' ' . $per->user->lastName),
                        'role' => $per->user->role,
                        'permission' => $per->permission->permission,
                        'description' => $per->permission->description,

                    ];
                });
            if ($permission->isEmpty()) {
                return response()->json([
                    "status" => false,
                    "message" => "the specific user don't have a permission yet"
                ], 422);
            }

            return response()->json([
                "status" => true,
                "message" => $permission
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
