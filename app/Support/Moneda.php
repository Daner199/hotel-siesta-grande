<?php

namespace App\Support;

/**
 * El sistema trabaja solo en bolivianos.
 */
class Moneda
{
    // Formato boliviano a número: "1.250,50" → "1250.50" · "250,5" → "250.5" · "1.250" → "1250"
    // Devuelve el texto listo para validar como número (null si está vacío)
    public static function leer(mixed $texto): ?string
    {
        $valor = preg_replace('/\s+/', '', (string) $texto);

        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $valor)) {
            $valor = str_replace('.', '', $valor); // el punto era separador de miles
        }

        return $valor === '' ? null : $valor;
    }

    // 1250.5 → "Bs 1.250,50"
    public static function formato(int|float|string|null $monto): string
    {
        return 'Bs ' . number_format((float) $monto, 2, ',', '.');
    }
}
