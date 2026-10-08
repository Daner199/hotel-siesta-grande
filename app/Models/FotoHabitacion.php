<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoHabitacion extends Foto
{
    protected $table = 'foto_habitacion';

    public function habitacion(): BelongsTo
    {
        return $this->belongsTo(Habitacion::class, 'habitacion_id');
    }
}
