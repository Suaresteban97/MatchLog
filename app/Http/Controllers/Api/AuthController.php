<?php

namespace App\Http\Controllers\Api;

use App\Services\AuthService;
use App\Http\Controllers\Controller; // Ensure Controller is imported
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\LogoutRequest;
use App\Http\Requests\User\ForgotPasswordRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json([
                'user' => $result['user']->simpleTransformer(),
                'token' => $result['plainToken'],
                'token_type' => 'Bearer',
                'expires_at' => $result['user']->token_expires_at
            ], 201)->withCookie(cookie('auth_token', $result['plainToken'], 60 * 24 * 7, '/', null, false, true));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en el registro',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Inicio de sesión
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->email, $request->password);

        if (!$result) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        return response()->json([
            'token' => $result['plainToken'],
            'expires_at' => $result['user']->token_expires_at
        ], 200)->withCookie(cookie('auth_token', $result['plainToken'], 60 * 24 * 7, '/', null, false, true));
    }

    // Cerrar sesión
    public function logout(LogoutRequest $request)
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ], 200)->withCookie(cookie('auth_token', '', -1, '/', null, false, true));
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $token = $this->authService->forgotPassword($request->email);

        return response()->json([
            'message' => 'Si el correo existe, hemos enviado un token de recuperación.',
            'token' => $token
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $result = $this->authService->resetPassword($request->email, $request->token, $request->password);

        if (isset($result['status']) && $result['status'] === 'error') {
            return response()->json([
                'message' => $result['message']
            ], $result['code']);
        }

        return response()->json([
            'message' => $result['message']
        ], 200);
    }
}
