<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de estados físicos (se carga por SQL, no se edita desde el sistema).
 */
class EstadoHabitacion extends Model
{
    public const ACTIVA         = 'ACTIVA';
    public const MANTENIMIENTO  = 'MANTENIMIENTO';
    public const FUERA_SERVICIO = 'FUERA_SERVICIO';

    protected $table = 'estado_habitacion';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public function habitaciones(): HasMany
    {
        return $this->hasMany(Habitacion::class, 'estado_habitacion_id');
    }

    // "FUERA_SERVICIO" → "Fuera de servicio" (para mostrar en pantalla)
    public function etiqueta(): string
    {
        return match ($this->nombre) {
            self::ACTIVA         => 'Activa',
            self::MANTENIMIENTO  => 'Mantenimiento',
            self::FUERA_SERVICIO => 'Fuera de servicio',
            default              => $this->nombre,
        };
    }
}
