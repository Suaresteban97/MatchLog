<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\RenderController;

class DashboardController extends Controller
{
    public function index()
    {
        RenderController::header([
            "titulo" => "Social Dashboard",
            "module" => 0 // Social module
        ]);

        echo view('components.dashboard.social');

        RenderController::footer([
            "scripts" => [
                "Social/Social"
            ]
        ]);
    }
}
