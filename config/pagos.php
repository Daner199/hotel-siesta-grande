<?php

/*
| Pasarela de pagos SIMULADA (requisito 17: no hay banco real).
|
| PAGOS_API_MODO=interno (por defecto): Laravel usa la lógica de la API por dentro. Nunca se cuelga.
| PAGOS_API_MODO=http: Laravel llama de verdad a POST /api/pagos/simular en PAGOS_API_URL
|                      (otro "php artisan serve --port=8001"), para mostrarlo en la defensa.
|
| La ruta POST /api/pagos/simular existe siempre (para probarla con Postman) y pide la cabecera
| X-Api-Clave igual a PAGOS_API_CLAVE.
*/

return [
    'modo'     => env('PAGOS_API_MODO', 'interno'),
    'url'      => env('PAGOS_API_URL', 'http://127.0.0.1:8001/api/pagos/simular'),
    'clave'    => env('PAGOS_API_CLAVE', ''),
    'segundos' => 5, // espera máxima en modo http

    // Tarjeta de prueba que el "banco" rechaza: la que termina en estos 4 dígitos
    'tarjeta_rechazada' => '0002',
];
