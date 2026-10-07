<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    // Nombres de los roles, para no escribirlos a mano en todo el sistema
    public const CLIENTE       = 'CLIENTE';
    public const AGENCIA       = 'AGENCIA';
    public const RECEPCIONISTA = 'RECEPCIONISTA';
    public const ADMINISTRADOR = 'ADMINISTRADOR';

    // Tabla de PostgreSQL que usa este modelo
    protected $table = 'usuario';

    // Columnas que se pueden llenar desde formularios
    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'email',
        'password',
        'rol',
        'activo',
    ];

    // Columnas que nunca se muestran al convertir el usuario a JSON
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            // Al guardar, Laravel convierte la contraseña en hash automáticamente
            'password' => 'hashed',
            'activo'   => 'boolean',
        ];
    }

    // Un usuario con rol AGENCIA tiene una fila en la tabla agencia
    public function agencia(): HasOne
    {
        return $this->hasOne(Agencia::class, 'usuario_id');
    }
        // A qué panel va cada rol después de iniciar sesión
    public function rutaInicio(): string
    {
        return match ($this->rol) {
            self::ADMINISTRADOR => route('admin.inicio'),
            self::RECEPCIONISTA => route('recepcion.inicio'),
            self::AGENCIA       => route('agencia.inicio'),
            default             => route('cliente.inicio'),
        };
    }

    public function nombreCompleto(): string
    {
        return trim($this->nombre . ' ' . $this->apellido);
    }
        // "Lucía Rojas" → "LR" (para los avatares)
    public function iniciales(): string
    {
        return mb_strtoupper(
            mb_substr($this->nombre, 0, 1) . mb_substr($this->apellido ?? '', 0, 1)
        );
    }
}