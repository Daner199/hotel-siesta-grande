<?php

namespace App\Http\Controllers\Banco;

use App\Http\Controllers\Controller;
use App\Models\CobroQr;
use App\Models\Hotel;
use App\Support\BancoSiesta;
use App\Support\CobrosQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "App" de Banco Siesta (banco simulado) que se abre en el celular al escanear el QR.
 * Es pública: el celular no tiene sesión en el hotel. La protege el token (40 caracteres al azar)
 * y el vencimiento de 15 minutos.
 */
class BancoSiestaController extends Controller
{
    public function mostrar(string $token)
    {
        $cobro = CobroQr::where('token', $token)->with('reserva')->first();
        if ($cobro) {
            CobrosQr::actualizar($cobro);
        }

        return view('banco.qr', [
            'cobro' => $cobro,
            'hotel' => Hotel::datos(),
            'banco' => config('pagos.banco'),
        ]);
    }

    // POST /banco/qr/{token}/pagar  (resultado = aprobar | fondos)
    public function pagar(Request $request, string $token): JsonResponse
    {
        $cobro = CobroQr::where('token', $token)->first();
        if (! $cobro) {
            return response()->json(['ok' => false, 'error' => 'El cobro no existe.'], 404);
        }

        CobrosQr::actualizar($cobro);
        if ($cobro->estado !== CobroQr::PENDIENTE) {
            return response()->json(['ok' => false, 'error' => match ($cobro->estado) {
                CobroQr::VENCIDO => 'Este cobro venció. Pide al comercio un QR nuevo.',
                CobroQr::PAGADO  => 'Este cobro ya fue pagado.',
                default          => 'Este cobro ya no está disponible.',
            }], 409);
        }

        $respuesta = BancoSiesta::pagar($cobro, $request->input('resultado') === 'fondos');

        return response()->json($respuesta, $respuesta['ok'] ? 200 : 409);
    }
}
