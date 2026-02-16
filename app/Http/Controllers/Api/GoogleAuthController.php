<?php

namespace App\Http\Controllers\Api;

use App\Services\GoogleAuthService;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\GeneralController;

class GoogleAuthController extends Controller
{
    protected $googleService;

    public function __construct(GoogleAuthService $googleService)
    {
        $this->googleService = $googleService;
    }

    public function storeGoogleToken(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return GeneralController::defaultResponse("Código de autorización faltante.", 400);
        }

        try {
            $result = $this->googleService->handleGoogleCallback($code);

            return response()->json([
                'user' => $result['user']->singleTransformer(),
                'token' => $result['plainToken'],
                'token_type' => 'Bearer',
                'expires_at' => $result['user']->token_expires_at
            ], $result['status']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en autenticación con Google',
                'message' => $e->getMessage(),
                'details' => 'No se pudo procesar la autenticación. Verifica tus credenciales de Google.'
            ], 500);
        }
    }

    public function checkAccessToken($profile)
    {
        $googleProfile = $this->googleService->checkAccessToken($profile);
        return $googleProfile ? $googleProfile : [];
    }

    public function refreshAccessToken($profile)
    {
        try {
            $result = $this->googleService->refreshAccessToken($profile);
            if (!$result) {
                return null;
            }
            return GeneralController::defaultResponse("Token actualizado", 200); // Or strict return based on usage

        } catch (\Exception $e) {
            GeneralController::defaultLog(
                "GoogleAuthController",
                $e->getLine(),
                $e->getMessage(),
                $e->getCode()
            );
            return GeneralController::defaultResponse($e->getMessage(), 200); // Maintain original behavior
        }
    }

    public function revokeAccess(Request $request)
    {
        try {
            $this->googleService->revokeAccess($request->user());
            return GeneralController::defaultResponse("Perfil desvinculado de Google Correctamente", 200);
        } catch (\Exception $e) {
            GeneralController::defaultLog(
                "GoogleAuthController",
                $e->getLine(),
                $e->getMessage(),
                $e->getCode(),
                $request->user()->id ?? null
            );

            // Even if error, service deletes profile, so we return success message as per original logic logic
            return GeneralController::defaultResponse($e->getMessage(), 400);
        }
    }
}
