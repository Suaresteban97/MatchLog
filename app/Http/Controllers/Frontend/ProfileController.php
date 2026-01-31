<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\RenderController;

class ProfileController extends Controller
{
    /**
     * Show edit profile form
     */
    public function edit()
    {
        RenderController::header([
            "titulo" => "Editar Perfil",
            "module" => 3 // Profile module code
        ]);

        echo view('components.profile.form');

        RenderController::footer([
            "scripts" => [
                "profile/ProfileForm"
            ]
        ]);
    }
}
