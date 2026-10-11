<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Recibo;

/**
 * GET /recibos/verificar/{token}: página pública a la que lleva el QR del recibo.
 * Muestra solo lo necesario para comprobar que el recibo es auténtico.
 */
class ReciboVerificacionController extends Controller
{
    public function show(string $token)
    {
        $recibo = Recibo::where('token_verificacion', $token)
            ->with(['pago.reserva', 'pago.metodo'])
            ->first();

        return view('recibos.verificar', [
            'recibo' => $recibo,
            'hotel'  => Hotel::datos(),
        ]);
    }
}
