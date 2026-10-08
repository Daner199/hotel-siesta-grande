<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoTipoHabitacion extends Foto
{
    protected $table = 'foto_tipo_habitacion';

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoHabitacion::class, 'tipo_habitacion_id');
    }
}
