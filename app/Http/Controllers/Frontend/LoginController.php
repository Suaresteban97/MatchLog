<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function index(Request $request)
    {
        if (session('usuario')) {
            return redirect('/dashboard');
        }

        return \Inertia\Inertia::render('Frontend/Login');
    }
}
