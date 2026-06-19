<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioDisponible extends Model
{
    protected $table = 'horarios_disponibles';
    public $timestamps = true;

    protected $fillable = [
        'id_profesional', 'id_especialidad', 'id_ipress',
        'fecha', 'hora_inicio', 'hora_fin', 'tipo_cita',
        'cupo_maximo', 'cupo_ocupado', 'esta_disponible', 'id_usuario'
    ];

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
