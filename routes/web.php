<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Banco\BancoSiestaController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ReciboVerificacionController;
use App\Http\Controllers\Auth\RegistroController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RecepcionistaController;
use App\Http\Controllers\Admin\AgenciaController;
use App\Http\Controllers\Admin\BloqueoController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\FotoHabitacionController;
use App\Http\Controllers\Admin\FotoTipoController;
use App\Http\Controllers\Admin\HabitacionController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\TarifaController;
use App\Http\Controllers\Admin\TipoHabitacionController;
use App\Http\Controllers\Agencia\ReservaController as ReservaAgencia;
use App\Http\Controllers\Cliente\ReservaController as ReservaCliente;
use App\Http\Controllers\Recepcion\ComisionController as ComisionRecepcion;
use App\Http\Controllers\Recepcion\ReservaController as ReservaRecepcion;

// Página pública del hotel (landing) y consulta de disponibilidad (máx. 30 consultas por minuto)
Route::get('/', [LandingController::class, 'index'])->name('inicio');
Route::get('/disponibilidad', [LandingController::class, 'disponibilidad'])
    ->middleware('throttle:30,1')
    ->name('disponibilidad');

// Solo para quien NO ha iniciado sesión
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/login', [LoginController::class, 'ingresar'])->name('login.ingresar');
    Route::get('/registro', [RegistroController::class, 'mostrar'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'registrar'])->name('registro.guardar');
});

// Cerrar sesión
Route::post('/logout', [LoginController::class, 'salir'])
    ->middleware('auth')
    ->name('logout');

// ---------- Administrador ----------
Route::middleware(['auth', 'rol:ADMINISTRADOR'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [PanelController::class, 'admin'])->name('inicio');

        // Recepcionistas
        Route::get('/recepcionistas', [RecepcionistaController::class, 'index'])
            ->name('recepcionistas.index');
        Route::get('/recepcionistas/crear', [RecepcionistaController::class, 'create'])
            ->name('recepcionistas.create');
        Route::post('/recepcionistas', [RecepcionistaController::class, 'store'])
            ->name('recepcionistas.store');
        Route::get('/recepcionistas/{recepcionista}/editar', [RecepcionistaController::class, 'edit'])
            ->whereNumber('recepcionista')->name('recepcionistas.edit');
        Route::put('/recepcionistas/{recepcionista}', [RecepcionistaController::class, 'update'])
            ->whereNumber('recepcionista')->name('recepcionistas.update');
        Route::patch('/recepcionistas/{recepcionista}/estado', [RecepcionistaController::class, 'cambiarEstado'])
            ->whereNumber('recepcionista')->name('recepcionistas.estado');

                    // Agencias
        Route::get('/agencias', [AgenciaController::class, 'index'])
            ->name('agencias.index');
        Route::get('/agencias/crear', [AgenciaController::class, 'create'])
            ->name('agencias.create');
        Route::post('/agencias', [AgenciaController::class, 'store'])
            ->name('agencias.store');
        Route::get('/agencias/{agencia}/editar', [AgenciaController::class, 'edit'])
            ->whereNumber('agencia')->name('agencias.edit');
        Route::put('/agencias/{agencia}', [AgenciaController::class, 'update'])
            ->whereNumber('agencia')->name('agencias.update');
        Route::patch('/agencias/{agencia}/estado', [AgenciaController::class, 'cambiarEstado'])
            ->whereNumber('agencia')->name('agencias.estado');

        // Clientes (solo lista y activar/desactivar)
        Route::get('/clientes', [ClienteController::class, 'index'])
            ->name('clientes.index');
        Route::patch('/clientes/{cliente}/estado', [ClienteController::class, 'cambiarEstado'])
            ->whereNumber('cliente')->name('clientes.estado');

        // Tipos de habitación
        Route::get('/tipos', [TipoHabitacionController::class, 'index'])
            ->name('tipos.index');
        Route::get('/tipos/crear', [TipoHabitacionController::class, 'create'])
            ->name('tipos.create');
        Route::post('/tipos', [TipoHabitacionController::class, 'store'])
            ->name('tipos.store');
        Route::get('/tipos/{tipo}/editar', [TipoHabitacionController::class, 'edit'])
            ->whereNumber('tipo')->name('tipos.edit');
        Route::put('/tipos/{tipo}', [TipoHabitacionController::class, 'update'])
            ->whereNumber('tipo')->name('tipos.update');
        Route::patch('/tipos/{tipo}/estado', [TipoHabitacionController::class, 'cambiarEstado'])
            ->whereNumber('tipo')->name('tipos.estado');
        Route::delete('/tipos/{tipo}', [TipoHabitacionController::class, 'destroy'])
            ->whereNumber('tipo')->name('tipos.destroy');

        // Tarifas de cada tipo (scopeBindings: la tarifa debe ser de ESE tipo, si no → 404)
        Route::get('/tipos/{tipo}/tarifas', [TarifaController::class, 'index'])
            ->whereNumber('tipo')->name('tipos.tarifas');
        Route::post('/tipos/{tipo}/tarifas', [TarifaController::class, 'store'])
            ->whereNumber('tipo')->name('tipos.tarifas.store');
        Route::patch('/tipos/{tipo}/tarifas/{tarifa}/anular', [TarifaController::class, 'anular'])
            ->whereNumber(['tipo', 'tarifa'])->scopeBindings()->name('tipos.tarifas.anular');

        // Fotos de cada tipo (scopeBindings: la foto debe ser de ESE tipo)
        Route::get('/tipos/{tipo}/fotos', [FotoTipoController::class, 'index'])
            ->whereNumber('tipo')->name('tipos.fotos');
        Route::post('/tipos/{tipo}/fotos', [FotoTipoController::class, 'store'])
            ->whereNumber('tipo')->name('tipos.fotos.store');
        Route::patch('/tipos/{tipo}/fotos/{foto}/principal', [FotoTipoController::class, 'principal'])
            ->whereNumber(['tipo', 'foto'])->scopeBindings()->name('tipos.fotos.principal');
        Route::patch('/tipos/{tipo}/fotos/{foto}/mover', [FotoTipoController::class, 'mover'])
            ->whereNumber(['tipo', 'foto'])->scopeBindings()->name('tipos.fotos.mover');
        Route::delete('/tipos/{tipo}/fotos/{foto}', [FotoTipoController::class, 'destroy'])
            ->whereNumber(['tipo', 'foto'])->scopeBindings()->name('tipos.fotos.destroy');

        // Habitaciones (no se borran: pasan a FUERA_SERVICIO)
        Route::get('/habitaciones', [HabitacionController::class, 'index'])
            ->name('habitaciones.index');
        Route::get('/habitaciones/crear', [HabitacionController::class, 'create'])
            ->name('habitaciones.create');
        Route::post('/habitaciones', [HabitacionController::class, 'store'])
            ->name('habitaciones.store');
        Route::get('/habitaciones/{habitacion}/editar', [HabitacionController::class, 'edit'])
            ->whereNumber('habitacion')->name('habitaciones.edit');
        Route::put('/habitaciones/{habitacion}', [HabitacionController::class, 'update'])
            ->whereNumber('habitacion')->name('habitaciones.update');
        Route::patch('/habitaciones/{habitacion}/estado', [HabitacionController::class, 'cambiarEstado'])
            ->whereNumber('habitacion')->name('habitaciones.estado');

        // Bloqueos por fechas de una habitación (mantenimiento temporal)
        Route::post('/habitaciones/{habitacion}/bloqueos', [BloqueoController::class, 'store'])
            ->whereNumber('habitacion')->name('habitaciones.bloqueos.store');
        Route::delete('/habitaciones/{habitacion}/bloqueos/{bloqueo}', [BloqueoController::class, 'destroy'])
            ->whereNumber(['habitacion', 'bloqueo'])->scopeBindings()->name('habitaciones.bloqueos.destroy');

        // Datos del hotel (una sola fila)
        Route::get('/hotel', [HotelController::class, 'edit'])->name('hotel.edit');
        Route::put('/hotel', [HotelController::class, 'update'])->name('hotel.update');

        // Fotos propias de cada habitación (opcionales)
        Route::post('/habitaciones/{habitacion}/fotos', [FotoHabitacionController::class, 'store'])
            ->whereNumber('habitacion')->name('habitaciones.fotos.store');
        Route::patch('/habitaciones/{habitacion}/fotos/{foto}/principal', [FotoHabitacionController::class, 'principal'])
            ->whereNumber(['habitacion', 'foto'])->scopeBindings()->name('habitaciones.fotos.principal');
        Route::patch('/habitaciones/{habitacion}/fotos/{foto}/mover', [FotoHabitacionController::class, 'mover'])
            ->whereNumber(['habitacion', 'foto'])->scopeBindings()->name('habitaciones.fotos.mover');
        Route::delete('/habitaciones/{habitacion}/fotos/{foto}', [FotoHabitacionController::class, 'destroy'])
            ->whereNumber(['habitacion', 'foto'])->scopeBindings()->name('habitaciones.fotos.destroy');
    });

Route::middleware(['auth', 'rol:RECEPCIONISTA'])->group(function () {
    Route::get('/recepcion', [PanelController::class, 'recepcion'])->name('recepcion.inicio');

    Route::get('/recepcion/pagos', [ReservaRecepcion::class, 'pagos'])->name('recepcion.pagos.index');
    Route::get('/recepcion/cobros/{cobro}', [ReservaRecepcion::class, 'cobro'])->whereNumber('cobro')->name('recepcion.cobros.show');
    Route::get('/recepcion/cobros/{cobro}/estado', [ReservaRecepcion::class, 'estadoCobro'])->whereNumber('cobro')->name('recepcion.cobros.estado');
    Route::patch('/recepcion/cobros/{cobro}/anular', [ReservaRecepcion::class, 'anularCobro'])->whereNumber('cobro')->name('recepcion.cobros.anular');
    Route::get('/recepcion/recibos/{recibo}', [ReservaRecepcion::class, 'recibo'])->whereNumber('recibo')->name('recepcion.recibos.show');
    Route::get('/recepcion/recibos/{recibo}/pdf', [ReservaRecepcion::class, 'reciboPdf'])->whereNumber('recibo')->name('recepcion.recibos.pdf');
    Route::get('/recepcion/comisiones', [ComisionRecepcion::class, 'index'])->name('recepcion.comisiones');

    // Reservas desde recepción (para cualquier persona, habitaciones exactas)
    Route::prefix('recepcion/reservas')->name('recepcion.reservas.')->controller(ReservaRecepcion::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/nueva', 'create')->name('create');
        Route::get('/cotizar', 'cotizar')->middleware('throttle:120,1')->name('cotizar');
        Route::post('/', 'store')->name('store');
        Route::get('/{reserva}', 'show')->whereNumber('reserva')->name('show');
        Route::get('/{reserva}/editar', 'edit')->whereNumber('reserva')->name('edit');
        Route::put('/{reserva}', 'update')->whereNumber('reserva')->name('update');
        Route::patch('/{reserva}/cancelar', 'cancelar')->whereNumber('reserva')->name('cancelar');
        Route::get('/{reserva}/pagar', 'formularioPago')->whereNumber('reserva')->name('pagar');
        Route::post('/{reserva}/pagar', 'pagar')->whereNumber('reserva')->middleware('throttle:20,1')->name('pagar.store');
    });
});

Route::middleware(['auth', 'rol:AGENCIA'])->group(function () {
    Route::get('/agencia', [PanelController::class, 'agencia'])->name('agencia.inicio');

    Route::get('/agencia/pagos', [ReservaAgencia::class, 'pagos'])->name('agencia.pagos.index');
    Route::get('/agencia/cobros/{cobro}', [ReservaAgencia::class, 'cobro'])->whereNumber('cobro')->name('agencia.cobros.show');
    Route::get('/agencia/cobros/{cobro}/estado', [ReservaAgencia::class, 'estadoCobro'])->whereNumber('cobro')->name('agencia.cobros.estado');
    Route::patch('/agencia/cobros/{cobro}/anular', [ReservaAgencia::class, 'anularCobro'])->whereNumber('cobro')->name('agencia.cobros.anular');
    Route::get('/agencia/recibos/{recibo}', [ReservaAgencia::class, 'recibo'])->whereNumber('recibo')->name('agencia.recibos.show');
    Route::get('/agencia/recibos/{recibo}/pdf', [ReservaAgencia::class, 'reciboPdf'])->whereNumber('recibo')->name('agencia.recibos.pdf');

    // Reservas de la agencia (para un titular, con 10 % de descuento)
    Route::prefix('agencia/reservas')->name('agencia.reservas.')->controller(ReservaAgencia::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/nueva', 'create')->name('create');
        Route::get('/cotizar', 'cotizar')->middleware('throttle:60,1')->name('cotizar');
        Route::post('/', 'store')->name('store');
        Route::get('/{reserva}', 'show')->whereNumber('reserva')->name('show');
        Route::patch('/{reserva}/cancelar', 'cancelar')->whereNumber('reserva')->name('cancelar');
        Route::get('/{reserva}/pagar', 'formularioPago')->whereNumber('reserva')->name('pagar');
        Route::post('/{reserva}/pagar', 'pagar')->whereNumber('reserva')->middleware('throttle:20,1')->name('pagar.store');
    });
});

Route::middleware(['auth', 'rol:CLIENTE'])->group(function () {
    Route::get('/cliente', [PanelController::class, 'cliente'])->name('cliente.inicio');

    Route::get('/cliente/pagos', [ReservaCliente::class, 'pagos'])->name('cliente.pagos.index');
    Route::get('/cliente/cobros/{cobro}', [ReservaCliente::class, 'cobro'])->whereNumber('cobro')->name('cliente.cobros.show');
    Route::get('/cliente/cobros/{cobro}/estado', [ReservaCliente::class, 'estadoCobro'])->whereNumber('cobro')->name('cliente.cobros.estado');
    Route::patch('/cliente/cobros/{cobro}/anular', [ReservaCliente::class, 'anularCobro'])->whereNumber('cobro')->name('cliente.cobros.anular');
    Route::get('/cliente/recibos/{recibo}', [ReservaCliente::class, 'recibo'])->whereNumber('recibo')->name('cliente.recibos.show');
    Route::get('/cliente/recibos/{recibo}/pdf', [ReservaCliente::class, 'reciboPdf'])->whereNumber('recibo')->name('cliente.recibos.pdf');

    // Reservas del cliente (para sí mismo)
    Route::prefix('cliente/reservas')->name('cliente.reservas.')->controller(ReservaCliente::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/nueva', 'create')->name('create');
        Route::get('/cotizar', 'cotizar')->middleware('throttle:60,1')->name('cotizar');
        Route::post('/', 'store')->name('store');
        Route::get('/{reserva}', 'show')->whereNumber('reserva')->name('show');
        Route::patch('/{reserva}/cancelar', 'cancelar')->whereNumber('reserva')->name('cancelar');
        Route::get('/{reserva}/pagar', 'formularioPago')->whereNumber('reserva')->name('pagar');
        Route::post('/{reserva}/pagar', 'pagar')->whereNumber('reserva')->middleware('throttle:20,1')->name('pagar.store');
    });
});

// Banco Siesta (banco simulado): la "app" que se abre en el celular al escanear el QR
Route::get('/banco/qr/{token}', [BancoSiestaController::class, 'mostrar'])
    ->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:60,1')->name('banco.qr');
Route::post('/banco/qr/{token}/pagar', [BancoSiestaController::class, 'pagar'])
    ->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:20,1')->name('banco.qr.pagar');

// Verificación pública de un recibo (el QR del recibo lleva aquí)
Route::get('/recibos/verificar/{token}', [ReciboVerificacionController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1')->name('recibos.verificar');

// "Reservar" desde la landing: lleva a "Nueva reserva" del panel de cada rol
// (sin sesión pasa por el login y vuelve aquí con las mismas fechas)
Route::get('/reservar', [PanelController::class, 'reservar'])->middleware('auth')->name('reservar');