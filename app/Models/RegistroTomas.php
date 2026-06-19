<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroTomas extends Model
{
    protected $table = 'registro_tomas';
    public $timestamps = true;

    protected $fillable = [
        'id_programacion', 'id_paciente', 'id_medicamento',
        'fecha_programada', 'hora_programada', 'fecha_real',
        'hora_real', 'estado', 'minutos_pospuestos',
        'periodo_dia', 'id_usuario'
    ];

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class, 'id_medicamento');
    }

    public function programacion()
    {
        return $this->belongsTo(ProgramacionTomas::class, 'id_programacion');
    }
}
