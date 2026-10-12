<?php

namespace App\Http\Controllers;

use App\Models\EstadoHabitacion;
use App\Models\EstadoReserva;
use App\Models\Habitacion;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\TipoHabitacion;
use App\Models\Usuario;
use App\Support\Contadores;
use App\Support\Disponibilidad;
use App\Support\Estadias;
use App\Support\Moneda;
use App\Support\NoShow;
use App\Support\Reportes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Inicio de cada panel: los datos del día de cada rol (llegadas, salidas, ocupación,
 * pendientes de cobro, próximas reservas). Las vistas están en panel/inicio/<rol>.
 */
class PanelController extends Controller
{
    public function admin()
    {
        $ocupacion = $this->ocupacionEstaNoche();
        $inicioMes = today()->startOfMonth();

        $libresPorTipo = Disponibilidad::libresPorTipo(today(), today()->addDay());
        $tipos = TipoHabitacion::where('activo', true)
            ->withCount(['habitaciones as en_servicio' => fn ($q) => $q->whereHas('estado', fn ($e) => $e->where('nombre', EstadoHabitacion::ACTIVA))])
            ->orderBy('capacidad')->orderBy('nombre')
            ->get()
            ->filter(fn ($t) => $t->en_servicio > 0);

        return view('panel.inicio', [
            'titulo'   => 'Administración',
            'rol'      => 'admin',
            'acciones' => [['texto' => 'Reportes', 'ruta' => route('admin.reportes.index'), 'icono' => 'chart-column']],
            'cifras'   => [
                ['texto' => 'Ocupación esta noche', 'valor' => Reportes::celda($ocupacion['porcentaje'], 'porcentaje'),
                    'detalle' => $ocupacion['ocupadas'] . ' de ' . $ocupacion['enServicio'] . ' habitaciones'],
                ['texto' => 'Llegan hoy', 'valor' => Estadias::lista('llegadas')->count()],
                ['texto' => 'Salen hoy', 'valor' => Estadias::lista('salidas')->count()],
                ['texto' => 'Ingresos del mes', 'valor' => Moneda::formato(Pago::where('estado', Pago::APROBADO)
                    ->where('fecha_pago', '>=', $inicioMes)->sum('monto')),
                    'detalle' => ucfirst($inicioMes->locale('es')->isoFormat('MMMM'))],
                ['texto' => 'Sin adelanto', 'valor' => $this->conEstado(Reserva::query(), [EstadoReserva::PENDIENTE])->count(),
                    'detalle' => 'reservas pendientes'],
            ],
            // Ocupación de esta noche por tipo (barras simples en HTML)
            'porTipo' => $tipos->map(fn ($t) => [
                'tipo'       => $t->nombreVisible(),
                'ocupadas'   => $t->en_servicio - ($libresPorTipo[$t->id] ?? 0),
                'enServicio' => $t->en_servicio,
            ])->values(),
            'proximas' => $this->conEstado(Reserva::query(), [EstadoReserva::PENDIENTE, EstadoReserva::CONFIRMADA])
                ->whereBetween('fecha_entrada', [today()->toDateString(), today()->addDays(7)->toDateString()])
                ->with(['estado', 'agencia', 'habitaciones'])
                ->orderBy('fecha_entrada')->orderBy('id')
                ->limit(8)->get(),
            'reportes' => Reportes::permitidos(Usuario::ADMINISTRADOR),
        ]);
    }

    public function recepcion()
    {
        NoShow::siCorresponde();

        $ocupacion = $this->ocupacionEstaNoche();
        $llegadas  = Estadias::lista('llegadas')->with(['estado', 'habitaciones.habitacion'])->get();
        $salidas   = Estadias::lista('salidas')->with(['estado', 'habitaciones.habitacion', 'habitaciones'])->get();
        $alojados  = Estadias::lista('alojados')->withCount('habitaciones')->get();
        $transferencias = Contadores::valor('transferencias');

        $cifras = [
            ['texto' => 'Llegan hoy', 'valor' => $llegadas->count(), 'ruta' => route('recepcion.estadias.index', ['pestana' => 'llegadas'])],
            ['texto' => 'Salen hoy', 'valor' => $salidas->count(), 'ruta' => route('recepcion.estadias.index', ['pestana' => 'salidas'])],
            ['texto' => 'Alojados', 'valor' => $alojados->sum('habitaciones_count'),
                'detalle' => $alojados->sum('cantidad_personas') . ($alojados->sum('cantidad_personas') === 1 ? ' huésped' : ' huéspedes'), 'ruta' => route('recepcion.alojados')],
            ['texto' => 'Libres esta noche', 'valor' => $ocupacion['enServicio'] - $ocupacion['ocupadas'],
                'detalle' => 'de ' . $ocupacion['enServicio'] . ' habitaciones'],
        ];
        if ($transferencias > 0) {
            $cifras[] = ['texto' => 'Transferencias', 'valor' => $transferencias, 'detalle' => 'por revisar',
                'ruta' => route('recepcion.transferencias.index'), 'alerta' => true];
        }

        return view('panel.inicio', [
            'titulo'   => 'Recepción',
            'rol'      => 'recepcion',
            'acciones' => [
                ['texto' => 'Check-in / Check-out', 'ruta' => route('recepcion.estadias.index'), 'icono' => 'door-open'],
                ['texto' => 'Nueva reserva', 'ruta' => route('recepcion.reservas.create'), 'icono' => 'calendar-plus', 'principal' => true],
            ],
            'cifras'    => $cifras,
            'llegadas'  => $llegadas,
            'salidas'   => $salidas,
            // Pendientes de pago: las que llegan antes primero
            'pendientes' => $this->conEstado(Reserva::query(), [EstadoReserva::PENDIENTE])
                ->with(['estado', 'habitaciones'])
                ->orderBy('fecha_entrada')->orderBy('id')
                ->limit(6)->get(),
        ]);
    }

    public function agencia(Request $request)
    {
        $propias = fn () => Reserva::where('agencia_id', $request->user()->agencia->id);

        return view('panel.inicio', $this->datosCuenta($propias, 'agencia') + [
            'titulo' => $request->user()->agencia->nombre,
            'rol'    => 'agencia',
            'acciones' => [['texto' => 'Nueva reserva', 'ruta' => route('agencia.reservas.create'), 'icono' => 'calendar-plus', 'principal' => true]],
        ]);
    }

    public function cliente(Request $request)
    {
        $propias = fn () => Reserva::where('cliente_id', $request->user()->id);

        return view('panel.inicio', $this->datosCuenta($propias, 'cliente') + [
            'titulo' => 'Mi cuenta',
            'rol'    => 'cliente',
            'acciones' => [['texto' => 'Nueva reserva', 'ruta' => route('cliente.reservas.create'), 'icono' => 'calendar-plus', 'principal' => true]],
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

    // Cliente y agencia: próximas reservas, lo que falta pagar y la próxima estadía
    private function datosCuenta(\Closure $propias, string $prefijo): array
    {
        $activas = $this->conEstado($propias(), EstadoReserva::OCUPAN)
            ->with(['estado', 'habitaciones.habitacion.tipo'])
            ->orderBy('fecha_entrada')->orderBy('id')
            ->get();

        $porPagar = round($activas->sum(fn ($r) => $r->saldo()), 2);
        $inicioMes = today()->startOfMonth();

        $cifras = [
            ['texto' => 'Reservas activas', 'valor' => $activas->count(), 'ruta' => route($prefijo . '.reservas.index')],
            ['texto' => 'Llegan esta semana', 'valor' => $activas->filter(fn ($r) => $r->fecha_entrada->between(today(), today()->addDays(7)))->count()],
            ['texto' => 'Por pagar', 'valor' => Moneda::formato($porPagar), 'alerta' => $porPagar > 0],
        ];
        $cifras[] = $prefijo === 'agencia'
            ? ['texto' => 'Reservado este mes', 'valor' => Moneda::formato(
                $this->conEstado($propias(), [EstadoReserva::CANCELADA], false)
                    ->where('fecha_creacion', '>=', $inicioMes)->with('habitaciones')->get()->sum(fn ($r) => $r->total())),
                'detalle' => ucfirst($inicioMes->locale('es')->isoFormat('MMMM'))]
            : ['texto' => 'Estadías realizadas', 'valor' => $this->conEstado($propias(), [EstadoReserva::CHECK_OUT])->count()];

        return [
            'cifras'   => $cifras,
            'prefijo'  => $prefijo,
            'proxima'  => $activas->first(),
            'activas'  => $activas->take(8),
        ];
    }

    // Habitaciones en servicio y cuántas están ocupadas esta noche (reservas o bloqueos)
    private function ocupacionEstaNoche(): array
    {
        $enServicio = Habitacion::whereHas('estado', fn ($q) => $q->where('nombre', EstadoHabitacion::ACTIVA))
            ->whereHas('tipo', fn ($q) => $q->where('activo', true))
            ->count();
        $ocupadas = $enServicio - Disponibilidad::habitacionesLibres(today(), today()->addDay())->count();

        return [
            'enServicio' => $enServicio,
            'ocupadas'   => $ocupadas,
            'porcentaje' => $enServicio ? $ocupadas / $enServicio * 100 : 0,
        ];
    }

    private function conEstado(Builder $consulta, array $estados, bool $incluir = true): Builder
    {
        return $consulta->whereHas('estado', fn ($q) => $incluir ? $q->whereIn('nombre', $estados) : $q->whereNotIn('nombre', $estados));
    }
}
