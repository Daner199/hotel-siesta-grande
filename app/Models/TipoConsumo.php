<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de tipos de consumo (se carga por SQL): RESTAURANTE y FRIGOBAR.
 */
class TipoConsumo extends Model
{
    public const RESTAURANTE = 'RESTAURANTE';
    public const FRIGOBAR    = 'FRIGOBAR';

    protected $table = 'tipo_consumo';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public function etiqueta(): string
    {
        return match ($this->nombre) {
            self::RESTAURANTE => 'Restaurante',
            self::FRIGOBAR    => 'Frigobar',
            default           => $this->nombre,
        };
    }
}
