<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';
    public $timestamps = true;

    protected $fillable = [
        'dni', 'contrasena', 'correo', 'esta_activo', 'token_fcm', 'usa_biometria', 'ultimo_acceso'
    ];

    protected $hidden = [
        'contrasena',
    ];

    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'id_usuario');
    }
}
