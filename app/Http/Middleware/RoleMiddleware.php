<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Ensure the authenticated user has one of the required roles,
     * admins can access everything.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = $request->user();

        //ADMIN BYPASS
        if ($user->role === 'admin') {
            return $next($request);
        }

        // Check allowed roles
        if (in_array($user->role, $roles, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}
