<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    protected $table = 'especialidades';
    public $timestamps = true;

    protected $fillable = [
        'nombre', 'codigo', 'esta_activa', 'id_usuario'
    ];
}
