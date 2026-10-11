<?php

namespace App\Support;

use App\Models\ComisionRecepcionista;
use App\Models\EstadoReserva;
use App\Models\Habitacion;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\ReservaHabitacion;
use App\Models\TipoHabitacion;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio de las reservas (compartidas por cliente, agencia y recepción).
 * Los controladores validan el formulario y deciden de quién es la reserva;
 * aquí se revisa disponibilidad, se calcula el precio y se cambia el estado.
 *
 * Los errores de negocio se lanzan como ValidationException: vuelven al formulario con el mensaje.
 */
class Reservas
{
    /**
     * Elige habitaciones libres de un tipo (números más bajos primero).
     * Lo usan cliente y agencia, que eligen tipo + cantidad; recepción elige los números.
     */
    public static function asignar(int $tipoId, int $cantidad, Carbon $llegada, Carbon $salida): Collection
    {
        $libres = Disponibilidad::habitacionesLibres($llegada, $salida)
            ->where('tipo_habitacion_id', $tipoId)
            ->orderBy('numero')
            ->limit($cantidad)
            ->pluck('id');

        if ($libres->count() < $cantidad) {
            $quedan = $libres->count();
            $tipo   = TipoHabitacion::find($tipoId)?->nombreVisible() ?? 'de ese tipo';
            throw ValidationException::withMessages([
                'habitaciones' => match ($quedan) {
                    0       => "No quedan habitaciones {$tipo} libres en esas fechas.",
                    1       => "Solo queda 1 habitación {$tipo} libre en esas fechas.",
                    default => "Solo quedan {$quedan} habitaciones {$tipo} libres en esas fechas.",
                },
            ]);
        }

        return $libres;
    }

    /**
     * Se cobra por HABITACIÓN y por noche (nunca por persona). Para que se lea claro:
     * ['Doble' => 1, 'Simple' => 2], 3 → ["1 habitación Doble × 3 noches", "2 habitaciones Simple × 3 noches"]
     */
    public static function lineas(array $cantidadesPorTipo, int $noches): array
    {
        $lineas = [];
        foreach ($cantidadesPorTipo as $tipo => $n) {
            $lineas[] = $n . ($n === 1 ? ' habitación ' : ' habitaciones ') . $tipo
                . ' × ' . $noches . ($noches === 1 ? ' noche' : ' noches');
        }

        return $lineas;
    }

    // Las líneas de una reserva ya hecha
    public static function lineasEstadia(Reserva $reserva): array
    {
        $reserva->loadMissing('habitaciones.habitacion.tipo');

        return self::lineas(
            $reserva->habitaciones->countBy(fn ($rh) => $rh->habitacion->tipo->nombreVisible())->all(),
            $reserva->noches(),
        );
    }

    /**
     * Datos para el formulario de nueva reserva (resumen en vivo).
     * Precios ya calculados aquí (el navegador no hace cuentas): misma lógica que al guardar.
     *
     * @param array<int, int> $cantidades [tipo_habitacion_id => cuántas habitaciones]
     * @return array{noches: int, tipos: array, libres: array<int>, resumen: ?array}
     */
    public static function cotizar(Carbon $llegada, Carbon $salida, bool $esAgencia, array $cantidades): array
    {
        $noches = (int) $llegada->diffInDays($salida);
        $libres = Disponibilidad::habitacionesLibres($llegada, $salida)->get(['id', 'tipo_habitacion_id']);
        $porTipo = $libres->countBy('tipo_habitacion_id');

        $tipos = TipoHabitacion::where('activo', true)->orderBy('id')->get();

        $datosTipos = [];
        $porTipo = [];
        $totales = ['habitaciones' => 0, 'capacidad' => 0, 'sin_descuento' => 0.0, 'total' => 0.0];

        foreach ($tipos as $tipo) {
            $precio = Disponibilidad::precioNoche($tipo, $llegada, $esAgencia);
            $base   = Disponibilidad::precioNoche($tipo, $llegada);

            $datosTipos[] = [
                'id'     => $tipo->id,
                'libres' => $precio === null ? 0 : ($porTipo[$tipo->id] ?? 0),
                'precio' => $precio === null ? 'Sin tarifa' : Moneda::formato($precio),
            ];

            $n = (int) ($cantidades[$tipo->id] ?? 0);
            if ($n > 0 && $precio !== null) {
                $estadia = Disponibilidad::calcularEstadia($precio, $noches);
                $porTipo[$tipo->nombreVisible()] = $n;
                $totales['habitaciones']  += $n;
                $totales['capacidad']     += $n * $tipo->capacidad;
                $totales['sin_descuento'] += $n * $base * $noches;
                $totales['total']         += $n * $estadia['total'];
            }
        }

        $resumen = null;
        if ($totales['habitaciones'] > 0) {
            $etiquetas = [];
            if ($esAgencia) {
                $etiquetas[] = 'Agencia: ' . Disponibilidad::DESCUENTO_AGENCIA . ' % menos';
            }
            if ($noches > Disponibilidad::NOCHES_PRECIO_NORMAL) {
                $etiquetas[] = Disponibilidad::etiquetaEstadiaLarga();
            }

            $total = round($totales['total'], 2);

            $resumen = [
                'habitaciones'  => $totales['habitaciones'],
                'lineas'        => self::lineas($porTipo, $noches),
                'capacidad'     => $totales['capacidad'],
                'etiquetas'     => $etiquetas,
                'sin_descuento' => $etiquetas ? Moneda::formato($totales['sin_descuento']) : null,
                'total'         => Moneda::formato($total),
                'adelanto'      => Moneda::formato(Disponibilidad::adelanto($total)),
            ];
        }

        return [
            'noches'  => $noches,
            'tipos'   => $datosTipos,
            'libres'  => $libres->pluck('id')->all(),
            'resumen' => $resumen,
        ];
    }

    /**
     * Crea una reserva PENDIENTE con las habitaciones indicadas.
     *
     * @param array $datos nombre_titular, cantidad_personas, fecha_entrada, fecha_salida,
     *                     y opcionales observacion, cliente_id, agencia_id
     * @param array<int> $habitacionIds ids exactos de las habitaciones
     */
    public static function crear(array $datos, array $habitacionIds, Usuario $creador): Reserva
    {
        $llegada = Carbon::parse($datos['fecha_entrada'])->startOfDay();
        $salida  = Carbon::parse($datos['fecha_salida'])->startOfDay();
        $noches  = (int) $llegada->diffInDays($salida);

        if ($llegada->lt(today())) {
            throw ValidationException::withMessages(['fecha_entrada' => 'La llegada no puede ser en el pasado.']);
        }
        if ($noches < 1) {
            throw ValidationException::withMessages(['fecha_salida' => 'La salida debe ser después de la llegada.']);
        }
        if ($noches > Disponibilidad::MAX_NOCHES) {
            throw ValidationException::withMessages([
                'fecha_salida' => 'Se puede reservar hasta ' . Disponibilidad::MAX_NOCHES . ' noches.',
            ]);
        }

        $habitacionIds = array_values(array_unique(array_map('intval', $habitacionIds)));
        if (! $habitacionIds) {
            throw ValidationException::withMessages(['habitaciones' => 'Elige al menos una habitación.']);
        }

        $habitaciones = Habitacion::with('tipo')->whereKey($habitacionIds)->orderBy('numero')->get();

        // ¿Todas libres? (la BD lo vuelve a revisar con el trigger)
        $libres  = Disponibilidad::habitacionesLibres($llegada, $salida)->whereKey($habitacionIds)->pluck('id');
        $ocupadas = $habitaciones->reject(fn ($h) => $libres->contains($h->id));
        if ($ocupadas->isNotEmpty() || $habitaciones->count() !== count($habitacionIds)) {
            throw ValidationException::withMessages([
                'habitaciones' => $ocupadas->isNotEmpty()
                    ? 'Ya no están libres en esas fechas: ' . $ocupadas->pluck('numero')->join(', ') . '.'
                    : 'Una de las habitaciones elegidas no existe.',
            ]);
        }

        $capacidad = $habitaciones->sum(fn ($h) => $h->tipo->capacidad);
        if ((int) $datos['cantidad_personas'] > $capacidad) {
            throw ValidationException::withMessages([
                'cantidad_personas' => 'Las habitaciones elegidas son para ' . $capacidad
                    . ($capacidad === 1 ? ' persona' : ' personas') . ' como máximo.',
            ]);
        }

        // Precio por noche de cada habitación (tarifa del día de llegada, −10 % agencia)
        $esAgencia = ! empty($datos['agencia_id']);
        $precios = [];
        foreach ($habitaciones as $h) {
            $precio = Disponibilidad::precioNoche($h->tipo, $llegada, $esAgencia);
            if ($precio === null) {
                throw ValidationException::withMessages([
                    'habitaciones' => 'El tipo ' . $h->tipo->nombreVisible() . ' no tiene tarifa para esa fecha.',
                ]);
            }
            $precios[$h->id] = $precio;
        }

        try {
            return DB::transaction(function () use ($datos, $llegada, $salida, $precios, $creador) {
                // El id se pide antes para armar el código: SG-2026-0042
                $id = (int) DB::scalar("SELECT nextval(pg_get_serial_sequence('reserva', 'id'))");

                $reserva = new Reserva([
                    'codigo'                => sprintf('SG-%d-%04d', now()->year, $id),
                    'cliente_id'            => $datos['cliente_id'] ?? null,
                    'agencia_id'            => $datos['agencia_id'] ?? null,
                    'creado_por_usuario_id' => $creador->id,
                    'estado_reserva_id'     => EstadoReserva::id(EstadoReserva::PENDIENTE),
                    'nombre_titular'        => $datos['nombre_titular'],
                    'cantidad_personas'     => (int) $datos['cantidad_personas'],
                    'fecha_entrada'         => $llegada->toDateString(),
                    'fecha_salida'          => $salida->toDateString(),
                    'observacion'           => $datos['observacion'] ?? null,
                ]);
                $reserva->id = $id;
                $reserva->save();

                foreach ($precios as $habitacionId => $precio) {
                    ReservaHabitacion::create([
                        'reserva_id'            => $reserva->id,
                        'habitacion_id'         => $habitacionId,
                        'precio_noche_aplicado' => $precio,
                    ]);
                }

                return $reserva;
            });
        } catch (QueryException $e) {
            // 23P01 = el trigger de la BD encontró un solapamiento (otra persona reservó en ese instante)
            if ($e->getCode() === '23P01') {
                throw ValidationException::withMessages(['habitaciones' => self::mensajeBd($e)]);
            }
            throw $e;
        }
    }

    /**
     * Política 4: cancela una reserva PENDIENTE o CONFIRMADA.
     * Hasta 48 h antes del check-in: los pagos aprobados pasan a DEVUELTO (devolución total).
     * Con menos de 48 h: se cancela, pero el adelanto no se devuelve.
     *
     * @return bool true si corresponde devolución
     */
    public static function cancelar(Reserva $reserva): bool
    {
        return DB::transaction(function () use ($reserva) {
            $reserva = Reserva::whereKey($reserva->id)->lockForUpdate()->with('estado')->firstOrFail();

            if (! $reserva->sePuedeCancelar()) {
                throw ValidationException::withMessages([
                    'reserva' => 'Una reserva en estado ' . $reserva->estado->etiqueta() . ' no se puede cancelar.',
                ]);
            }

            if ($reserva->transferenciaEnRevision()) {
                throw ValidationException::withMessages([
                    'reserva' => 'Hay una transferencia en revisión: recepción debe revisarla antes de cancelar la reserva.',
                ]);
            }

            $devuelve = $reserva->devuelveSiCancela();

            $reserva->update(['estado_reserva_id' => EstadoReserva::id(EstadoReserva::CANCELADA)]);

            if ($devuelve) {
                Pago::where('reserva_id', $reserva->id)
                    ->where('estado', Pago::APROBADO)
                    ->update(['estado' => Pago::DEVUELTO]);
            }

            return $devuelve;
        });
    }

    /**
     * PENDIENTE → CONFIRMADA, solo si ya se pagó al menos el adelanto (30 %).
     * Si confirma un RECEPCIONISTA, gana Bs 5 de comisión (una sola vez por reserva).
     * Si el cliente o la agencia confirman solos en línea, no hay comisión.
     * La llama el Módulo 5 cuando se aprueba un pago.
     */
    public static function confirmar(Reserva $reserva, Usuario $quien): void
    {
        DB::transaction(function () use ($reserva, $quien) {
            $reserva = Reserva::whereKey($reserva->id)->lockForUpdate()->with('estado', 'habitaciones')->firstOrFail();

            if (! $reserva->esEstado(EstadoReserva::PENDIENTE)) {
                throw ValidationException::withMessages([
                    'reserva' => 'Solo se puede confirmar una reserva pendiente (esta está ' . mb_strtolower($reserva->estado->etiqueta()) . ').',
                ]);
            }

            $falta = round($reserva->adelanto() - $reserva->totalPagado(), 2);
            if ($falta > 0) {
                throw ValidationException::withMessages([
                    'reserva' => 'Falta pagar ' . Moneda::formato($falta) . ' del adelanto ('
                        . Disponibilidad::ADELANTO . ' %: ' . Moneda::formato($reserva->adelanto()) . ').',
                ]);
            }

            $reserva->update(['estado_reserva_id' => EstadoReserva::id(EstadoReserva::CONFIRMADA)]);

            if ($quien->rol === Usuario::RECEPCIONISTA) {
                ComisionRecepcionista::firstOrCreate(
                    ['reserva_id' => $reserva->id],
                    ['recepcionista_id' => $quien->id, 'monto' => ComisionRecepcionista::MONTO],
                );
            }
        });
    }

    // "ERROR:  La habitación 201 ya está reservada... CONTEXT: ..." → solo la frase del trigger
    public static function mensajeBd(QueryException $e): string
    {
        return preg_match('/ERROR:\s+(.+?)(\n|$)/u', $e->errorInfo[2] ?? '', $m)
            ? trim($m[1])
            : 'Una de las habitaciones se acaba de ocupar. Vuelve a buscar.';
    }
}
