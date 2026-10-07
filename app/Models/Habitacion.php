<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Habitacion extends Model
{
    protected $table = 'habitacion';

    public $timestamps = false;

    protected $fillable = [
        'numero',
        'piso',
        'tipo_habitacion_id',
        'estado_habitacion_id',
        'descripcion',
    ];

    protected $casts = [
        'piso' => 'integer',
    ];

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoHabitacion::class, 'tipo_habitacion_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoHabitacion::class, 'estado_habitacion_id');
    }
}
