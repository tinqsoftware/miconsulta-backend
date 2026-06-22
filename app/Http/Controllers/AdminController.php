<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario;
use App\Models\Paciente;
use App\Models\Cita;
use App\Models\Receta;
use App\Models\Profesional;
use App\Models\Especialidad;
use App\Models\Ipress;
use App\Models\Banner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function resetDb()
    {
        try {
            $path = base_path('../database/miconsulta_db.mysql');
            if (!file_exists($path)) {
                return back()->with('error', 'Archivo SQL no encontrado en ' . $path);
            }
            
            $sql = file_get_contents($path);
            DB::unprepared($sql);

            return back()->with('success', '¡Base de datos reseteada a la versión inicial exitosamente!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al resetear BD: ' . $e->getMessage());
        }
    }
    public function dashboard()
    {
        $totalPacientes = Paciente::count();
        $totalCitas = Cita::count();
        $citasHoy = Cita::whereDate('fecha', now()->toDateString())->count();

        return view('admin.dashboard', compact('totalPacientes', 'totalCitas', 'citasHoy'));
    }

    public function pacientes()
    {
        $pacientes = Paciente::with('ipress')->paginate(15);
        return view('admin.pacientes.index', compact('pacientes'));
    }

    public function citas()
    {
        $citas = Cita::with(['paciente', 'profesional', 'especialidad', 'ipress'])->orderBy('fecha', 'desc')->paginate(15);
        return view('admin.citas.index', compact('citas'));
    }

    public function cambiarEstadoCita(Request $request, $id)
    {
        $cita = Cita::findOrFail($id);
        $estado = $request->input('estado');
        
        if (in_array($estado, ['programada', 'confirmada', 'completada', 'cancelada', 'no_asistio'])) {
            $cita->estado = $estado;
            $cita->save();
            return back()->with('success', 'Estado de la cita actualizado a ' . strtoupper($estado));
        }

        return back()->with('error', 'Estado inválido');
    }

    public function recetas()
    {
        $recetas = Receta::with(['paciente', 'profesional', 'especialidad'])->orderBy('fecha_emision', 'desc')->paginate(15);
        return view('admin.recetas.index', compact('recetas'));
    }

    public function createReceta()
    {
        $pacientes = Paciente::all();
        $profesionales = Profesional::all();
        $especialidades = Especialidad::all();
        $ipress = Ipress::all();
        return view('admin.recetas.create', compact('pacientes', 'profesionales', 'especialidades', 'ipress'));
    }

    public function storeReceta(Request $request)
    {
        $request->validate([
            'id_paciente' => 'required',
            'id_profesional' => 'required',
            'id_especialidad' => 'required',
            'id_ipress' => 'required',
            'acto_medico' => 'required',
        ]);

        $receta = new Receta();
        $receta->numero_receta = 'REC-' . strtoupper(Str::random(8));
        $receta->id_paciente = $request->id_paciente;
        $receta->id_profesional = $request->id_profesional;
        $receta->id_especialidad = $request->id_especialidad;
        $receta->id_ipress = $request->id_ipress;
        $receta->acto_medico = $request->acto_medico;
        $receta->numero_acto_medico = 'AM-' . rand(1000, 9999);
        $receta->fecha_emision = now()->toDateString();
        $receta->fecha_vigencia = now()->addDays(30)->toDateString();
        $receta->estado = 'vigente';
        $receta->save();

        return redirect()->route('admin.recetas')->with('success', 'Receta emitida correctamente.');
    }

    public function banners()
    {
        $banners = Banner::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.banners.index', compact('banners'));
    }

    public function createBanner()
    {
        return view('admin.banners.create');
    }

    public function storeBanner(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link_url' => 'nullable|string'
        ]);

        $banner = new Banner();
        $banner->titulo = $request->titulo;
        
        if ($request->hasFile('imagen')) {
            $imageName = time().'.'.$request->imagen->extension();  
            $path = public_path('uploads/banners');
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $request->imagen->move($path, $imageName);
            $banner->imagen_url = '/uploads/banners/' . $imageName;
        }

        $banner->link_url = $request->link_url;
        $banner->estado = $request->has('estado') ? true : false;
        $banner->save();

        return redirect()->route('admin.banners')->with('success', 'Banner publicitario subido exitosamente.');
    }

    public function editBanner($id)
    {
        $banner = Banner::findOrFail($id);
        return view('admin.banners.edit', compact('banner'));
    }

    public function updateBanner(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'titulo' => 'required|string|max:255',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link_url' => 'nullable|string'
        ]);

        $banner->titulo = $request->titulo;
        
        if ($request->hasFile('imagen')) {
            $imageName = time().'.'.$request->imagen->extension();  
            $path = public_path('uploads/banners');
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $request->imagen->move($path, $imageName);
            $banner->imagen_url = '/uploads/banners/' . $imageName;
        }

        $banner->link_url = $request->link_url;
        $banner->estado = $request->has('estado') ? true : false;
        $banner->save();

        return redirect()->route('admin.banners')->with('success', 'Banner actualizado correctamente.');
    }

    public function toggleBannerEstado($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->estado = !$banner->estado;
        $banner->save();

        return back()->with('success', 'El estado del banner ha sido actualizado.');
    }
}
