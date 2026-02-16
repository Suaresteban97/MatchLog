<?php

namespace App\Services;

use App\Models\User;
use App\Models\InfoUser;
use App\Models\UsersStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuthService
{
    public function register(array $data)
    {
        DB::beginTransaction();

        try {
            $user = new User();
            $user->name = $data['name'] . " " . $data['last_name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);

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
            $userInfo->first_name = $data['name'] ?? "";
            $userInfo->last_name = $data['last_name'] ?? "";
            $userInfo->nickname = $data['nickname'] ?? "";
            $userInfo->user_id = $user->id;

            if (isset($data['age'])) {
                $userInfo->age = $data['age'];
            }
            if (isset($data['genre'])) {
                $userInfo->genre = $data['genre'];
            }

            $userInfo->save();

            DB::commit();

            return [
                'user' => $user,
                'plainToken' => $plainToken
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login(string $email, string $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        $plainToken = Str::random(80);
        $user->api_token = hash('sha256', $plainToken);
        $user->token_expires_at = Carbon::now()->addDays(7);
        $user->save();

        return [
            'user' => $user,
            'plainToken' => $plainToken
        ];
    }

    public function logout(?User $user)
    {
        if ($user) {
            $user->api_token = null;
            $user->token_expires_at = null;
            $user->save();
            Log::info('User logged out: ' . $user->email);
            return true;
        }

        Log::warning('Logout called with no authenticated user');
        return false;
    }

    public function forgotPassword(string $email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return null;
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        // ForgotPasswordJob::dispatch($token, $email)->onQueue('high');

        return $token;
    }

    public function resetPassword(string $email, string $token, string $password)
    {
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$passwordReset || $token !== $passwordReset->token) {
            return ['status' => 'error', 'message' => 'El token es inválido o ha expirado', 'code' => 400];
        }

        $tokenAge = Carbon::parse($passwordReset->created_at)->diffInMinutes(Carbon::now());
        if ($tokenAge > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return ['status' => 'error', 'message' => 'El token ha expirado. Solicita uno nuevo.', 'code' => 400];
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return ['status' => 'error', 'message' => 'Usuario no encontrado', 'code' => 404];
        }

        $user->password = Hash::make($password);
        $user->api_token = null;
        $user->token_expires_at = null;
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return ['status' => 'success', 'message' => 'Contraseña actualizada correctamente. Por favor, inicia sesión nuevamente.'];
    }
}
