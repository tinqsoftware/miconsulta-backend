<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $configuraciones = Configuracion::all();
        // Index by key for easy access in view
        $config = [];
        foreach ($configuraciones as $c) {
            $config[$c->clave] = $c;
        }

        return view('admin.configuraciones.index', compact('config'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'link_telemedicina' => 'nullable|string|max:255',
        ]);

        $telemedicina = Configuracion::where('clave', 'link_telemedicina')->first();
        if ($telemedicina) {
            $telemedicina->valor = $request->link_telemedicina;
            $telemedicina->save();
        } else {
            Configuracion::create([
                'clave' => 'link_telemedicina',
                'valor' => $request->link_telemedicina,
                'descripcion' => 'Enlace dinámico para el botón de Próximas Citas - Telemedicina'
            ]);
        }

        return redirect()->route('admin.configuraciones')->with('success', 'Configuraciones actualizadas exitosamente.');
    }
}
