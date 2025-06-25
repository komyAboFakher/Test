<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class gaith
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {if (auth()->user()->role === 'supervisor' || auth()->user()->role === 'dean' || auth()->user()->role === 'teacher') {
            return $next($request);
        }

        abort(403, 'Unauthorized');
    }
}
