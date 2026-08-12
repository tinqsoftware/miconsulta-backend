<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionAtencion extends Model
{
    protected $table = 'evaluaciones_atencion';

    protected $fillable = [
        'id_cita', 'id_paciente', 'id_profesional', 'token_acceso',
        'puntuacion', 'comentario', 'enviada_at',
    ];

    protected $casts = [
        'puntuacion' => 'decimal:1',
        'enviada_at' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }
}
