<?php

namespace App\Http\Controllers;

//Models
use App\Models\Log;

use Illuminate\Http\Request;

class GeneralController extends Controller
{
    public static function defaultLog($source, $line, $message, $status_code, $user_id = null){
        $newLog = new Log();

        $newLog->source = $source;
        $newLog->line = $line;
        $newLog->message = $message;
        $newLog->status_code = $status_code;
        $newLog->user_id = $user_id;

        $newLog->save();
    }

    public static function defaultResponse($message, $code){
        return response()->json([
            "code" => $code,
            "message" => $message
        ], $code);
    }
}
