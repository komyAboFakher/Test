<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // If the request expects a JSON response, return null to trigger a 401 error.
        // Otherwise, for web requests, redirect to the login route.
        return $request->expectsJson() ? null : route('login');
    }
}
