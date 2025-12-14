<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApprovedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! auth()->user()->is_approved) {
            return response()->json(['message' => 'Account not approved'], 403);
        }

        return $next($request);
    }
}
