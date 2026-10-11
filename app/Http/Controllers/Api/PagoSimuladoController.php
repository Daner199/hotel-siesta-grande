<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetodoPago;
use App\Support\PasarelaPagos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * POST /api/pagos/simular  (requisito 17: API simulada de pagos, sin banco real)
 *
 * Cabecera: X-Api-Clave: <PAGOS_API_CLAVE>
 * Cuerpo:   {"monto": 300, "metodo": "QR"}  o  {"monto": 300, "metodo": "TARJETA", "ultimos4": "4242"}
 * Responde: {"estado": "APROBADO", "codigo": "PAY-123456"}  o  {"estado": "RECHAZADO", "motivo": "..."}
 * La tarjeta que termina en 0002 se rechaza siempre (para mostrarlo en la defensa).
 */
class PagoSimuladoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $clave = (string) config('pagos.clave');
        if ($clave === '' || ! hash_equals($clave, (string) $request->header('X-Api-Clave'))) {
            return response()->json(['mensaje' => 'Clave de API inválida o no configurada (PAGOS_API_CLAVE).'], 401);
        }

        $datos = $request->validate([
            'monto'    => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'metodo'   => ['required', Rule::in([MetodoPago::QR, MetodoPago::TARJETA])],
            'ultimos4' => ['required_if:metodo,' . MetodoPago::TARJETA, 'nullable', 'digits:4'],
        ], [
            'monto.*'              => 'El monto debe ser un número mayor que 0.',
            'metodo.*'             => 'El método debe ser QR o TARJETA (el efectivo no pasa por la pasarela).',
            'ultimos4.required_if' => 'Para TARJETA envía los 4 últimos dígitos (ultimos4).',
            'ultimos4.digits'      => 'ultimos4 debe tener exactamente 4 dígitos.',
        ]);

        return response()->json(
            PasarelaPagos::simular((float) $datos['monto'], $datos['metodo'], $datos['ultimos4'] ?? null)
        );
    }
}
