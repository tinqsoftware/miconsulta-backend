<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ipress;

class IpressController extends Controller
{
    public function activas()
    {
        $ipress = Ipress::query()
            ->where('esta_activa', true)
            ->orderBy('nombre')
            ->get()
            ->map(static function (Ipress $item): array {
                return [
                    'id' => $item->id,
                    'codigo_renipress' => $item->codigo_renipress,
                    'nombre' => $item->nombre,
                    'latitud' => $item->latitud,
                    'longitud' => $item->longitud,
                    'direccion' => $item->direccion,
                    'telefono' => $item->telefono,
                    'horario' => $item->horario_atencion,
                    'nivel' => $item->nivel,
                    'tiene_emergencia' => (bool) $item->tiene_emergencia,
                    'servicios' => $item->servicios ?? [],
                ];
            });

        return response()->json(['data' => $ipress]);
    }
}
