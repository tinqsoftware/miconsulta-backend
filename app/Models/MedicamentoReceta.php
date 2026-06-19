<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicamentoReceta extends Model
{
    protected $table = 'medicamentos_receta';
    public $timestamps = true;

    protected $fillable = [
        'id_receta', 'id_medicamento', 'cantidad', 
        'unidad_formato', 'indicacion', 'esta_disponible', 'id_usuario'
    ];

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class, 'id_medicamento');
    }

    public function receta()
    {
        return $this->belongsTo(Receta::class, 'id_receta');
    }
}
