<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base de las fotos de galería (por tipo y por habitación): misma estructura y reglas.
 * El archivo vive en storage/app/public; en la BD solo se guarda su ruta.
 */
abstract class Foto extends Model
{
    // Solo tiene created_at
    const UPDATED_AT = null;

    protected $fillable = ['ruta', 'orden', 'es_principal'];

    protected $casts = [
        'orden'        => 'integer',
        'es_principal' => 'boolean',
        'created_at'   => 'datetime',
    ];

    // Dirección pública de la imagen (usa el host actual, no APP_URL)
    public function url(): string
    {
        return asset('storage/' . $this->ruta);
    }
}
