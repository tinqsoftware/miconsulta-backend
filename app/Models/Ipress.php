<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ipress extends Model
{
    protected $table = 'ipress';
    public $timestamps = true;

    protected $fillable = [
        'codigo_renipress', 'nombre', 'direccion', 'telefono',
        'nivel', 'latitud', 'longitud', 'horario_atencion', 'esta_activa'
    ];
}
