<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RobotCallOutbox extends Model
{
    protected $table = 'robot_call_outbox';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'idempotency_key',
        'tipo',
        'celular',
        'payload_cifrado',
        'estado',
        'intentos',
        'expira_at',
        'disponible_at',
        'lease_hasta',
        'bridge_id',
        'job_id_remoto',
        'ultimo_error',
    ];

    protected $casts = [
        'expira_at' => 'datetime',
        'disponible_at' => 'datetime',
        'lease_hasta' => 'datetime',
        'intentos' => 'integer',
    ];
}
