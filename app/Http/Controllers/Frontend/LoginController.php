<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    /**
     * Vista de Login
     */
    public function index(Request $request){
        
        if (session('usuario')) {
            return redirect('/dashboard');
        }

        RenderController::header([
            "titulo" => "Login",
            "styles" => [

            ]
        ]);

        echo view ("components.login.login", [

        ]);

        RenderController::footer([
            "scripts" => [
                "login/Login"
            ],
            "external_scripts" => [

            ]
        ]);
    }
}
