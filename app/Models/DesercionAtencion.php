<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesercionAtencion extends Model
{
    protected $table = 'deserciones_atencion';

    protected $fillable = [
        'id_cita',
        'id_paciente',
        'token_acceso',
        'motivo_codigo',
        'motivo_descripcion',
        'enviada_at',
    ];

    protected $casts = [
        'enviada_at' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }
}
