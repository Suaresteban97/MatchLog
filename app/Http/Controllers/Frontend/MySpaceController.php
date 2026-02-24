<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\RenderController;

class MySpaceController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('Frontend/MySpace', [
            'module' => 1 // Dashboard module
        ]);
    }
}
