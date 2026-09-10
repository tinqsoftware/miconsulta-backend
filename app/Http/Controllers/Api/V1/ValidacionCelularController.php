<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\ValidacionCelular;
use App\Services\FirebaseMessagingService;
use App\Services\RobotCallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ValidacionCelularController extends Controller
{
    public function solicitar(Request $request)
    {
        $data = $request->validate([
            'celular' => ['required', 'regex:/^9\\d{8}$/'],
            'canal' => 'required|in:push,llamada',
        ], [
            'celular.regex' => 'Ingresa un número celular peruano válido de 9 dígitos.',
        ]);

        $usuario = $request->user();
        $paciente = $usuario->paciente;
        abort_unless($paciente, 404, 'Paciente no encontrado.');

        if ($data['canal'] === 'push' && !$usuario->token_fcm) {
            return response()->json([
                'message' => 'No podemos enviar una notificación push a este dispositivo. Activa las notificaciones e inténtalo otra vez.',
            ], 422);
        }

        $codigo = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $validacion = ValidacionCelular::updateOrCreate(
            ['id_usuario' => $usuario->id],
            [
                'celular' => $data['celular'],
                'codigo_hash' => Hash::make($codigo),
                'canal' => $data['canal'],
                'intentos' => 0,
                'expira_at' => now()->addMinutes(10),
                'verificado_at' => null,
            ]
        );

        if ($data['canal'] === 'push') {
            $titulo = 'Código para validar tu celular';
            $mensaje = "Tu código de validación es {$codigo}. Vence en 10 minutos.";

            app(FirebaseMessagingService::class)->send($usuario->token_fcm, $titulo, $mensaje, [
                'ruta' => '/perfil',
                'tipo' => 'validacion_celular',
            ]);

            Notificacion::create([
                'id_paciente' => $paciente->id,
                'tipo' => 'validacion_celular',
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'categoria' => 'general',
                'datos_extra' => ['ruta' => '/perfil', 'tipo' => 'validacion_celular'],
                'fue_leida' => false,
                'fecha_envio' => now(),
            ]);
        } else {
            app(RobotCallService::class)->validarCelular($data['celular'], $codigo);
        }

        return response()->json([
            'message' => $data['canal'] === 'push'
                ? 'Enviamos un código de cuatro dígitos por notificación push.'
                : 'Solicitamos una llamada automática con tu código de validación.',
            'data' => [
                'canal' => $validacion->canal,
                'expira_at' => $validacion->expira_at->toIso8601String(),
            ],
        ]);
    }

    public function confirmar(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required', 'regex:/^\\d{4}$/'],
        ]);

        $usuario = $request->user();
        $validacion = ValidacionCelular::where('id_usuario', $usuario->id)->firstOrFail();

        if ($validacion->verificado_at) {
            return response()->json(['message' => 'Este número celular ya fue validado.'], 409);
        }
        if ($validacion->expira_at->isPast()) {
            return response()->json(['message' => 'El código venció. Solicita uno nuevo.'], 422);
        }
        if ($validacion->intentos >= 5) {
            return response()->json(['message' => 'Superaste el número de intentos. Solicita un código nuevo.'], 429);
        }

        $validacion->increment('intentos');
        if (!Hash::check($data['codigo'], $validacion->codigo_hash)) {
            return response()->json(['message' => 'El código no coincide. Verifica e inténtalo nuevamente.'], 422);
        }

        $validacion->update(['verificado_at' => now()]);
        $usuario->paciente?->update(['celular' => $validacion->celular]);

        return response()->json([
            'message' => 'Tu número celular fue validado correctamente.',
            'data' => ['celular' => $validacion->celular],
        ]);
    }
}
