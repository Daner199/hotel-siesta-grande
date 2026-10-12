<?php

namespace App\Support;

use App\Models\Habitacion;
use App\Models\EstadoHabitacion;
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
        };

        $datos = array_merge($defecto, array_filter($request->only(array_keys($reglas)), fn ($v) => $v !== null && $v !== ''));

        Validator::make($datos, $reglas, [
            'date_format'    => 'Fecha no válida.',
            'after'          => 'La salida debe ser después de la llegada.',
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
}
