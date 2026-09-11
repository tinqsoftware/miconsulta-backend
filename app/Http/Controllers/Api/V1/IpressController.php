<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ipress;

class IpressController extends Controller
{
    // Cobertura inicial del mapa: Lima Metropolitana. No sustituye una
    // clasificación territorial; mientras no exista esa columna usamos las
    // coordenadas geográficas disponibles.
    private const LIMA_LATITUD_MIN = -12.55;
    private const LIMA_LATITUD_MAX = -11.45;
    private const LIMA_LONGITUD_MIN = -77.60;
    private const LIMA_LONGITUD_MAX = -76.65;

    public function activas()
    {
        $ipress = Ipress::query()
            ->where('esta_activa', true)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->whereBetween('latitud', [self::LIMA_LATITUD_MIN, self::LIMA_LATITUD_MAX])
            ->whereBetween('longitud', [self::LIMA_LONGITUD_MIN, self::LIMA_LONGITUD_MAX])
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
