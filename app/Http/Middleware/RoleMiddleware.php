<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Ensure the authenticated user has one of the required roles.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $userRole = $request->user()->role ?? null;

        if ($userRole && in_array($userRole, $roles, true)) {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}

