<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\Usuario;
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
                ['nombre' => 'Habitaciones y tarifas', 'descripcion' => 'Las 60 habitaciones, sus tipos y sus precios por noche.', 'icono' => 'bed-double', 'paso' => 'Módulo 2'],
                ['nombre' => 'Promociones y beneficios', 'descripcion' => 'Paquetes con beneficios configurables y vigencia.', 'icono' => 'sparkles', 'paso' => 'Módulo 3'],
                ['nombre' => 'Salón de eventos', 'descripcion' => 'Capacidad, costo por hora y reservas del salón.', 'icono' => 'party-popper', 'paso' => 'Módulo 8'],
                ['nombre' => 'Reportes', 'descripcion' => 'Ocupación, NO_SHOW y reservas de agencias.', 'icono' => 'chart-column', 'paso' => 'Módulo 9'],
            ],
        ]);
    }

    public function recepcion()
    {
        return view('panel.inicio', [
            'titulo'       => 'Recepción',
            'resumen'      => 'Reservas, llegadas, salidas y cuentas de los huéspedes.',
            'estadisticas' => [],
            'secciones'    => [
                ['nombre' => 'Reservas', 'descripcion' => 'Crea y modifica reservas de huéspedes.', 'icono' => 'calendar-check', 'paso' => 'Módulo 4'],
                ['nombre' => 'Pagos', 'descripcion' => 'Registra adelantos y pagos del saldo.', 'icono' => 'wallet', 'paso' => 'Módulo 5'],
                ['nombre' => 'Check-in y check-out', 'descripcion' => 'Llegadas y salidas con cálculo de la cuenta.', 'icono' => 'door-open', 'paso' => 'Módulo 6'],
                ['nombre' => 'Consumos', 'descripcion' => 'Restaurante y frigobar cargados a la cuenta.', 'icono' => 'utensils', 'paso' => 'Módulo 7'],
                ['nombre' => 'Huéspedes alojados', 'descripcion' => 'Quién está en el hotel ahora mismo.', 'icono' => 'users', 'paso' => 'Módulo 9'],
            ],
        ]);
    }

    public function agencia()
    {
        return view('panel.inicio', [
            'titulo'       => 'Agencia',
            'resumen'      => 'Reserva habitaciones para tus clientes con 10% de descuento.',
            'estadisticas' => [],
            'secciones'    => [
                ['nombre' => 'Nueva reserva', 'descripcion' => 'Reserva para el titular y sus acompañantes.', 'icono' => 'calendar-plus', 'paso' => 'Módulo 4'],
                ['nombre' => 'Mis reservas', 'descripcion' => 'Estado de todas las reservas de la agencia.', 'icono' => 'calendar-check', 'paso' => 'Módulo 4'],
                ['nombre' => 'Pagos', 'descripcion' => 'Adelantos y saldos de cada reserva.', 'icono' => 'wallet', 'paso' => 'Módulo 5'],
            ],
        ]);
    }

    public function cliente()
    {
        return view('panel.inicio', [
            'titulo'       => 'Mi cuenta',
            'resumen'      => 'Consulta tus reservas, tus pagos y el estado de tu cuenta.',
            'estadisticas' => [],
            'secciones'    => [
                ['nombre' => 'Nueva reserva', 'descripcion' => 'Elige fechas, habitación y promoción.', 'icono' => 'calendar-plus', 'paso' => 'Módulo 4'],
                ['nombre' => 'Mis reservas', 'descripcion' => 'Estado y detalle de tus reservas.', 'icono' => 'calendar-check', 'paso' => 'Módulo 4'],
                ['nombre' => 'Mis pagos', 'descripcion' => 'Lo que pagaste y lo que falta pagar.', 'icono' => 'wallet', 'paso' => 'Módulo 5'],
            ],
        ]);
    }
}