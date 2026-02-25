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
        return \Inertia\Inertia::render('Frontend/ProfileForm', [
            'module' => 3 // Profile module code
        ]);
    }
}
