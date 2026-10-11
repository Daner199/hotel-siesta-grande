<?php

use App\Support\NoShow;
use App\Support\Recibos;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Crea los recibos de los pagos aprobados que todavía no tienen (por orden de fecha)
Artisan::command('recibos:generar', function () {
    $cantidad = Recibos::generarFaltantes();
    $this->info($cantidad === 0 ? 'Todos los pagos ya tenían recibo.' : "Se crearon {$cantidad} recibos.");
})->purpose('Crear los recibos de los pagos aprobados que no tienen');

// Política 5: no-show (confirmadas que no llegaron) y pendientes vencidas sin pago
Artisan::command('reservas:no-show', function () {
    $r = NoShow::procesar();
    $this->info("No-show: {$r['no_show']} · pendientes liberadas: {$r['liberadas']}.");
})->purpose('Pasar a NO_SHOW las reservas que no llegaron y liberar las pendientes vencidas');

// Con "php artisan schedule:work" corre solo cada 5 minutos (además corre al abrir recepción)
Schedule::command('reservas:no-show')->everyFiveMinutes();
