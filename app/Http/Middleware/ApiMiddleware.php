<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

//models
use App\Models\User;

class ApiMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header("Authorization");
        
        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        if (!$token) {
            return response()->json(["code" => 403, "message" => "You should send Authorization Header"], 403);
        }

        $user = User::where("api_token", hash('sha256', $token))
            ->where("token_expires_at", ">", now()) // Verifica que no haya expirado
            ->first();

        if (!$user) {
            return response()->json(["code" => 403, "message" => "Invalid or expired token"], 403);
        }

        // Cerrar sesión si hay una activa
        if (Auth::check()) {
            Auth::logout();
        }

        $request->setUserResolver(fn() => $user);

        return $next($request);
    }

}
