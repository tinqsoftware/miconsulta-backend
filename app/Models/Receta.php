<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    protected $table = 'recetas';
    public $timestamps = true;

    protected $fillable = [
        'numero_receta', 'id_paciente', 'id_profesional', 'id_ipress',
        'id_especialidad', 'acto_medico', 'numero_acto_medico',
        'fecha_emision', 'fecha_vigencia', 'estado', 'url_pdf', 'id_usuario'
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function profesional()
    {
        return $this->belongsTo(Profesional::class, 'id_profesional');
    }

    public function ipress()
    {
        return $this->belongsTo(Ipress::class, 'id_ipress');
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }

    public function medicamentos()
    {
        return $this->hasMany(MedicamentoReceta::class, 'id_receta')->with('medicamento');
    }
}
