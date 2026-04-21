<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModerationViewController extends Controller
{
    public function index()
    {
        return Inertia::render('Frontend/Admin/ModerationPanel');
    }
}
