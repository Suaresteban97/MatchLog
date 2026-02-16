<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class WebAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the plain token directly from the cookie (excluded from encryption)
        $token = $request->cookie('auth_token');

        if (!$token) {
            return redirect()->route('loginView');
        }

        // Token is stored as SHA256 hash in the database
        $user = User::where("api_token", hash('sha256', $token))
            ->where("token_expires_at", ">", now())
            ->first();

        if (!$user) {
            return redirect()->route('loginView');
        }

        Auth::login($user);

        return $next($request);
    }
}
