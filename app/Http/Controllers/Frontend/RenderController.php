<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;

class RenderController extends Controller
{
    /**
     * Render Header
     */
    public static function header ($variables = []) {

        $variables["usuario"] = session("usuario");

        if (!isset($variables["module"]) || $variables["module"] == "") {
            $variables["module"] = 0;
        }

        echo view("templates.header", $variables);
    }

    /**
     * Render Footer
     */
    public static function footer ($variables = []) {
        echo view("templates.footer", $variables);
    }
}
