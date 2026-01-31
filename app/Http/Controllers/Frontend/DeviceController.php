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
        RenderController::header([
            "titulo" => "Mis Dispositivos",
            "module" => 2 // Devices module
        ]);

        echo view('components.devices.index');

        RenderController::footer([
            "scripts" => [
                "devices/DeviceList"
            ]
        ]);
    }

    /**
     * Show create device form
     */
    public function create()
    {
        RenderController::header([
            "titulo" => "Agregar Dispositivo",
            "module" => 2
        ]);

        echo view('components.devices.form');

        RenderController::footer([
            "scripts" => [
                "devices/DeviceForm"
            ]
        ]);
    }

    /**
     * Show edit device form
     */
    public function edit($id)
    {
        RenderController::header([
            "titulo" => "Editar Dispositivo",
            "module" => 2
        ]);

        // Inject ID to view for JS to use
        echo "<script>window.DEVICE_ID = {$id};</script>";
        echo view('components.devices.form');

        RenderController::footer([
            "scripts" => [
                "devices/DeviceForm"
            ]
        ]);
    }
}
