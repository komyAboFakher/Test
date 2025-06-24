<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class deanMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
            if(Auth::check() && Auth::user()->role === 'dean'){
                return $next($request);
            }
    // Return a JSON response if the user is not an admin 
    
            return response()->json([ 
                'status'=>false,
                'message' => 'Not authorized',
             ], 401);    }
}
