<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Receta;
use App\Models\MedicamentoReceta;
use App\Models\SeguimientoToma;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecordatorioController extends Controller
{
    /**
     * Activa o desactiva los recordatorios para todos los medicamentos de una receta.
     */
    public function toggleRecordatorios(Request $request, $recetaId)
    {
        $request->validate([
            'activados' => 'required|boolean'
        ]);

        MedicamentoReceta::where('id_receta', $recetaId)
            ->update(['recordatorios_activados' => $request->activados]);

        return response()->json(['message' => 'Recordatorios actualizados']);
    }

    /**
     * Programa las tomas en la BD basándose en los horarios seleccionados.
     */
    public function programarTomas(Request $request, $recetaId)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'medicamentos' => 'required|array',
            'medicamentos.*.id' => 'required|exists:medicamentos_receta,id',
            'medicamentos.*.horarios' => 'required|array',
            'medicamentos.*.horarios.*' => 'required|date_format:H:i'
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();

        DB::beginTransaction();

        try {
            foreach ($request->medicamentos as $medData) {
                $medicamento = MedicamentoReceta::where('id_receta', $recetaId)
                                                ->where('id', $medData['id'])
                                                ->firstOrFail();

                // Borramos programaciones anteriores si existen
                SeguimientoToma::where('id_medicamentos_receta', $medicamento->id)->delete();

                $dias = $medicamento->dias ?? 1;
                $tomasRestantes = $medicamento->total_tomas ?? 1;

                $horarios = $medData['horarios'];
                $tomasPorDia = count($horarios);

                for ($dia = 0; $dia < $dias; $dia++) {
                    $fechaActual = $fechaInicio->copy()->addDays($dia);

                    foreach ($horarios as $horario) {
                        if ($tomasRestantes <= 0) break;

                        list($hora, $minuto) = explode(':', $horario);
                        $fechaHoraProgramada = $fechaActual->copy()->setTime((int)$hora, (int)$minuto);

                        SeguimientoToma::create([
                            'id_receta' => $recetaId,
                            'id_medicamentos_receta' => $medicamento->id,
                            'fecha_hora_programada' => $fechaHoraProgramada,
                            'estado' => 'pendiente'
                        ]);

                        $tomasRestantes--;
                    }
                }
                
                // Marcar recordatorios como activados para este medicamento
                $medicamento->update(['recordatorios_activados' => true]);
            }

            DB::commit();
            return response()->json(['message' => 'Tomas programadas exitosamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al programar tomas', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene las tomas de un día específico (por defecto hoy).
     */
    public function obtenerTomasPorDia(Request $request, $recetaId)
    {
        $fecha = $request->has('fecha') ? Carbon::parse($request->fecha) : Carbon::today();

        $tomas = SeguimientoToma::with(['medicamentoReceta.medicamento'])
            ->where('id_receta', $recetaId)
            ->whereDate('fecha_hora_programada', $fecha)
            ->orderBy('fecha_hora_programada', 'asc')
            ->get();

        $manana = [];
        $tarde = [];
        $noche = [];

        foreach ($tomas as $toma) {
            $hora = Carbon::parse($toma->fecha_hora_programada)->format('H');
            $data = [
                'id' => $toma->id,
                'hora' => Carbon::parse($toma->fecha_hora_programada)->format('H:i'),
                'nombre' => $toma->medicamentoReceta->medicamento->nombre ?? 'Medicamento',
                'dosis' => $toma->medicamentoReceta->cantidad . ' ' . $toma->medicamentoReceta->unidad_formato,
                'estado' => $toma->estado,
            ];

            if ($hora < 12) {
                $manana[] = $data;
            } elseif ($hora < 18) {
                $tarde[] = $data;
            } else {
                $noche[] = $data;
            }
        }

        return response()->json([
            'fecha' => $fecha->format('Y-m-d'),
            'manana' => $manana,
            'tarde' => $tarde,
            'noche' => $noche,
        ]);
    }

    /**
     * Marca o actualiza el estado de una toma individual.
     */
    public function marcarToma(Request $request, $tomaId)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,tomada,fuera_de_hora,no_tomada'
        ]);

        $toma = SeguimientoToma::findOrFail($tomaId);
        $toma->estado = $request->estado;
        
        if ($request->estado === 'tomada' || $request->estado === 'fuera_de_hora') {
            $toma->fecha_hora_real = now();
        } else {
            $toma->fecha_hora_real = null;
        }

        $toma->save();

        return response()->json(['message' => 'Toma actualizada', 'toma' => $toma]);
    }
}
