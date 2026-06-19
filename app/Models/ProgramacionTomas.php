<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramacionTomas extends Model
{
    protected $table = 'programacion_tomas';
    public $timestamps = true;

    protected $fillable = [
        'id_medicamento_receta', 'id_paciente', 'fecha_inicio',
        'hora_inicio', 'tipo_frecuencia', 'valor_frecuencia',
        'cantidad_por_toma', 'duracion_dias', 'numero_tomas_total',
        'esta_activa', 'id_usuario'
    ];

    public function medicamentoReceta()
    {
        return $this->belongsTo(MedicamentoReceta::class, 'id_medicamento_receta');
    }
}
