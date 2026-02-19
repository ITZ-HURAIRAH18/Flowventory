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
     * Usage in routes:
     *   ->middleware('role:super_admin')              — only super admin
     *   ->middleware('role:super_admin,branch_manager') — admin OR manager
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Load the role relationship if not already loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Check if the user's role matches any of the allowed roles
        if (!$user->role || !in_array($user->role->name, $roles)) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to perform this action.'
            ], 403);
        }

        return $next($request);
    }
}
