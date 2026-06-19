<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'dni' => 'required|string',
            'contrasena' => 'required|string',
        ]);

        $usuario = Usuario::where('dni', $request->dni)->first();

        if (!$usuario || !Hash::check($request->contrasena, $usuario->contrasena)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        if (!$usuario->esta_activo) {
            return response()->json(['message' => 'Usuario inactivo'], 403);
        }

        $usuario->update(['ultimo_acceso' => now()]);
        
        $token = $usuario->createToken('miconsulta_app_token')->plainTextToken;

        $paciente = $usuario->paciente()->with('ipress')->first();

        return response()->json([
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'dni' => $usuario->dni,
                'correo' => $usuario->correo,
                'nombres' => $paciente ? $paciente->nombres : null,
                'apellidos' => $paciente ? ($paciente->apellido_paterno . ' ' . $paciente->apellido_materno) : null,
                'usa_biometria' => (bool) $usuario->usa_biometria,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function olvideContrasena(Request $request)
    {
        $request->validate(['dni' => 'required|string']);
        $usuario = Usuario::where('dni', $request->dni)->first();

        if (!$usuario) {
            return response()->json(['message' => 'DNI no encontrado'], 404);
        }

        // Siempre 123456 en MVP
        return response()->json([
            'message' => 'Código enviado al correo',
            'correo_enmascarado' => $this->maskEmail($usuario->correo)
        ]);
    }

    public function verificarCodigo(Request $request)
    {
        $request->validate([
            'dni' => 'required|string',
            'codigo' => 'required|string'
        ]);

        if ($request->codigo !== '123456') {
            return response()->json(['message' => 'Código inválido'], 400);
        }

        return response()->json(['message' => 'Código verificado']);
    }

    public function cambiarContrasena(Request $request)
    {
        $request->validate([
            'dni' => 'required|string',
            'codigo' => 'required|string',
            'nueva_contrasena' => 'required|string|min:6'
        ]);

        if ($request->codigo !== '123456') {
            return response()->json(['message' => 'Código inválido'], 400);
        }

        $usuario = Usuario::where('dni', $request->dni)->first();
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $usuario->update(['contrasena' => Hash::make($request->nueva_contrasena)]);

        return response()->json(['message' => 'Contraseña cambiada exitosamente']);
    }

    public function actualizarBiometria(Request $request)
    {
        $request->validate(['usa_biometria' => 'required|boolean']);
        $request->user()->update(['usa_biometria' => $request->usa_biometria]);
        return response()->json(['message' => 'Preferencia de biometría actualizada']);
    }

    private function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) != 2) return $email;
        $name = $parts[0];
        $domain = $parts[1];
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
        return $maskedName . '@' . $domain;
    }
}
