<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * Vista de Login
     */
    public function index(Request $request){

        RenderController::header([
            "titulo" => "Login",
            "styles" => [

            ]
        ]);

        echo view ("components.login", [

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
