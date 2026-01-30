<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\RenderController;

class MySpaceController extends Controller
{
    public function index()
    {
        RenderController::header([
            "titulo" => "Mi Espacio",
            "module" => 1 // Dashboard module (maybe keep as 1 or change?)
        ]);

        echo view('components.myspace.myspace');

        RenderController::footer([
            "scripts" => [
                "myspace/MySpace"
            ]
        ]);
    }
}
