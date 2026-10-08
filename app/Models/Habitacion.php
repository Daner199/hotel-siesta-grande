<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // Fotos propias (opcionales: vista, balcón...)
    public function fotos(): HasMany
    {
        return $this->hasMany(FotoHabitacion::class, 'habitacion_id')
            ->orderBy('orden')->orderBy('id');
    }

    // Carpeta de sus fotos dentro de storage/app/public
    public function carpetaFotos(): string
    {
        return "habitaciones/{$this->id}";
    }

    /**
     * Fotos para mostrar al reservar: las propias, o las de su tipo si no tiene.
     * La principal va primero.
     */
    public function galeria(): Collection
    {
        $fotos = $this->fotos->isNotEmpty() ? $this->fotos : $this->tipo->fotos;

        return $fotos->sortByDesc('es_principal')->values();
    }
}
