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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        // Check if user is authenticated, has a role, and the role matches
        if (!$user || !$user->role || $user->role->slug !== $role) {
            // For API endpoints
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action.'
                ], 403);
            }
            
            // For Inertia/Web navigation fallback
            return redirect()->route('dashboardView')->withErrors('No tienes permisos de administrador.');
        }

        return $next($request);
    }
}
