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

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoHabitacion::class, 'tipo_habitacion_id');
    }
}
