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

            $permissionId = Permission::where('permission', $request->permission)->firstOrFail();
            $user = User::findOrFail($request->user_id);

            $alreadyHasPermission = UserPermission::where('user_id', $request->user_id)
                ->where('permission_id', $permissionId->id)
                ->exists();


            if ($alreadyHasPermission) {
                return response()->json([
                    'status' => false,
                    'message' => trim($user->name . ' ' . $user->middleName . ' ' . $user->lastName) . ' already has this permission !!'
                ]);
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
    public function unassignPermission(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_permission_id' => 'required|integer|exists:user_permissions,id',
                'user_id' => 'required|integer|exists:users,id',
                'permission' => ['required', 'string', new PermissionRule]
            ]);


            $userPermission = UserPermission::findOrFail($request->user_permission_id);
            $permissionId = Permission::where('permission', $request->permission)->firstOrFail();
            $user = User::findOrFail($request->user_id);

            $alreadyHasPermission = UserPermission::where('user_id', $request->user_id)
                ->where('permission_id', $permissionId->id)
                ->exists();

            if ($alreadyHasPermission) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mr' . trim($user->name . ' ' . $user->middleName . ' ' . $user->lastName) . 'is assigned already to this permission '
                ]);
            }


            $userPermission->fill($request->only([
                'user_permission_id',
                'user_id',
                'permission',
            ]))->update();

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
}
