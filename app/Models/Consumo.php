<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consumo del restaurante o del frigobar cargado a la cuenta de una reserva alojada (CHECK_IN).
 * El precio unitario queda guardado como se cobró. Se paga en el check-out.
 */
class Consumo extends Model
{
    protected $table = 'consumo';

    // Solo tiene fecha_consumo (la pone la BD)
    public $timestamps = false;

    protected $fillable = ['reserva_id', 'tipo_consumo_id', 'descripcion', 'cantidad', 'precio_unitario', 'registrado_por_usuario_id'];

    protected $casts = [
        'cantidad'        => 'integer',
        'precio_unitario' => 'decimal:2',
        'fecha_consumo'   => 'datetime',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoConsumo::class, 'tipo_consumo_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por_usuario_id');
    }

    public function importe(): float
    {
        return round($this->cantidad * (float) $this->precio_unitario, 2);
    }
}
