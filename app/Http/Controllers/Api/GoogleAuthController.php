<?php

namespace App\Http\Controllers\Api;

//Models
use App\Models\PlatformsToken;
use App\Models\User;
use App\Models\UsersStatus;
use App\Models\InfoUser;

//Packages
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use GuzzleHttp\Client;

class GoogleAuthController extends Controller
{
    public function storeGoogleToken(Request $request) {
        $code = $request->input('code');

        if (!$code) {
            return GeneralController::defaultResponse("Código de autorización faltante.", 400);
        }

        $client = new Client();

        try {

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

            $userResponse = $client->get('https://www.googleapis.com/oauth2/v3/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $tokens['access_token'],
                ],
            ]);

            $googleUser = json_decode($userResponse->getBody(), true);

            if (!isset($googleUser['email'])) {
                return GeneralController::defaultResponse("No se pudo obtener la información del usuario.", 400);
            }

            $user = User::with(["googleToken"])->where("email", $googleUser['email'])->first();

            if (!isset($user)) {
                DB::beginTransaction();
                
                try {
                    $user = new User();
                    $user->name = $googleUser['name'];
                    $user->email = $googleUser['email'];
                    $user->password = Hash::make(Str::random(16));

                    $plainToken = Str::random(80);
                    $user->api_token = hash('sha256', $plainToken);
                    $user->token_expires_at = Carbon::now()->addDays(7);
                    $user->save();

                    if (!$user->id) {
                        throw new \Exception("No se pudo crear el usuario");
                    }

                    $userStatus = new UsersStatus(); 
                    $userStatus->user_id = $user->id;
                    $userStatus->status_id = 1;
                    $userStatus->save();

                    $userInfo = new InfoUser();
                    $userInfo->first_name = $googleUser['given_name'] ?? ''; 
                    $userInfo->last_name = $googleUser['family_name'] ?? '';
                    $userInfo->user_id = $user->id;
                    $userInfo->photo = $googleUser['picture'] ?? ""; 
                    $userInfo->save();
                    
                    $user->email_verified_at = Carbon::now();
                    $user->save();

                    $newPlatformToken = new PlatformsToken();
                    $newPlatformToken->user_id = $user->id;
                    $newPlatformToken->provider = "google";
                    $newPlatformToken->access_token = $tokens['access_token'];
                    $newPlatformToken->refresh_token = $tokens['refresh_token'] ?? null;
                    $newPlatformToken->save();
                    
                    DB::commit();

                    return response()->json([
                        'user' => $user->singleTransformer(),
                        'token' => $plainToken,
                        'token_type' => 'Bearer',
                        'expires_at' => $user->token_expires_at
                    ], 201);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    return response()->json([
                        'error' => 'Error al crear usuario con Google',
                        'message' => $e->getMessage()
                    ], 500);
                }

            } else {
                
                $this->refreshAccessToken($user["id"]);
                
                // Refresh API Token
                $plainToken = Str::random(80);
                $user->api_token = hash('sha256', $plainToken);
                $user->token_expires_at = Carbon::now()->addDays(7);
                $user->save();

                return response()->json([
                    'user' => $user->singleTransformer(),
                    'token' => $plainToken,
                    'token_type' => 'Bearer',
                    'expires_at' => $user->token_expires_at
                ], 201);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en autenticación con Google',
                'message' => $e->getMessage(),
                'details' => 'No se pudo procesar la autenticación. Verifica tus credenciales de Google.'
            ], 500);
        }
    }


    public function checkAccessToken($profile) {
        $googleProfile = PlatformsToken::where("user_id", $profile)->first();
    
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

        $googleProfile = PlatformsToken::where("user_id", $profile)->first();

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

        $googleProfile = PlatformsToken::where("user_id", $request->user()->id)->first();

        if (!isset($googleProfile) || !isset($googleProfile->refresh_token)) {
            return GeneralController::defaultResponse("Ya no está vinculado este usuario", 400);
        }
    
        $client = new Client();
    
        try {
            $response = $client->post('https://oauth2.googleapis.com/revoke', [
                'form_params' => ['token' => $googleProfile->access_token],
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
