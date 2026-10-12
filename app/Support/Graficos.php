<?php

namespace App\Support;

/**
 * Gráficos de barras en SVG para los reportes (Módulo 9).
 * Laravel los dibuja: el mismo SVG se ve en pantalla y dentro del PDF (sin librerías de JS).
 * Una sola serie → un solo color (selva); al pasar el mouse cada barra muestra su valor (<title>).
 */
class Graficos
{
    private const BARRA = '#1D3A2E';
    private const PISTA = '#ECEEE8';
    private const LINEA = '#DDE1DA';
    private const TINTA = '#17221C';
    private const GRIS  = '#5D6A63';
    private const FUENTE = 'Figtree, DejaVu Sans, sans-serif';

    /**
     * Barras horizontales con su valor al final (ej.: ocupación por tipo, 0–100 %).
     *
     * @param array<int, array{etiqueta: string, valor: float, texto: string}> $filas
     */
    public static function barrasHorizontales(array $filas, float $maximo): string
    {
        $ancho = 640;
        $alto  = 34;
        $x0    = 130;               // donde empieza la barra
        $largo = $ancho - $x0 - 80; // espacio para el valor a la derecha
        $total = max(1, count($filas)) * $alto;
        $maximo = max($maximo, 0.01);

        $svg = '';
        foreach (array_values($filas) as $i => $f) {
            $y = $i * $alto + 8;
            $w = round(max(0, min(1, $f['valor'] / $maximo)) * $largo, 1);
            $svg .= '<g><title>' . e($f['etiqueta'] . ': ' . $f['texto']) . '</title>'
                . '<text x="0" y="' . ($y + 13) . '" font-size="13" fill="' . self::TINTA . '">' . e($f['etiqueta']) . '</text>'
                . '<rect x="' . $x0 . '" y="' . $y . '" width="' . $largo . '" height="18" rx="4" fill="' . self::PISTA . '"/>'
                . ($w > 0 ? '<rect x="' . $x0 . '" y="' . $y . '" width="' . max($w, 4) . '" height="18" rx="4" fill="' . self::BARRA . '"/>' : '')
                . '<text x="' . ($x0 + $largo + 10) . '" y="' . ($y + 13) . '" font-size="13" font-weight="bold" fill="' . self::TINTA . '">' . e($f['texto']) . '</text>'
                . '</g>';
        }

        return self::envolver($ancho, $total, $svg);
    }

    /**
     * Barras verticales por día (ej.: ingresos del mes). Eje con 0, la mitad y el máximo.
     *
     * @param array<int, float> $valores [día => monto]
     * @param callable(float): string $formato
     */
    public static function barrasVerticales(array $valores, callable $formato): string
    {
        $ancho = 680;
        $alto  = 220;
        $x0    = 78;  // espacio para el eje
        $y0    = 190; // línea base
        $altoUtil = 170;
        $n     = max(1, count($valores));
        $paso  = ($ancho - $x0) / $n;
        $maximo = self::techo(max($valores ?: [0]));

        $svg = '';
        foreach ([0, .5, 1] as $parte) {
            $y = round($y0 - $altoUtil * $parte, 1);
            $svg .= '<line x1="' . $x0 . '" x2="' . $ancho . '" y1="' . $y . '" y2="' . $y . '" stroke="' . self::LINEA . '" stroke-width="1"/>'
                . '<text x="' . ($x0 - 8) . '" y="' . ($y + 4) . '" font-size="11" text-anchor="end" fill="' . self::GRIS . '">' . e($formato($maximo * $parte)) . '</text>';
        }

        $mayor = $valores ? array_search(max($valores), $valores, true) : null;
        $i = 0;
        foreach ($valores as $dia => $valor) {
            $x = round($x0 + $i * $paso + 1, 1);
            $h = $maximo > 0 ? round($valor / $maximo * $altoUtil, 1) : 0;
            $svg .= '<g><title>Día ' . $dia . ': ' . e($formato($valor)) . '</title>'
                . '<rect x="' . $x . '" y="' . ($y0 - $altoUtil) . '" width="' . round($paso - 2, 1) . '" height="' . $altoUtil . '" fill="transparent"/>';
            if ($h > 0) {
                $svg .= '<rect x="' . $x . '" y="' . ($y0 - max($h, 2)) . '" width="' . round($paso - 2, 1) . '" height="' . max($h, 2) . '" rx="2" fill="' . self::BARRA . '"/>';
            }
            $svg .= '</g>';
            // Días 1, 5, 10, 15… para no amontonar
            if ($dia === 1 || $dia % 5 === 0) {
                $svg .= '<text x="' . round($x + ($paso - 2) / 2, 1) . '" y="' . ($y0 + 16) . '" font-size="11" text-anchor="middle" fill="' . self::GRIS . '">' . $dia . '</text>';
            }
            // Solo el día más alto lleva su monto escrito
            if ($dia === $mayor && $valor > 0) {
                $svg .= '<text x="' . round($x + ($paso - 2) / 2, 1) . '" y="' . ($y0 - $h - 6) . '" font-size="11" font-weight="bold" text-anchor="middle" fill="' . self::TINTA . '">' . e($formato($valor)) . '</text>';
            }
            $i++;
        }

        return self::envolver($ancho, $alto + 8, $svg);
    }

    // Para el PDF: el SVG como imagen
    public static function dataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private static function envolver(int $ancho, int|float $alto, string $contenido): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $ancho . ' ' . $alto . '" width="' . $ancho . '" height="' . $alto
            . '" font-family="' . self::FUENTE . '" role="img">' . $contenido . '</svg>';
    }

    // Un máximo "redondo" para el eje: 1.730 → 2.000; 460 → 500
    private static function techo(float $valor): float
    {
        if ($valor <= 0) {
            return 0;
        }
        $base = 10 ** floor(log10($valor));
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($valor <= $m * $base) {
                return $m * $base;
            }
        }

        return 10 * $base;
    }
}
