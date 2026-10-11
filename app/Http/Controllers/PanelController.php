<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\EstadoReserva;
use App\Models\Reserva;
use App\Models\Usuario;
use App\Support\Disponibilidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PanelController extends Controller
{
    public function admin()
    {
        return view('panel.inicio', [
            'titulo'  => 'Administración',
            'resumen' => 'Gestiona el personal, las agencias y la información del hotel.',

            'estadisticas' => [
                [
                    'texto' => 'Recepcionistas activos',
                    'valor' => Usuario::where('rol', Usuario::RECEPCIONISTA)->where('activo', true)->count(),
                    'icono' => 'concierge-bell',
                    'color' => 'verde',
                ],
                [
                    'texto' => 'Agencias activas',
                    'valor' => Agencia::where('activa', true)->count(),
                    'icono' => 'building-2',
                    'color' => 'rosa',
                ],
                [
                    'texto' => 'Clientes registrados',
                    'valor' => Usuario::where('rol', Usuario::CLIENTE)->count(),
                    'icono' => 'users',
                    'color' => 'dorado',
                ],
                [
                    'texto' => 'Habitaciones',
                    'valor' => DB::table('habitacion')->count(),
                    'icono' => 'bed-double',
                    'color' => 'azul',
                ],
            ],

            'secciones' => [
                                ['nombre' => 'Recepcionistas', 'descripcion' => 'Crea y gestiona las cuentas del personal de recepción.', 'icono' => 'concierge-bell', 'paso' => 'Paso 1.4b', 'ruta' => 'admin.recepcionistas.index'],
                ['nombre' => 'Agencias', 'descripcion' => 'Registra agencias y a su persona de contacto.', 'icono' => 'building-2', 'paso' => 'Paso 1.4c', 'ruta' => 'admin.agencias.index'],
                ['nombre' => 'Clientes', 'descripcion' => 'Consulta los clientes registrados y activa o desactiva sus cuentas.', 'icono' => 'users', 'paso' => 'Paso 1.5', 'ruta' => 'admin.clientes.index'],
                ['nombre' => 'Datos del hotel', 'descripcion' => 'Fotos, contacto, redes, horarios y ubicación en el mapa.', 'icono' => 'hotel', 'paso' => 'Paso 2.5', 'ruta' => 'admin.hotel.edit'],
                ['nombre' => 'Tipos y tarifas', 'descripcion' => 'Capacidad de cada tipo y su historial de precios por noche.', 'icono' => 'tags', 'paso' => 'Paso 2.2', 'ruta' => 'admin.tipos.index'],
                ['nombre' => 'Habitaciones', 'descripcion' => 'Las ' . DB::table('habitacion')->count() . ' habitaciones: número, piso, tipo y estado.', 'icono' => 'bed-double', 'paso' => 'Paso 2.3', 'ruta' => 'admin.habitaciones.index'],
                ['nombre' => 'Promociones y beneficios', 'descripcion' => 'Paquetes con beneficios configurables y vigencia.', 'icono' => 'sparkles', 'paso' => 'Módulo 3'],
                ['nombre' => 'Salón de eventos', 'descripcion' => 'Capacidad, costo por hora y reservas del salón.', 'icono' => 'party-popper', 'paso' => 'Módulo 8'],
                ['nombre' => 'Reportes', 'descripcion' => 'Ocupación, NO_SHOW y reservas de agencias.', 'icono' => 'chart-column', 'paso' => 'Módulo 9'],
            ],
        ]);
    }

    public function recepcion()
    {
        \App\Support\NoShow::siCorresponde();

        return view('panel.inicio', [
            'titulo'       => 'Recepción',
            'resumen'      => 'Reservas, llegadas, salidas y cuentas de los huéspedes.',
            'estadisticas' => [
                [
                    'texto' => 'Reservas pendientes',
                    'valor' => Reserva::whereHas('estado', fn ($q) => $q->where('nombre', EstadoReserva::PENDIENTE))->count(),
                    'icono' => 'hourglass',
                    'color' => 'dorado',
                ],
                [
                    'texto' => 'Reservas confirmadas',
                    'valor' => Reserva::whereHas('estado', fn ($q) => $q->where('nombre', EstadoReserva::CONFIRMADA))->count(),
                    'icono' => 'calendar-check',
                    'color' => 'verde',
                ],
                [
                    'texto' => 'Llegan hoy',
                    'valor' => Reserva::whereDate('fecha_entrada', today())
                        ->whereHas('estado', fn ($q) => $q->whereIn('nombre', [EstadoReserva::PENDIENTE, EstadoReserva::CONFIRMADA]))
                        ->count(),
                    'icono' => 'plane-landing',
                    'color' => 'rosa',
                ],
                [
                    'texto' => 'Habitaciones libres hoy',
                    'valor' => Disponibilidad::habitacionesLibres(today(), today()->addDay())->count(),
                    'icono' => 'bed-double',
                    'color' => 'azul',
                ],
            ],
            'secciones'    => [
                ['nombre' => 'Reservas', 'descripcion' => 'Crea, consulta, edita y cancela reservas de huéspedes.', 'icono' => 'calendar-check', 'paso' => 'Paso 4.6', 'ruta' => 'recepcion.reservas.index'],
                ['nombre' => 'Pagos', 'descripcion' => 'Registra adelantos y pagos del saldo.', 'icono' => 'wallet', 'paso' => 'Paso 5.4', 'ruta' => 'recepcion.pagos.index'],
                ['nombre' => 'Mis comisiones', 'descripcion' => 'Bs 5 por cada reserva que confirmas.', 'icono' => 'coins', 'paso' => 'Paso 5.4', 'ruta' => 'recepcion.comisiones'],
                ['nombre' => 'Check-in y check-out', 'descripcion' => 'Llegadas, alojados, salidas y cuenta del huésped.', 'icono' => 'door-open', 'paso' => 'Paso 6.3', 'ruta' => 'recepcion.estadias.index'],
                ['nombre' => 'Consumos', 'descripcion' => 'Restaurante y frigobar cargados a la cuenta.', 'icono' => 'utensils', 'paso' => 'Módulo 7'],
                ['nombre' => 'Huéspedes alojados', 'descripcion' => 'Quién está en el hotel ahora mismo.', 'icono' => 'users', 'paso' => 'Módulo 9'],
            ],
        ]);
    }

    public function agencia()
    {
        return view('panel.inicio', [
            'titulo'       => 'Agencia',
            'resumen'      => 'Reserva habitaciones para tus clientes con ' . Disponibilidad::DESCUENTO_AGENCIA . ' % de descuento.',
            'estadisticas' => [],
            'secciones'    => [
                ['nombre' => 'Nueva reserva', 'descripcion' => 'Reserva para el titular y sus acompañantes.', 'icono' => 'calendar-plus', 'paso' => 'Paso 4.5', 'ruta' => 'agencia.reservas.create'],
                ['nombre' => 'Mis reservas', 'descripcion' => 'Estado de todas las reservas de la agencia.', 'icono' => 'calendar-check', 'paso' => 'Paso 4.5', 'ruta' => 'agencia.reservas.index'],
                ['nombre' => 'Pagos', 'descripcion' => 'Adelantos y saldos de cada reserva.', 'icono' => 'wallet', 'paso' => 'Paso 5.3', 'ruta' => 'agencia.pagos.index'],
            ],
        ]);
    }

    /**
     * GET /reservar?llegada=…&salida=…&tipo=… (botón "Reservar" de la landing).
     * Manda a "Nueva reserva" del rol con las mismas fechas y tipo.
     */
    public function reservar(Request $request)
    {
        $ruta = match ($request->user()->rol) {
            Usuario::CLIENTE       => 'cliente.reservas.create',
            Usuario::AGENCIA       => 'agencia.reservas.create',
            Usuario::RECEPCIONISTA => 'recepcion.reservas.create',
            default                => null,
        };

        if (! $ruta || ! \Illuminate\Support\Facades\Route::has($ruta)) {
            return redirect()->to($request->user()->rutaInicio());
        }

        return redirect()->route($ruta, $request->only(['llegada', 'salida', 'tipo']));
    }

    public function cliente()
    {
        return view('panel.inicio', [
            'titulo'       => 'Mi cuenta',
            'resumen'      => 'Consulta tus reservas, tus pagos y el estado de tu cuenta.',
            'estadisticas' => [],
            'secciones'    => [
                ['nombre' => 'Nueva reserva', 'descripcion' => 'Elige fechas y habitaciones: el total se calcula al instante.', 'icono' => 'calendar-plus', 'paso' => 'Paso 4.4', 'ruta' => 'cliente.reservas.create'],
                ['nombre' => 'Mis reservas', 'descripcion' => 'Estado y detalle de tus reservas.', 'icono' => 'calendar-check', 'paso' => 'Paso 4.4', 'ruta' => 'cliente.reservas.index'],
                ['nombre' => 'Mis pagos', 'descripcion' => 'Lo que pagaste y lo que falta pagar.', 'icono' => 'wallet', 'paso' => 'Paso 5.3', 'ruta' => 'cliente.pagos.index'],
            ],
        ]);
    }
}