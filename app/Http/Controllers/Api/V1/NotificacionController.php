<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notificacion;

class NotificacionController extends Controller
{
    public function misNotificaciones(Request $request)
    {
        $paciente = $request->user()->paciente;
        if (!$paciente) return response()->json(['data' => []]);

        $notificaciones = Notificacion::where('id_paciente', $paciente->id)
            ->orderBy('fecha_envio', 'desc')
            ->get();

        return response()->json(['data' => $notificaciones]);
    }

    public function marcarLeida(Request $request, $id)
    {
        $paciente = $request->user()->paciente;
        $notificacion = Notificacion::where('id', $id)
            ->where('id_paciente', $paciente->id)
            ->firstOrFail();

        $notificacion->update(['fue_leida' => true]);

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    public function noLeidasCount(Request $request)
    {
        $paciente = $request->user()->paciente;
        if (!$paciente) return response()->json(['count' => 0]);

        $count = Notificacion::where('id_paciente', $paciente->id)
            ->where('fue_leida', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
