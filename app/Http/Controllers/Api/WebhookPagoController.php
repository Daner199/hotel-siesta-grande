<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CobrosQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/pagos/webhook — aviso de Banco Siesta cuando se paga (o rechaza) un cobro con QR.
 * Cabecera X-Firma: HMAC-SHA256 del cuerpo con PAGOS_WEBHOOK_SECRETO (si no coincide → 401).
 */
class WebhookPagoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $resultado = CobrosQr::recibirWebhook($request->getContent(), $request->header('X-Firma'));

        return response()->json($resultado['cuerpo'], $resultado['estado']);
    }
}
