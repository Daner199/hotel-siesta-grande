<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Beneficios configurables (Desayuno, Piscina...). Se administran en el Módulo 3.
 */
class Beneficio extends Model
{
    protected $table = 'beneficio';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function promociones(): BelongsToMany
    {
        return $this->belongsToMany(Promocion::class, 'promocion_beneficio', 'beneficio_id', 'promocion_id');
    }

    // "ACCESO A PISCINA" → "Acceso a piscina"
    public function etiqueta(): string
    {
        $texto = mb_strtolower($this->nombre);

        return mb_strtoupper(mb_substr($texto, 0, 1)) . mb_substr($texto, 1);
    }

    // Ícono de Lucide según el nombre (si no reconoce el nombre, uno genérico)
    public function icono(): string
    {
        $nombre = mb_strtolower($this->nombre);

        return match (true) {
            str_contains($nombre, 'desayuno')        => 'coffee',
            str_contains($nombre, 'almuerzo')        => 'utensils',
            str_contains($nombre, 'cena')            => 'wine',
            str_contains($nombre, 'piscina')         => 'waves',
            str_contains($nombre, 'estacionamiento') => 'car',
            str_contains($nombre, 'wifi')            => 'wifi',
            default                                  => 'sparkles',
        };
    }
}
