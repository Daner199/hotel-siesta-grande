<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Precio por noche de un tipo en un rango de fechas [fecha_desde, fecha_hasta).
 * fecha_hasta NULL = sin fecha de fin. No se editan: un cambio de precio es una tarifa nueva.
 */
class TarifaHabitacion extends Model
{
    protected $table = 'tarifa_habitacion';

    public $timestamps = false;

    protected $fillable = [
        'tipo_habitacion_id',
        'fecha_desde',
        'fecha_hasta',
        'precio_noche',
        'activa',
    ];

    protected $casts = [
        'fecha_desde'  => 'date',
        'fecha_hasta'  => 'date',
        'precio_noche' => 'decimal:2',
        'activa'       => 'boolean',
    ];

    public const VIGENTE    = 'VIGENTE';
    public const PROGRAMADA = 'PROGRAMADA';
    public const FINALIZADA = 'FINALIZADA';
    public const ANULADA    = 'ANULADA';

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoHabitacion::class, 'tipo_habitacion_id');
    }

    // Estado según la fecha de hoy (no se guarda en la BD, se calcula)
    public function estado(): string
    {
        $hoy = today();

        $terminada = $this->fecha_hasta !== null && $this->fecha_hasta->lte($hoy);

        return match (true) {
            ! $this->activa              => self::ANULADA,
            $this->fecha_desde->gt($hoy) => self::PROGRAMADA,
            $terminada                   => self::FINALIZADA,
            default                      => self::VIGENTE,
        };
    }

    // Solo se puede anular una tarifa que todavía no empezó
    public function sePuedeAnular(): bool
    {
        return $this->estado() === self::PROGRAMADA;
    }
}
