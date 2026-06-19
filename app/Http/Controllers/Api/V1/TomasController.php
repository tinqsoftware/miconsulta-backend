<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistroTomas;

class TomasController extends Controller
{
    public function misTomas(Request $request)
    {
        $paciente = $request->user()->paciente;
        if (!$paciente) return response()->json(['data' => []]);

        // Filtrar tomas del día actual o fecha enviada
        $fecha = $request->query('fecha', now()->toDateString());

        $tomas = RegistroTomas::with('medicamento')
            ->where('id_paciente', $paciente->id)
            ->whereDate('fecha_programada', $fecha)
            ->orderBy('hora_programada')
            ->get();

        // Agrupar por periodo del día para el MVP
        $agrupado = [
            'manana' => $tomas->where('periodo_dia', 'manana')->values(),
            'tarde' => $tomas->where('periodo_dia', 'tarde')->values(),
            'noche' => $tomas->where('periodo_dia', 'noche')->values(),
        ];

        return response()->json(['data' => $agrupado]);
    }

    public function marcarToma(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:tomada,pospuesta,omitida',
            'minutos_pospuestos' => 'nullable|integer'
        ]);

        $paciente = $request->user()->paciente;
        $toma = RegistroTomas::where('id', $id)
            ->where('id_paciente', $paciente->id)
            ->firstOrFail();

        $toma->update([
            'estado' => $request->estado,
            'fecha_real' => now()->toDateString(),
            'hora_real' => now()->toTimeString(),
            'minutos_pospuestos' => $request->minutos_pospuestos
        ]);

        return response()->json(['message' => 'Toma actualizada correctamente', 'data' => $toma]);
    }

    public function getAdherencia(Request $request)
    {
        $paciente = $request->user()->paciente;
        if (!$paciente) return response()->json(['adherencia_porcentaje' => 0]);

        $total = RegistroTomas::where('id_paciente', $paciente->id)
            ->whereDate('fecha_programada', '<=', now()->toDateString())
            ->count();

        if ($total === 0) return response()->json(['adherencia_porcentaje' => 0]);

        $tomadas = RegistroTomas::where('id_paciente', $paciente->id)
            ->whereDate('fecha_programada', '<=', now()->toDateString())
            ->where('estado', 'tomada')
            ->count();

        $porcentaje = round(($tomadas / $total) * 100);

        // Para el MVP y según los mockups, si el usuario tiene datos simulados,
        // esto devolverá aprox 86% basado en los seeders.
        return response()->json(['adherencia_porcentaje' => $porcentaje]);
    }
}
