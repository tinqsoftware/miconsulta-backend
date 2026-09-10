<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValidacionCelular extends Model
{
    protected $table = 'validaciones_celular';

    protected $fillable = [
        'id_usuario',
        'celular',
        'codigo_hash',
        'canal',
        'intentos',
        'expira_at',
        'verificado_at',
    ];

    protected $casts = [
        'expira_at' => 'datetime',
        'verificado_at' => 'datetime',
        'intentos' => 'integer',
    ];
}
