<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloqueo temporal de una habitación (mantenimiento...). Rango [fecha_desde, fecha_hasta):
 * fecha_hasta es el día en que vuelve a estar libre. Ocupa la habitación igual que una reserva.
 */
class BloqueoHabitacion extends Model
{
    protected $table = 'bloqueo_habitacion';

    public $timestamps = false;

    protected $fillable = ['habitacion_id', 'fecha_desde', 'fecha_hasta', 'motivo'];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
    ];

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_id');
    }
}
