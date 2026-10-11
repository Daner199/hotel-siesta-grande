<?php

use App\Http\Controllers\Api\PagoSimuladoController;
use Illuminate\Support\Facades\Route;

// API simulada de pagos (requisito 17). Pide la cabecera X-Api-Clave; máx. 30 por minuto.
Route::post('/pagos/simular', PagoSimuladoController::class)
    ->middleware('throttle:30,1')
    ->name('api.pagos.simular');
