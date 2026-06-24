<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;

class ConfiguracionApiController extends Controller
{
    public function index()
    {
        $configuraciones = Configuracion::all()->pluck('valor', 'clave');
        
        return response()->json([
            'status' => 'success',
            'data' => $configuraciones
        ]);
    }
}
