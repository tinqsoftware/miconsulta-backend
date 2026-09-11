<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ipress extends Model
{
    protected $table = 'ipress';
    public $timestamps = true;

    protected $fillable = [
        'codigo_renipress', 'nombre', 'direccion', 'telefono',
        'nivel', 'latitud', 'longitud', 'horario_atencion', 'esta_activa',
        'tiene_emergencia', 'servicios',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'esta_activa' => 'boolean',
        'tiene_emergencia' => 'boolean',
        'servicios' => 'array',
    ];
}
