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
            $token = $request->cookie('auth_token');
        }

        if (!$token) {
            return response()->json([
                "code" => 403,
                "message" => "Usted debe enviar el Header de Autorización o iniciar sesión"
            ], 403);
        }

        $user = User::where("api_token", hash('sha256', $token))
            ->where("token_expires_at", ">", now())
            ->first();

        if (!$user) {
            return response()->json([
                "code" => 403,
                "message" => "Token inválido o expirado"
            ], 403);
        }

        Auth::login($user);
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
