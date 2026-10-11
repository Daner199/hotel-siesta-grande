<?php

use App\Http\Controllers\Api\PagoSimuladoController;
use App\Http\Controllers\Api\WebhookPagoController;
use Illuminate\Support\Facades\Route;

// Webhook de Banco Siesta: avisa cuando se paga un cobro con QR (firma HMAC en X-Firma)
Route::post('/pagos/webhook', WebhookPagoController::class)
    ->middleware('throttle:60,1')
    ->name('api.pagos.webhook');

// API simulada de pagos (requisito 17). Pide la cabecera X-Api-Clave; máx. 30 por minuto.
Route::post('/pagos/simular', PagoSimuladoController::class)
    ->middleware('throttle:30,1')
    ->name('api.pagos.simular');
