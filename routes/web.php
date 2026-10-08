<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\Auth\RegistroController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RecepcionistaController;
use App\Http\Controllers\Admin\AgenciaController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\FotoHabitacionController;
use App\Http\Controllers\Admin\FotoTipoController;
use App\Http\Controllers\Admin\HabitacionController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\TarifaController;
use App\Http\Controllers\Admin\TipoHabitacionController;

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
});

Route::middleware(['auth', 'rol:AGENCIA'])->group(function () {
    Route::get('/agencia', [PanelController::class, 'agencia'])->name('agencia.inicio');
});

Route::middleware(['auth', 'rol:CLIENTE'])->group(function () {
    Route::get('/cliente', [PanelController::class, 'cliente'])->name('cliente.inicio');
});