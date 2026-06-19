<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $table = 'pacientes';
    public $timestamps = true;

    protected $fillable = [
        'id_usuario', 'nombres', 'apellido_paterno', 'apellido_materno',
        'fecha_nacimiento', 'sexo', 'tipo_seguro', 'codigo_asegurado',
        'operador_celular', 'celular', 'telefono_fijo', 'departamento',
        'provincia', 'distrito', 'tipo_via', 'direccion', 'referencia',
        'id_ipress_asignada'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function ipress()
    {
        return $this->belongsTo(Ipress::class, 'id_ipress_asignada');
    }
}
