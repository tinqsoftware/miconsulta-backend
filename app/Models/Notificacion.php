<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
    public $timestamps = true;

    protected $fillable = [
        'id_paciente', 'tipo', 'titulo', 'mensaje', 
        'categoria', 'datos_extra', 'fue_leida', 
        'fecha_envio', 'id_usuario'
    ];

    protected $casts = [
        'datos_extra' => 'array',
        'fue_leida' => 'boolean',
        'fecha_envio' => 'datetime',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }
}
