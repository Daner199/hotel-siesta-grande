<?php

namespace App\Models;

use App\Support\Disponibilidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una habitación dentro de una reserva, con el precio por noche que se cobró
 * (tarifa del día de llegada, ya con el −10 % si la reservó una agencia).
 * Ese precio no cambia aunque después cambie la tarifa.
 */
class ReservaHabitacion extends Model
{
    protected $table = 'reserva_habitacion';

    public $timestamps = false;

    protected $fillable = ['reserva_id', 'habitacion_id', 'promocion_id', 'precio_noche_aplicado'];

    protected $casts = ['precio_noche_aplicado' => 'decimal:2'];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_id');
    }

    // Total de esta habitación por toda la estadía (con el 15 % desde la 8.ª noche)
    public function estadia(): array
    {
        return Disponibilidad::calcularEstadia((float) $this->precio_noche_aplicado, $this->reserva->noches());
    }
}
