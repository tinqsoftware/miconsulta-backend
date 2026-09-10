<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionAtencion extends Model
{
    protected $table = 'evaluaciones_atencion';

    protected $fillable = [
        'id_cita', 'id_paciente', 'id_profesional', 'token_acceso',
        'puntuacion', 'conexion_puntualidad', 'escucha_trato',
        'explicacion_diagnostico', 'explicacion_tratamiento',
        'claridad_proximos_pasos', 'comentario', 'enviada_at',
    ];

    protected $casts = [
        'puntuacion' => 'decimal:1',
        'conexion_puntualidad' => 'integer',
        'escucha_trato' => 'integer',
        'explicacion_diagnostico' => 'integer',
        'explicacion_tratamiento' => 'integer',
        'claridad_proximos_pasos' => 'integer',
        'enviada_at' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }
}
