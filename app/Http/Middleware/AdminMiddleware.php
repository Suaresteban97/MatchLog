<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header("Admin_Authorization");

        if (!$token) {
            return response()->json([
                "code" => 403,
                "message" => "You should send Authorization Header"
            ], 403);
        }

        if ($token == env("ADMIN_TOKEN")) {
            return $next($request);
        } else {
            return response()->json([
                "code" => 403,
                "message" => "Wrong Admin Authorization"
            ], 403);
        }
    }
}
