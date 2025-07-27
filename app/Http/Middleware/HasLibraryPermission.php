<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasLibraryPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        $hasPermission = $user->permissions()
            ->where('permission', 'Library')
            ->exists();

        if (!$hasPermission) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient permission to access this resource.'
            ], 403);
        }


        return $next($request);
    }
}
