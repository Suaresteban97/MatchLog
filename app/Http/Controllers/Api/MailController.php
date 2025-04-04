<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class MailController extends Controller
{
    public function sendResetPasswordEmail($token, $email){
        Mail::send('emails.password_reset', ['token' => $token], function ($message) use ($email) {
            $message->to($email);
            $message->subject('Recuperación de contraseña');
        });
    }
}
