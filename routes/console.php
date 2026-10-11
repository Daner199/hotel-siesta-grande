<?php

use App\Support\Recibos;
use Illuminate\Support\Facades\Artisan;

// Crea los recibos de los pagos aprobados que todavía no tienen (por orden de fecha)
Artisan::command('recibos:generar', function () {
    $cantidad = Recibos::generarFaltantes();
    $this->info($cantidad === 0 ? 'Todos los pagos ya tenían recibo.' : "Se crearon {$cantidad} recibos.");
})->purpose('Crear los recibos de los pagos aprobados que no tienen');
