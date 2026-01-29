<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\PcComponent;

class CatalogController extends Controller
{
    /**
     * Get all catalog data required for forms
     */
    public function index()
    {
        $devices = Device::all();
        $components = PcComponent::all()->groupBy('type');

        return response()->json([
            'devices' => $devices,
            'components' => $components
        ]);
    }
}
