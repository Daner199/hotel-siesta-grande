<?php

namespace App\Support;

use App\Models\Pago;

/**
 * Números que se muestran al lado de un enlace del menú (config/menu.php → 'contador').
 */
class Contadores
{
    public static function valor(string $nombre): int
    {
        return match ($nombre) {
            // Transferencias que esperan revisión de recepción
            'transferencias' => Pago::where('estado', Pago::PENDIENTE)->whereHas('transferencia')->count(),
            default          => 0,
        };
    }
}
