<?php

namespace App\Support;

use App\Models\Habitacion;
use App\Models\Agencia;
use App\Models\EstadoHabitacion;
use App\Models\EstadoReserva;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\TipoHabitacion;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Módulo 9: reportes. Todos devuelven la misma estructura, así una sola vista,
 * un solo PDF y un solo Excel sirven para todos:
 *
 *   titulo, periodo (los filtros en texto), cifras [[texto, valor]], graficos [[titulo, svg]],
 *   tablas [[titulo, columnas [[texto, tipo]], filas [[valor, …]], pie [valor, …]|null, vacio]], nota
 *
 * Tipos de columna: texto, entero, moneda, porcentaje (0–100), fecha, fechahora.
 * Las filas llevan el valor crudo (número o Carbon): se formatea al mostrar (celda()),
 * y en Excel queda como número de verdad.
 */
class Reportes
{
    private const AMBOS = [Usuario::ADMINISTRADOR, Usuario::RECEPCIONISTA];

    // clave de la URL => título y quién lo ve (recepción solo los que usa en su trabajo)
    public const LISTA = [
        'disponibles' => ['titulo' => 'Habitaciones disponibles', 'roles' => self::AMBOS],
        'agencias'    => ['titulo' => 'Reservas de agencias', 'roles' => [Usuario::ADMINISTRADOR]],
        'no-show'     => ['titulo' => 'No-show', 'roles' => self::AMBOS],
    ];

    /** @return array<string, string> [clave => título] que puede ver ese rol */
    public static function permitidos(string $rol): array
    {
        return collect(self::LISTA)
            ->filter(fn ($r) => in_array($rol, $r['roles'], true))
            ->map(fn ($r) => $r['titulo'])
            ->all();
    }

    // Filtros validados (con valores por defecto) y el reporte armado
    public static function generar(string $clave, Request $request): array
    {
        $metodo = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $clave))));
        $filtros = self::filtros($clave, $request);

        return ['clave' => $clave, 'filtros' => $filtros] + self::$metodo($filtros);
    }

    // Listas para los <select> de los filtros
    public static function opciones(string $clave): array
    {
        return match ($clave) {
            'disponibles' => ['tipos' => TipoHabitacion::where('activo', true)->orderBy('capacidad')->orderBy('nombre')->get()],
            'agencias'    => [
                'agencias' => Agencia::orderBy('nombre')->get(['id', 'nombre']),
                'estados'  => EstadoReserva::orderBy('id')->get(),
            ],
            default       => [],
        };
    }

    // Valor de una celda listo para leer: "Bs 1.250,00", "85,0 %", "12/10/2026"
    public static function celda(mixed $valor, string $tipo): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        return match ($tipo) {
            'moneda'     => Moneda::formato($valor),
            'entero'     => number_format((float) $valor, 0, ',', '.'),
            'porcentaje' => number_format((float) $valor, 1, ',', '.') . ' %',
            'fecha'      => Carbon::parse($valor)->format('d/m/Y'),
            'fechahora'  => Carbon::parse($valor)->format('d/m/Y H:i'),
            default      => (string) $valor,
        };
    }

    // Columnas numéricas: alineadas a la derecha
    public static function esNumero(string $tipo): bool
    {
        return in_array($tipo, ['moneda', 'entero', 'porcentaje'], true);
    }

    // ---------------------------------------------------------------------------------------------
    // Filtros
    // ---------------------------------------------------------------------------------------------

    private static function filtros(string $clave, Request $request): array
    {
        $hoy = today();

        [$reglas, $defecto] = match ($clave) {
            'disponibles' => [[
                'llegada' => ['date_format:Y-m-d'],
                'salida'  => ['date_format:Y-m-d', 'after:llegada'],
                'tipo'    => ['nullable', 'integer'],
            ], ['llegada' => $hoy->toDateString(), 'salida' => $hoy->copy()->addDay()->toDateString(), 'tipo' => null]],
            'agencias' => [[
                'desde'   => ['date_format:Y-m-d'],
                'hasta'   => ['date_format:Y-m-d', 'after_or_equal:desde'],
                'agencia' => ['nullable', 'integer'],
                'estado'  => ['nullable', 'string', 'max:30'],
            ], ['desde' => $hoy->copy()->startOfMonth()->toDateString(), 'hasta' => $hoy->copy()->endOfMonth()->toDateString(),
                'agencia' => null, 'estado' => null]],
            'no-show' => [[
                'desde' => ['date_format:Y-m-d'],
                'hasta' => ['date_format:Y-m-d', 'after_or_equal:desde'],
            ], ['desde' => $hoy->copy()->startOfMonth()->toDateString(), 'hasta' => $hoy->toDateString()]],
        };

        $datos = array_merge($defecto, array_filter($request->only(array_keys($reglas)), fn ($v) => $v !== null && $v !== ''));

        Validator::make($datos, $reglas, [
            'date_format'    => 'Fecha no válida.',
            'after'          => 'La salida debe ser después de la llegada.',
            'after_or_equal' => 'La fecha "hasta" no puede ser antes de "desde".',
            'max'            => 'Valor no válido.',
            'string'         => 'Valor no válido.',
            'integer'        => 'Valor no válido.',
        ])->validate();

        if ($clave === 'disponibles'
            && Carbon::parse($datos['llegada'])->diffInDays(Carbon::parse($datos['salida'])) > Disponibilidad::MAX_NOCHES) {
            throw ValidationException::withMessages(['salida' => 'Máximo ' . Disponibilidad::MAX_NOCHES . ' noches.']);
        }

        return $datos;
    }

    // ---------------------------------------------------------------------------------------------
    // 1. Habitaciones disponibles entre dos fechas, por tipo
    // ---------------------------------------------------------------------------------------------

    private static function disponibles(array $f): array
    {
        $llegada = Carbon::parse($f['llegada']);
        $salida  = Carbon::parse($f['salida']);
        $tipoId  = $f['tipo'] ? (int) $f['tipo'] : null;

        $libres = Disponibilidad::habitacionesLibres($llegada, $salida)
            ->when($tipoId, fn ($q) => $q->where('tipo_habitacion_id', $tipoId))
            ->with('tipo')
            ->orderByRaw('piso, numero')
            ->get();

        $tipos = TipoHabitacion::where('activo', true)
            ->when($tipoId, fn ($q) => $q->where('id', $tipoId))
            ->withCount(['habitaciones as activas' => fn ($q) => $q->whereHas('estado', fn ($e) => $e->where('nombre', EstadoHabitacion::ACTIVA))])
            ->orderBy('capacidad')->orderBy('nombre')
            ->get();

        $libresPorTipo = $libres->countBy('tipo_habitacion_id');
        $noches = (int) $llegada->diffInDays($salida);

        $porTipo = $tipos->map(fn ($t) => [
            $t->nombreVisible(),
            $t->capacidad,
            $t->activas,
            $libresPorTipo[$t->id] ?? 0,
            $t->activas - ($libresPorTipo[$t->id] ?? 0),
            $t->tarifaVigente($llegada)?->precio_noche,
        ])->all();

        $totalActivas = $tipos->sum('activas');
        $tipoNombre = $tipoId ? ($tipos->first()?->nombreVisible() ?? '—') : 'Todos los tipos';

        return [
            'titulo'  => self::LISTA['disponibles']['titulo'],
            'periodo' => 'Del ' . $llegada->format('d/m/Y') . ' al ' . $salida->format('d/m/Y')
                . ' · ' . $noches . ($noches === 1 ? ' noche' : ' noches') . ' · ' . $tipoNombre,
            'cifras'  => [
                ['texto' => 'Libres', 'valor' => (string) $libres->count()],
                ['texto' => 'Ocupadas o bloqueadas', 'valor' => (string) ($totalActivas - $libres->count())],
                ['texto' => 'Habitaciones en servicio', 'valor' => (string) $totalActivas],
            ],
            'graficos' => [],
            'tablas'  => [
                [
                    'titulo'   => 'Por tipo',
                    'columnas' => [
                        ['texto' => 'Tipo', 'tipo' => 'texto'],
                        ['texto' => 'Capacidad', 'tipo' => 'entero'],
                        ['texto' => 'En servicio', 'tipo' => 'entero'],
                        ['texto' => 'Libres', 'tipo' => 'entero'],
                        ['texto' => 'Ocupadas', 'tipo' => 'entero'],
                        ['texto' => 'Tarifa por noche', 'tipo' => 'moneda'],
                    ],
                    'filas' => $porTipo,
                    'pie'   => ['Total', null, $totalActivas, $libres->count(), $totalActivas - $libres->count(), null],
                    'vacio' => 'No hay tipos activos.',
                ],
                [
                    'titulo'   => 'Habitaciones libres',
                    'columnas' => [
                        ['texto' => 'Tipo', 'tipo' => 'texto'],
                        ['texto' => 'Libres', 'tipo' => 'entero'],
                        ['texto' => 'Números', 'tipo' => 'texto'],
                    ],
                    'filas' => $libres->groupBy(fn ($h) => $h->tipo->nombreVisible())
                        ->map(fn ($g, $tipo) => [$tipo, $g->count(), $g->pluck('numero')->join(', ')])
                        ->values()->all(),
                    'pie'   => null,
                    'vacio' => 'No hay habitaciones libres en esas fechas.',
                ],
            ],
            'nota' => null,
        ];
    }

    // ---------------------------------------------------------------------------------------------
    // 2. Reservas de agencias entre fechas (por fecha de entrada)
    // ---------------------------------------------------------------------------------------------

    private static function agencias(array $f): array
    {
        $desde = Carbon::parse($f['desde']);
        $hasta = Carbon::parse($f['hasta']);

        $reservas = self::conPagado(Reserva::query())
            ->whereNotNull('agencia_id')
            ->whereBetween('fecha_entrada', [$desde->toDateString(), $hasta->toDateString()])
            ->when($f['agencia'], fn ($q) => $q->where('agencia_id', (int) $f['agencia']))
            ->when($f['estado'], fn ($q) => $q->whereHas('estado', fn ($e) => $e->where('nombre', $f['estado'])))
            ->with(['agencia', 'estado', 'habitaciones'])
            ->orderBy('fecha_entrada')->orderBy('id')
            ->get();

        // El importe no cuenta las canceladas (no se cobran)
        $cancelada = fn ($r) => $r->esEstado(EstadoReserva::CANCELADA);
        $importe   = fn ($r) => $cancelada($r) ? 0 : $r->total();

        $porAgencia = $reservas->groupBy(fn ($r) => $r->agencia->nombre)->map(fn ($g, $nombre) => [
            $nombre,
            $g->count(),
            $g->filter($cancelada)->count(),
            $g->reject($cancelada)->sum(fn ($r) => $r->noches() * $r->habitaciones->count()),
            round($g->sum($importe), 2),
            round($g->sum(fn ($r) => (float) $r->pagado), 2),
        ])->sortByDesc(4)->values();

        $totalImporte = round($reservas->sum($importe), 2);
        $totalPagado  = round($reservas->sum(fn ($r) => (float) $r->pagado), 2);

        $filtroTexto = collect([
            $f['agencia'] ? (Agencia::find($f['agencia'])?->nombre ?? '—') : 'Todas las agencias',
            $f['estado'] ? (EstadoReserva::where('nombre', $f['estado'])->first()?->etiqueta() ?? $f['estado']) : null,
        ])->filter()->join(' · ');

        return [
            'titulo'  => self::LISTA['agencias']['titulo'],
            'periodo' => 'Entrada del ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y') . ' · ' . $filtroTexto,
            'cifras'  => [
                ['texto' => 'Reservas', 'valor' => (string) $reservas->count()],
                ['texto' => 'Agencias', 'valor' => (string) $porAgencia->count()],
                ['texto' => 'Importe (sin canceladas)', 'valor' => Moneda::formato($totalImporte)],
                ['texto' => 'Pagado', 'valor' => Moneda::formato($totalPagado)],
            ],
            'graficos' => [],
            'tablas'  => [
                [
                    'titulo'   => 'Por agencia',
                    'columnas' => [
                        ['texto' => 'Agencia', 'tipo' => 'texto'],
                        ['texto' => 'Reservas', 'tipo' => 'entero'],
                        ['texto' => 'Canceladas', 'tipo' => 'entero'],
                        ['texto' => 'Noches-habitación', 'tipo' => 'entero'],
                        ['texto' => 'Importe', 'tipo' => 'moneda'],
                        ['texto' => 'Pagado', 'tipo' => 'moneda'],
                    ],
                    'filas' => $porAgencia->all(),
                    'pie'   => $porAgencia->count() > 1
                        ? ['Total', $reservas->count(), $porAgencia->sum(2), $porAgencia->sum(3), $totalImporte, $totalPagado]
                        : null,
                    'vacio' => 'Sin reservas de agencias en esas fechas.',
                ],
                [
                    'titulo'   => 'Reservas',
                    'columnas' => [
                        ['texto' => 'Código', 'tipo' => 'texto'],
                        ['texto' => 'Agencia', 'tipo' => 'texto'],
                        ['texto' => 'Titular', 'tipo' => 'texto'],
                        ['texto' => 'Entrada', 'tipo' => 'fecha'],
                        ['texto' => 'Salida', 'tipo' => 'fecha'],
                        ['texto' => 'Hab.', 'tipo' => 'entero'],
                        ['texto' => 'Estado', 'tipo' => 'texto'],
                        ['texto' => 'Importe', 'tipo' => 'moneda'],
                        ['texto' => 'Pagado', 'tipo' => 'moneda'],
                    ],
                    'filas' => $reservas->map(fn ($r) => [
                        $r->codigo, $r->agencia->nombre, $r->nombre_titular, $r->fecha_entrada, $r->fecha_salida,
                        $r->habitaciones->count(), $r->estado->etiqueta(), $importe($r), (float) $r->pagado,
                    ])->all(),
                    'pie'   => $reservas->isNotEmpty() ? ['Total', null, null, null, null, null, null, $totalImporte, $totalPagado] : null,
                    'vacio' => 'Sin reservas.',
                ],
            ],
            'nota' => 'Importes con el ' . Disponibilidad::DESCUENTO_AGENCIA . ' % de descuento de agencia ya aplicado.',
        ];
    }

    // ---------------------------------------------------------------------------------------------
    // 3. Reservas con no-show (por fecha de llegada)
    // ---------------------------------------------------------------------------------------------

    private static function noShow(array $f): array
    {
        $desde = Carbon::parse($f['desde']);
        $hasta = Carbon::parse($f['hasta']);
        $rango = [$desde->toDateString(), $hasta->toDateString()];

        $reservas = self::conPagado(Reserva::query())
            ->whereHas('estado', fn ($e) => $e->where('nombre', EstadoReserva::NO_SHOW))
            ->whereBetween('fecha_entrada', $rango)
            ->with(['agencia', 'creador', 'habitaciones.habitacion'])
            ->orderBy('fecha_entrada')->orderBy('id')
            ->get();

        // Llegadas esperadas en el período (todas menos las canceladas): base de la tasa
        $esperadas = Reserva::whereBetween('fecha_entrada', $rango)
            ->whereHas('estado', fn ($e) => $e->where('nombre', '!=', EstadoReserva::CANCELADA))
            ->count();

        $retenido = round($reservas->sum(fn ($r) => (float) $r->pagado), 2);

        return [
            'titulo'  => self::LISTA['no-show']['titulo'],
            'periodo' => 'Llegada del ' . $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y'),
            'cifras'  => [
                ['texto' => 'Reservas no-show', 'valor' => (string) $reservas->count()],
                ['texto' => 'Llegadas esperadas', 'valor' => (string) $esperadas],
                ['texto' => 'Tasa de no-show', 'valor' => self::celda($esperadas ? $reservas->count() / $esperadas * 100 : 0, 'porcentaje')],
                ['texto' => 'Pagado retenido', 'valor' => Moneda::formato($retenido)],
            ],
            'graficos' => [],
            'tablas'  => [[
                'titulo'   => 'Reservas',
                'columnas' => [
                    ['texto' => 'Código', 'tipo' => 'texto'],
                    ['texto' => 'Titular', 'tipo' => 'texto'],
                    ['texto' => 'Canal', 'tipo' => 'texto'],
                    ['texto' => 'Llegada', 'tipo' => 'fecha'],
                    ['texto' => 'Noches', 'tipo' => 'entero'],
                    ['texto' => 'Habitaciones', 'tipo' => 'texto'],
                    ['texto' => 'Importe', 'tipo' => 'moneda'],
                    ['texto' => 'Pagado retenido', 'tipo' => 'moneda'],
                ],
                'filas' => $reservas->map(fn ($r) => [
                    $r->codigo, $r->nombre_titular, self::canal($r), $r->fecha_entrada, $r->noches(),
                    $r->habitaciones->map(fn ($rh) => $rh->habitacion->numero)->join(', '), $r->total(), (float) $r->pagado,
                ])->all(),
                'pie'   => $reservas->isNotEmpty()
                    ? ['Total', null, null, null, $reservas->sum(fn ($r) => $r->noches()), null,
                        round($reservas->sum(fn ($r) => $r->total()), 2), $retenido]
                    : null,
                'vacio' => 'Sin no-show en esas fechas.',
            ]],
            'nota' => null,
        ];
    }

    // ---------------------------------------------------------------------------------------------
    // Ayudas
    // ---------------------------------------------------------------------------------------------

    // Agrega "pagado" (suma de pagos aprobados) sin una consulta por reserva
    private static function conPagado($consulta)
    {
        return $consulta->select('reserva.*')->addSelect(['pagado' => Pago::selectRaw('COALESCE(SUM(monto), 0)')
            ->whereColumn('pago.reserva_id', 'reserva.id')
            ->where('estado', Pago::APROBADO)]);
    }

    // Quién hizo la reserva: la agencia, recepción o el cliente en línea
    private static function canal(Reserva $r): string
    {
        if ($r->agencia) {
            return $r->agencia->nombre;
        }

        return $r->creador?->rol === Usuario::RECEPCIONISTA ? 'Recepción' : 'En línea';
    }
}
