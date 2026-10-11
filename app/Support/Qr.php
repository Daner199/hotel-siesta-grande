<?php

namespace App\Support;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;

/**
 * Códigos QR en SVG (no necesitan GD). Los usan el cobro con QR y el recibo.
 */
class Qr
{
    // <svg>…</svg> para poner directo en la página
    public static function svg(string $texto): string
    {
        return (new QRCode(self::opciones()))->render($texto);
    }

    // data:image/svg+xml;base64,… para el PDF (dompdf lo lee como imagen)
    public static function dataUri(string $texto): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode(self::svg($texto));
    }

    private static function opciones(): QROptions
    {
        return new QROptions([
            'outputInterface'  => QRMarkupSVG::class,
            'outputBase64'     => false,
            'svgAddXmlHeader'  => false,
            'drawLightModules' => false,
            'connectPaths'     => true,
            'eccLevel'         => 'M',
            'addQuietzone'     => true,
            'quietzoneSize'    => 2,
        ]);
    }
}
