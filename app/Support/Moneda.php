<?php

namespace App\Support;

/**
 * El sistema trabaja solo en bolivianos.
 */
class Moneda
{
    // 1250.5 → "Bs 1.250,50"
    public static function formato(int|float|string|null $monto): string
    {
        return 'Bs ' . number_format((float) $monto, 2, ',', '.');
    }
}
