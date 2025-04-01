<?php

namespace App\Http\Controllers;

//Models
use App\Models\GoogleToken;
use App\Models\User;

//Packages
use Illuminate\Http\Request;
use Carbon\Carbon;
use GuzzleHttp\Client;

class GoogleAuthController extends Controller
{
    public function storeGoogleToken(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return GeneralController::defaultResponse("Código de autorización faltante.", 400);
        }

        $client = new Client();

        try {
            // Obtener tokens desde Google
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'code'          => $code,
                    'client_id'     => env('GOOGLE_CLIENT_ID'),
                    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                    'redirect_uri'  => env('GOOGLE_REDIRECT_URI'),
                    'grant_type'    => 'authorization_code',
                ],
            ]);

            $tokens = json_decode($response->getBody(), true);

            if (!isset($tokens['access_token'])) {
                return GeneralController::defaultResponse("No se recibió el access_token de Google.", 400);
            }

            // Obtener datos del usuario desde Google
            $userResponse = $client->get('https://www.googleapis.com/oauth2/v3/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokens['access_token'],
                ],
            ]);

            $googleUser = json_decode($userResponse->getBody(), true);

            if (!isset($googleUser['email'])) {
                return GeneralController::defaultResponse("No se pudo obtener la información del usuario.", 400);
            }

            // Buscar usuario en la base de datos o crearlo
            $user = User::firstOrCreate(
                ['email' => $googleUser['email']],
                [
                    'name' => $googleUser['name'] ?? 'Usuario sin nombre',
                ]
            );

            // Si el usuario no tiene un api_token, generarlo
            if (!$user->api_token) {
                do {
                    $token = bin2hex(random_bytes(40));
                } while (User::where('api_token', $token)->exists());

                $user->api_token = $token;
                $user->save();
            }

            // Guardar o actualizar tokens de Google
            GoogleToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'access_token'  => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                    'expires_at'    => now()->addSeconds($tokens['expires_in']),
                ]
            );

            return GeneralController::defaultResponse("Usuario creado correctamente", 201);

        } catch (\Exception $e) {
            return GeneralController::defaultResponse("No se almacenó el Token de Google", 400);
        }
    }


    public function checkAccessToken($profile) {
        $googleProfile = GoogleToken::where("user_id", $profile)->first();
    
        if (isset($googleProfile)) {

            $expiresAt = Carbon::createFromFormat('Y-m-d H:i:s', $googleProfile->token_expires_at, 'UTC')  
            ->setTimezone('America/Bogota');

            $currentTime = Carbon::now();
        
            if ($currentTime->gte($expiresAt)) {
                $this->refreshAccessToken($googleProfile["user_id"]);
            } 
        
            return $googleProfile;
        } else {
            return [];
        }
    }

    public function refreshAccessToken($profile) {

        $googleProfile = GoogleToken::where("user_id", $profile)->first();

        if (!isset($googleProfile) && !isset($googleProfile["refresh_token"])) {
            return null;
        }
    
        $client = new Client();
    
        try {
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id'     => env('GOOGLE_CLIENT_ID'),
                    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                    'refresh_token' => $googleProfile["refresh_token"],
                    'grant_type'    => 'refresh_token',
                ],
            ]);
    
            $data = json_decode($response->getBody(), true);
    
            if (!isset($data['access_token'])) {
                return null;
            }
    
            $googleProfile->access_token = $data['access_token'];
            $googleProfile->expires_at = now()->addSeconds($data['expires_in']);
            
            if (isset($data['refresh_token'])) {
                $googleProfile->refresh_token = $data['refresh_token'];
            }
    
            $googleProfile->save();
    
        } catch (\Exception $e) {
            GeneralController::defaultLog(
                "GoogleAuthController", 
                $e->getLine(),
                $e->getMessage(),
                $e->getCode(),
                $profile ?? null
            );
        
            return GeneralController::defaultResponse($e->getMessage(), 200);
        }
    }

    public function revokeAccess(Request $request){

        $googleProfile = GoogleToken::where("user_id", $request->user()->id)->first();

        if (!isset($googleProfile) && !isset($googleProfile["google_refresh_token"])) {
            return GeneralController::defaultResponse("Ya no está vinculado este usuario", 400);
        }
    
        $client = new Client();
    
        try {
            $response = $client->post('https://oauth2.googleapis.com/revoke', [
                'form_params' => ['token' => $googleProfile->google_token],
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            ]);
            
            if ($response->getStatusCode() == 200) {
                $googleProfile->delete();

                return GeneralController::defaultResponse("Perfil desvinculado de Google Correctamente", 200);
            }
    
        } catch (\Exception $e) {

            $googleProfile->delete();

            GeneralController::defaultLog(
                "GoogleAuthController", 
                $e->getLine(),
                $e->getMessage(),
                $e->getCode(),
                $request->user()->id ?? null
            );

            return GeneralController::defaultResponse("Perfil desvinculado de Google Correctamente", 200);
        }
    }
}
