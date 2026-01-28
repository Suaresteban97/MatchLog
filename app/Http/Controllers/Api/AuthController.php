<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

//Models
use App\Models\User;
use App\Models\InfoUser;
use App\Models\UsersStatus;

//Request
use App\Http\Requests\User\RegisterRequest;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\LogoutRequest;
use App\Http\Requests\User\ForgotPasswordRequest;
use App\Http\Requests\User\ResetPasswordRequest;
use Illuminate\Http\Request;

class AuthController extends Controller
{    
    public function register(RegisterRequest $request) {

        DB::beginTransaction();
    
        try {
            $user = new User();
            $user->name = $request->name . " " . $request->last_name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
    
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
            $userInfo->first_name = $request->name ?? "";
            $userInfo->last_name = $request->last_name ?? "";
            $userInfo->nickname = $request->nickname ?? "";
            $userInfo->user_id = $user->id;
    
            if ($request->age) {
                $userInfo->age = $request->age;
            }
            if ($request->genre) {
                $userInfo->genre = $request->genre;
            }
    
            $userInfo->save();
    
            DB::commit();
    
            return response()->json([
                'user' => $user->simpleTransformer(),
                'token' => $plainToken,
                'token_type' => 'Bearer',
                'expires_at' => $user->token_expires_at
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'error' => 'Error en el registro',
                'message' => $e->getMessage(),
            ], 500);
        }
    }        

    // Inicio de sesión
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        $plainToken = Str::random(80);
        $hashedToken = hash_hmac('sha256', $plainToken, env('APP_KEY'));
        
        // Generar un nuevo token
        $plainToken = Str::random(80);
        $user->api_token = hash('sha256', $plainToken);
        $user->token_expires_at = Carbon::now()->addDays(7);
        $user->save();

        return response()->json([
            'token' => $plainToken,
            'expires_at' => $user->token_expires_at
        ], 200);
    }

    // Cerrar sesión
    public function logout(LogoutRequest $request)
    {
        $user = $request->user();
        
        // Invalidar completamente el token
        $user->api_token = null;
        $user->token_expires_at = null;
        $user->save();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ], 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request) {

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'Si el correo existe, hemos enviado un token de recuperación.'
            ], 200);
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        // Enviar correo con el token
        // TODO: Descomentar cuando se implemente el sistema de correos
        // ForgotPasswordJob::dispatch($token, $request->email)->onQueue('high');

        return response()->json([
            'message' => 'Si el correo existe, hemos enviado un token de recuperación.',
            // TODO: Eliminar esta línea en producción (solo para desarrollo)
            'token' => $token
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request) {
        // Buscar el token en la base de datos
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset || $request->token !== $passwordReset->token) {
            return response()->json([
                'message' => 'El token es inválido o ha expirado'
            ], 400);
        }

        $tokenAge = Carbon::parse($passwordReset->created_at)->diffInMinutes(Carbon::now());
        if ($tokenAge > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            
            return response()->json([
                'message' => 'El token ha expirado. Solicita uno nuevo.'
            ], 400);
        }

        // Buscar el usuario
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->password = Hash::make($request->password);
        
        $user->api_token = null;
        $user->token_expires_at = null;
        
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente. Por favor, inicia sesión nuevamente.'
        ], 200);
    }

}

