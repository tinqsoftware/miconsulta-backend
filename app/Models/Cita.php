<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $table = 'citas';
    public $timestamps = true;

    protected $fillable = [
        'id_paciente', 'id_horario', 'id_profesional', 'id_especialidad',
        'id_ipress', 'tipo_cita', 'fecha', 'hora', 'estado',
        'motivo_consulta', 'url_audio_sintomas', 'id_usuario'
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function horario()
    {
        return $this->belongsTo(HorarioDisponible::class, 'id_horario');
    }

    public function profesional()
    {
        return $this->belongsTo(Profesional::class, 'id_profesional');
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }

    public function ipress()
    {
        return $this->belongsTo(Ipress::class, 'id_ipress');
    }
}
