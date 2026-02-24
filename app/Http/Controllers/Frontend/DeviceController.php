<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\RenderController;

class DeviceController extends Controller
{
    /**
     * List all devices
     */
    public function index()
    {
        return \Inertia\Inertia::render('Frontend/DeviceList', [
            'module' => 2 // Devices module
        ]);
    }

    /**
     * Show create device form
     */
    public function create()
    {
        return \Inertia\Inertia::render('Frontend/DeviceForm', [
            'module' => 2
        ]);
    }

    /**
     * Show edit device form
     */
    public function edit($id)
    {
        return \Inertia\Inertia::render('Frontend/DeviceForm', [
            'module' => 2,
            'deviceId' => $id
        ]);
    }
}
