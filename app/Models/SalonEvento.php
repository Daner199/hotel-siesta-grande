<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Salón de eventos. Se administra en el Módulo 8.
 */
class SalonEvento extends Model
{
    protected $table = 'salon_evento';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'capacidad', 'costo_hora', 'activo'];

    protected $casts = [
        'capacidad'  => 'integer',
        'costo_hora' => 'decimal:2',
        'activo'     => 'boolean',
    ];

    public function fotos(): HasMany
    {
        return $this->hasMany(FotoSalonEvento::class, 'salon_evento_id')
            ->orderByDesc('es_principal')->orderBy('orden')->orderBy('id');
    }

    public function carpetaFotos(): string
    {
        return "salones/{$this->id}";
    }
}
