<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Promociones con vigencia, beneficios y tipos de habitación. Se administran en el Módulo 3.
 * Si no tiene tipos asociados, aplica a todas las habitaciones.
 */
class Promocion extends Model
{
    protected $table = 'promocion';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'fecha_desde', 'fecha_hasta', 'porcentaje_descuento', 'activo'];

    protected $casts = [
        'fecha_desde'          => 'date',
        'fecha_hasta'          => 'date',
        'porcentaje_descuento' => 'decimal:2',
        'activo'               => 'boolean',
    ];

    public function beneficios(): BelongsToMany
    {
        return $this->belongsToMany(Beneficio::class, 'promocion_beneficio', 'promocion_id', 'beneficio_id');
    }

    // Tipos a los que aplica, con su precio especial por noche (si tiene)
    public function tipos(): BelongsToMany
    {
        return $this->belongsToMany(TipoHabitacion::class, 'promocion_tipo_habitacion', 'promocion_id', 'tipo_habitacion_id')
            ->withPivot('precio_noche');
    }

    /**
     * Activas y vigentes en una fecha (hoy si no se indica).
     * fecha_hasta se toma como el último día válido (incluido). A confirmar en el Módulo 3.
     */
    public function scopeVigentes(Builder $q, Carbon|string|null $fecha = null): Builder
    {
        $dia = Carbon::parse($fecha ?? today())->toDateString();

        return $q->where('activo', true)
            ->where('fecha_desde', '<=', $dia)
            ->where(fn ($w) => $w->whereNull('fecha_hasta')->orWhere('fecha_hasta', '>=', $dia));
    }
}
