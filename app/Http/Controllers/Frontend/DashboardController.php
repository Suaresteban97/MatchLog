<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\RenderController;

class DashboardController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('Frontend/Dashboard', [
            'module' => 0 // Social module
        ]);
    }
}
