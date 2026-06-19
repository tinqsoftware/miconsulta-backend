<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicamento extends Model
{
    protected $table = 'medicamentos';
    public $timestamps = true;

    protected $fillable = [
        'nombre_generico', 'nombre_comercial', 'forma_farmaceutica',
        'concentracion', 'unidad_medida', 'esta_activo', 'id_usuario'
    ];
}
