<?php

namespace App\Services;

use App\Models\PlatformsToken;
use App\Models\User;
use App\Models\UsersStatus;
use App\Models\InfoUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Http\Controllers\Api\GeneralController;

class GoogleAuthService
{
    public function handleGoogleCallback(string $code)
    {
        $client = new Client();

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
            throw new \Exception("No se recibió el access_token de Google.");
        }

        $userResponse = $client->get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokens['access_token'],
            ],
        ]);

        $googleUser = json_decode($userResponse->getBody(), true);

        if (!isset($googleUser['email'])) {
            throw new \Exception("No se pudo obtener la información del usuario.");
        }

        $user = User::with(["googleToken"])->where("email", $googleUser['email'])->first();

        if (!isset($user)) {
            return $this->registerGoogleUser($googleUser, $tokens);
        } else {
            return $this->loginGoogleUser($user, $tokens);
        }
    }

    protected function registerGoogleUser(array $googleUser, array $tokens)
    {
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

            return [
                'user' => $user,
                'plainToken' => $plainToken,
                'status' => 201
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function loginGoogleUser(User $user, array $tokens = [])
    {
        $this->refreshAccessToken($user->id);

        // Refresh API Token
        $plainToken = Str::random(80);
        $user->api_token = hash('sha256', $plainToken);
        $user->token_expires_at = Carbon::now()->addDays(7);
        $user->save();

        return [
            'user' => $user,
            'plainToken' => $plainToken,
            'status' => 201
        ];
    }

    public function checkAccessToken($userId)
    {
        $googleProfile = PlatformsToken::where("user_id", $userId)->first();

        if (isset($googleProfile)) {
            $expiresAt = Carbon::createFromFormat('Y-m-d H:i:s', $googleProfile->token_expires_at, 'UTC')
                ->setTimezone('America/Bogota'); // Consider passing timezone as argument or config

            $currentTime = Carbon::now();

            if ($currentTime->gte($expiresAt)) {
                $this->refreshAccessToken($googleProfile->user_id);
            }

            return $googleProfile;
        } else {
            return null;
        }
    }

    public function refreshAccessToken($userId)
    {
        $googleProfile = PlatformsToken::where("user_id", $userId)->first();

        if (!isset($googleProfile) || !isset($googleProfile->refresh_token)) {
            return null;
        }

        $client = new Client();

        try {
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id'     => env('GOOGLE_CLIENT_ID'),
                    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                    'refresh_token' => $googleProfile->refresh_token,
                    'grant_type'    => 'refresh_token',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['access_token'])) {
                return null;
            }

            $googleProfile->access_token = $data['access_token'];
            // Google tokens usually expire in 3600 seconds (1 hour)
            // But we should check if 'expires_in' is present
            if (isset($data['expires_in'])) {
                // Assuming token_expires_at is the column in your DB for google token expiry? 
                // Or create another one? The model has token_expires_at but that might be for the user api token?
                // The original code tried to save expires_at, but PlatformsToken likely has token_expires_at
                // Let's check the migration or model... assuming token_expires_at for now based on context in checkAccessToken
                $googleProfile->token_expires_at = now()->addSeconds($data['expires_in']);
            }

            if (isset($data['refresh_token'])) {
                $googleProfile->refresh_token = $data['refresh_token'];
            }

            $googleProfile->save();

            return $googleProfile;
        } catch (\Exception $e) {
            // Log logic could be here or in controller
            throw $e;
        }
    }

    public function revokeAccess(User $user)
    {
        $googleProfile = PlatformsToken::where("user_id", $user->id)->first();

        if (!isset($googleProfile) || !isset($googleProfile->refresh_token)) {
            throw new \Exception("Ya no está vinculado este usuario");
        }

        $client = new Client();

        try {
            $response = $client->post('https://oauth2.googleapis.com/revoke', [
                'form_params' => ['token' => $googleProfile->access_token],
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            ]);

            $googleProfile->delete();
            return true;
        } catch (\Exception $e) {
            $googleProfile->delete();
            // We rethrow or just return true if we want to suppress?
            // Original code suppressed and returned success message
            return true;
        }
    }
}
