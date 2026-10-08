<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TipoHabitacion extends Model
{
    protected $table = 'tipo_habitacion';

    // La tabla no tiene created_at ni updated_at
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'capacidad',
        'activo',
    ];

    protected $casts = [
        'capacidad' => 'integer',
        'activo'    => 'boolean',
    ];

    // Habitaciones de este tipo
    public function habitaciones(): HasMany
    {
        return $this->hasMany(Habitacion::class, 'tipo_habitacion_id');
    }

    // Historial de tarifas de este tipo
    public function tarifas(): HasMany
    {
        return $this->hasMany(TarifaHabitacion::class, 'tipo_habitacion_id');
    }

    // Galería del tipo (la usa la landing), en el orden elegido por el admin
    public function fotos(): HasMany
    {
        return $this->hasMany(FotoTipoHabitacion::class, 'tipo_habitacion_id')
            ->orderBy('orden')->orderBy('id');
    }

    public function fotoPrincipal(): HasOne
    {
        return $this->hasOne(FotoTipoHabitacion::class, 'tipo_habitacion_id')->where('es_principal', true);
    }

    // "SUITE PRESIDENCIAL" → "Suite presidencial" (para la página pública)
    public function nombreVisible(): string
    {
        $texto = mb_strtolower($this->nombre);

        return mb_strtoupper(mb_substr($texto, 0, 1)) . mb_substr($texto, 1);
    }

    // Carpeta de sus fotos dentro de storage/app/public
    public function carpetaFotos(): string
    {
        return "tipos/{$this->id}";
    }

    /**
     * Tarifa que rige en una fecha (hoy si no se indica).
     * Rango [fecha_desde, fecha_hasta): el día fecha_hasta ya rige la siguiente.
     */
    public function tarifaVigente(Carbon|string|null $fecha = null): ?TarifaHabitacion
    {
        $dia = Carbon::parse($fecha ?? today())->toDateString();

        return $this->tarifas()
            ->where('activa', true)
            ->where('fecha_desde', '<=', $dia)
            ->where(fn ($q) => $q->whereNull('fecha_hasta')->orWhere('fecha_hasta', '>', $dia))
            ->first();
    }

    // Próxima tarifa que empieza en el futuro (si hay una programada)
    public function tarifaProgramada(): ?TarifaHabitacion
    {
        return $this->tarifas()
            ->where('activa', true)
            ->where('fecha_desde', '>', today()->toDateString())
            ->orderBy('fecha_desde')
            ->first();
    }

    // La tarifa activa que empieza más tarde: una nueva solo puede empezar después de ella
    public function ultimaTarifa(): ?TarifaHabitacion
    {
        return $this->tarifas()
            ->where('activa', true)
            ->orderByDesc('fecha_desde')
            ->first();
    }
}
