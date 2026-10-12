<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Usuario;
use App\Support\Reportes;
use App\Support\ReporteExcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Módulo 9: reportes del administrador (los 6) y de recepción (los que usa en su trabajo).
 * Misma pantalla para los dos roles; ?exportar=pdf|excel descarga el mismo reporte.
 */
class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $primero = array_key_first(Reportes::permitidos($request->user()->rol));

        return redirect()->route($this->prefijo($request) . '.reportes.show', $primero);
    }

    // Recepción: "Huéspedes alojados" del menú
    public function alojados(Request $request)
    {
        return $this->show($request, 'alojados');
    }

    public function show(Request $request, string $reporte)
    {
        $permitidos = Reportes::permitidos($request->user()->rol);
        abort_unless(isset($permitidos[$reporte]), 404);

        // Filtros no válidos → el mismo reporte con sus valores por defecto y el mensaje
        try {
            $datos = Reportes::generar($reporte, $request);
        } catch (ValidationException $e) {
            return redirect()->route($this->prefijo($request) . '.reportes.show', $reporte)->withErrors($e->errors());
        }

        $archivo = 'reporte-' . $reporte . '-' . now()->format('Y-m-d');

        return match ($request->query('exportar')) {
            'excel' => ReporteExcel::descargar($datos, $archivo),
            'pdf'   => $this->pdf($datos, $archivo),
            default => view('reportes.show', [
                'reporte'    => $datos,
                'permitidos' => $permitidos,
                'prefijo'    => $this->prefijo($request),
                'opciones'   => Reportes::opciones($reporte),
            ]),
        };
    }

    private function pdf(array $datos, string $archivo)
    {
        $hotel = Hotel::datos();
        $logo  = null;
        $ruta  = $hotel['rutas']['logo'] ?? null;
        if ($ruta && extension_loaded('gd') && preg_match('/\.(jpe?g|png)$/i', $ruta) && Storage::disk('public')->exists($ruta)) {
            $logo = 'data:image/' . (str_ends_with(strtolower($ruta), 'png') ? 'png' : 'jpeg') . ';base64,'
                . base64_encode(Storage::disk('public')->get($ruta));
        }

        // Tablas anchas → hoja horizontal
        $ancha = collect($datos['tablas'])->max(fn ($t) => count($t['columnas'])) > 6;

        return Pdf::loadView('reportes.pdf', ['reporte' => $datos, 'hotel' => $hotel, 'logo' => $logo])
            ->setPaper('letter', $ancha ? 'landscape' : 'portrait')
            ->setOption('isFontSubsettingEnabled', true)
            ->download($archivo . '.pdf');
    }

    private function prefijo(Request $request): string
    {
        return $request->user()->rol === Usuario::ADMINISTRADOR ? 'admin' : 'recepcion';
    }
}
