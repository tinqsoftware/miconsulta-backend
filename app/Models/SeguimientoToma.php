<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoToma extends Model
{
    protected $table = 'seguimiento_tomas';
    public $timestamps = true;

    protected $fillable = [
        'id_receta', 
        'id_medicamentos_receta', 
        'fecha_hora_programada', 
        'fecha_hora_real', 
        'estado'
    ];

    protected $casts = [
        'fecha_hora_programada' => 'datetime',
        'fecha_hora_real' => 'datetime',
    ];

    public function receta()
    {
        return $this->belongsTo(Receta::class, 'id_receta');
    }

    public function medicamentoReceta()
    {
        return $this->belongsTo(MedicamentoReceta::class, 'id_medicamentos_receta');
    }
}
