<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Accepts comma-separated roles as parameter. Checks the authenticated
     * user's role against the allowed roles list.
     *
     * Usage: middleware('role:cto,ceo')
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $allowedRoles = array_map('trim', explode(',', $roles));
        $userRole = $user->role->value ?? $user->role;

        if (! in_array($userRole, $allowedRoles, true)) {
            return response()->json([
                'message' => 'Forbidden. You do not have the required role to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
