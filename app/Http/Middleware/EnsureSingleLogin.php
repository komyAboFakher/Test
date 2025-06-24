<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $deviceType = $request->input('deviceType'); // 'web' or 'mobile'

            if (!$deviceType || !in_array($deviceType, ['web', 'mobile'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Device type is required and must be web or mobile'
                ], 400);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($user->role !== 'teacher') {
                // For non-teachers, allow only one token
                PersonalAccessToken::where('tokenable_id', $user->id)->delete();
            } else {
                // For teachers: delete only the token of the same device type
                PersonalAccessToken::where('tokenable_id', $user->id)->where('name', $deviceType)->delete();
            }

            return $next($request);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
