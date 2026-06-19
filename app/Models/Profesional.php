<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profesional extends Model
{
    protected $table = 'profesionales';
    public $timestamps = true;

    protected $fillable = [
        'nombres', 'apellidos', 'numero_colegiatura', 
        'id_especialidad', 'id_ipress', 'esta_activo', 'id_usuario'
    ];

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }

    public function ipress()
    {
        return $this->belongsTo(Ipress::class, 'id_ipress');
    }
}
