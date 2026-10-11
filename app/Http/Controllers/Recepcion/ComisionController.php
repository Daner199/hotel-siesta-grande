<?php

namespace App\Http\Controllers\Recepcion;

use App\Http\Controllers\Controller;
use App\Models\ComisionRecepcionista;

/**
 * "Mis comisiones": Bs 5 por cada reserva que el recepcionista confirmó (al registrar el adelanto).
 */
class ComisionController extends Controller
{
    public function index()
    {
        $mias = ComisionRecepcionista::where('recepcionista_id', auth()->id());

        $totales = (clone $mias)
            ->selectRaw('COUNT(*) AS cantidad, COALESCE(SUM(monto), 0) AS total')
            ->selectRaw("COALESCE(SUM(monto) FILTER (WHERE date_trunc('month', fecha_generacion) = date_trunc('month', CURRENT_DATE)), 0) AS mes")
            ->selectRaw('COALESCE(SUM(monto) FILTER (WHERE NOT pagada), 0) AS por_cobrar')
            ->first();

        return view('recepcion.comisiones', [
            'comisiones' => $mias->with('reserva')->orderByDesc('fecha_generacion')->paginate(15),
            'totales'    => $totales,
        ]);
    }
}
