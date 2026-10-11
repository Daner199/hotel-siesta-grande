<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PagaReservas;
use App\Models\EstadoReserva;
use App\Models\Habitacion;
use App\Models\Reserva;
use App\Models\TipoHabitacion;
use App\Support\Disponibilidad;
use App\Support\Moneda;
use App\Support\Reservas;
use App\Support\ValidacionUsuario;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Lo que comparten las reservas del cliente, de la agencia y de recepción:
 * lista, detalle, resumen en vivo, cancelar y "nueva reserva" eligiendo tipo + cantidad.
 * Cada rol solo cambia qué reservas puede ver y quién es el titular.
 * Las reglas de negocio están en App\Support\Reservas.
 */
abstract class ReservaBaseController extends Controller
{
    // Pagar, cobro con QR, recibos y lista de pagos (Módulo 5)
    use PagaReservas;

    // Prefijo de las rutas: 'cliente.reservas', 'agencia.reservas' o 'recepcion.reservas'
    abstract protected function prefijo(): string;

    // Ruta del inicio del panel (para las migas)
    abstract protected function inicio(): string;

    // Reservas que este usuario puede ver
    abstract protected function misReservas(): Builder;

    // Agencia: −10 % sobre la tarifa
    protected function esAgencia(): bool
    {
        return false;
    }

    // ---------- Lista ----------

    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = $request->integer('estado') ?: null;
        $texto  = '%' . addcslashes($buscar, '%_\\') . '%';

        $reservas = $this->misReservas()
            ->with(['estado', 'habitaciones.habitacion', 'agencia', 'cliente'])
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('codigo', 'ILIKE', $texto)
                ->orWhereRaw('unaccent(nombre_titular) ILIKE unaccent(?)', [$texto])
                ->orWhereHas('agencia', fn ($a) => $a->whereRaw('unaccent(nombre) ILIKE unaccent(?)', [$texto]))))
            ->when($estado, fn ($q) => $q->where('estado_reserva_id', $estado))
            ->orderByDesc('fecha_entrada')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('reservas.index', [
            'reservas' => $reservas,
            'estados'  => EstadoReserva::orderBy('id')->get(),
            'buscar'   => $buscar,
            'estado'   => $estado,
        ] + $this->datosVista());
    }

    // ---------- Detalle ----------

    public function show(Reserva $reserva)
    {
        $this->autorizar($reserva);

        $reserva->load(['estado', 'habitaciones.habitacion.tipo', 'agencia', 'cliente', 'creador', 'comision', 'pagos.metodo', 'pagos.registradoPor', 'pagos.recibo', 'pagos.transferencia']);

        return view('reservas.ver', ['reserva' => $reserva] + $this->datosVista());
    }

    // ---------- Cancelar (política 4) ----------

    public function cancelar(Reserva $reserva)
    {
        $this->autorizar($reserva);

        $pagado = $reserva->totalPagado();

        try {
            $devuelve = Reservas::cancelar($reserva);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $mensaje = "La reserva {$reserva->codigo} quedó cancelada.";
        if ($pagado > 0) {
            $mensaje .= $devuelve
                ? ' Se devuelve todo lo pagado (' . Moneda::formato($pagado) . ').'
                : ' Faltaban menos de ' . Reserva::HORAS_CANCELACION . ' h para el check-in: el adelanto no se devuelve.';
        }

        return redirect()->route($this->prefijo() . '.show', $reserva)->with('exito', $mensaje);
    }

    // ---------- Resumen en vivo (JSON para public/js/reservas.js) ----------

    public function cotizar(Request $request): JsonResponse
    {
        [$llegada, $salida] = $this->validarFechas($request);

        // Recepción manda habitaciones exactas; cliente y agencia, cantidad por tipo
        if ($request->has('habitaciones')) {
            $ids = array_map('intval', (array) $request->input('habitaciones'));
            $cantidades = Habitacion::whereKey($ids)->pluck('tipo_habitacion_id')->countBy()->all();
        } else {
            $cantidades = array_map('intval', array_filter((array) $request->input('tipos'), 'is_numeric'));
        }

        return response()->json(Reservas::cotizar($llegada, $salida, $this->esAgencia(), $cantidades));
    }

    // ---------- Nueva reserva por tipo y cantidad (cliente y agencia) ----------

    public function create(Request $request)
    {
        return view('reservas.crear', [
            'modo'     => 'tipos',
            'tipos'    => $this->tiposReservables(),
            'inicial'  => [
                'llegada' => $this->fechaDeConsulta($request, 'llegada'),
                'salida'  => $this->fechaDeConsulta($request, 'salida'),
                'tipo'    => $request->integer('tipo') ?: null,
            ],
        ] + $this->datosVista());
    }

    public function store(Request $request)
    {
        $this->prepararTitular($request);

        $datos = $request->validate($this->reglas() + [
            'tipos'   => ['required', 'array'],
            'tipos.*' => ['integer', 'between:0,60'],
        ], $this->mensajes() + [
            'tipos.required' => 'Elige al menos una habitación.',
            'tipos.*'        => 'La cantidad de habitaciones no es válida.',
        ]);

        $cantidades = array_filter(array_map('intval', $datos['tipos']));
        if (! $cantidades) {
            throw ValidationException::withMessages(['habitaciones' => 'Elige al menos una habitación.']);
        }

        $llegada = Carbon::parse($datos['fecha_entrada']);
        $salida  = Carbon::parse($datos['fecha_salida']);

        // El sistema asigna los números libres de cada tipo
        $ids = [];
        foreach ($cantidades as $tipoId => $cantidad) {
            $ids = array_merge($ids, Reservas::asignar((int) $tipoId, $cantidad, $llegada, $salida)->all());
        }

        $reserva = Reservas::crear($datos + $this->titular($datos), $ids, $request->user());

        return $this->guardada($reserva);
    }

    // ---------- Ayudas para los controladores de cada rol ----------

    // Datos comunes de todas las vistas de reservas
    protected function datosVista(): array
    {
        return [
            'prefijo'   => $this->prefijo(),
            'rutaPagos' => str_replace('.reservas', '.pagos', $this->prefijo()) . '.index',
            'rutaRecibo' => $this->rutaRecibos(),
            'rutaCobros' => $this->rutaCobros(),
            'rutaComprobante' => str_replace('.reservas', '.pagos', $this->prefijo()) . '.comprobante',
            'inicio'    => $this->inicio(),
            'esAgencia' => $this->esAgencia(),
            'rol'       => request()->user()->rol,
        ];
    }

    // 404 si la reserva no es de este usuario (no se revela que existe)
    protected function autorizar(Reserva $reserva): void
    {
        abort_unless($this->misReservas()->whereKey($reserva->id)->exists(), 404);
    }

    // Quién es el titular y de quién es la reserva (cada rol lo define)
    abstract protected function titular(array $datos): array;

    // Reglas del titular: el cliente no lo escribe (es él mismo)
    protected function reglasTitular(): array
    {
        return ['nombre_titular' => ['bail', 'required', 'string', 'min:2', 'max:100', ValidacionUsuario::SOLO_LETRAS]];
    }

    protected function prepararTitular(Request $request): void
    {
        $request->merge([
            'nombre_titular' => ValidacionUsuario::mayusculaInicial(ValidacionUsuario::limpiarTexto($request->input('nombre_titular'))),
            'observacion'    => ValidacionUsuario::limpiarTexto($request->input('observacion')),
        ]);
    }

    protected function reglas(): array
    {
        return $this->reglasTitular() + [
            'fecha_entrada'     => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fecha_salida'      => ['bail', 'required', 'date_format:Y-m-d', 'after:fecha_entrada'],
            'cantidad_personas' => ['bail', 'required', 'integer', 'between:1,100'],
            'observacion'       => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function mensajes(): array
    {
        return [
            'nombre_titular.required' => 'Escribe el nombre completo del titular.',
            'nombre_titular.min'      => 'El nombre debe tener al menos 2 letras.',
            'nombre_titular.max'      => 'El nombre es demasiado largo.',
            'nombre_titular.regex'    => 'El nombre solo puede tener letras.',

            'fecha_entrada.required'       => 'Elige la fecha de llegada.',
            'fecha_entrada.date_format'    => 'La fecha de llegada no es válida.',
            'fecha_entrada.after_or_equal' => 'La llegada no puede ser en el pasado.',
            'fecha_salida.required'        => 'Elige la fecha de salida.',
            'fecha_salida.date_format'     => 'La fecha de salida no es válida.',
            'fecha_salida.after'           => 'La salida debe ser después de la llegada.',

            'cantidad_personas.required' => 'Indica cuántas personas se alojan.',
            'cantidad_personas.integer'  => 'La cantidad de personas debe ser un número entero.',
            'cantidad_personas.between'  => 'La cantidad de personas debe estar entre 1 y 100.',

            'observacion.max' => 'La observación no puede pasar de 500 caracteres.',
        ];
    }

    // Después de guardar: al detalle, con el adelanto que falta para confirmar
    protected function guardada(Reserva $reserva)
    {
        $reserva->load('habitaciones');

        return redirect()
            ->route($this->prefijo() . '.show', $reserva)
            ->with('exito', "Reserva {$reserva->codigo} registrada. Queda pendiente hasta pagar el adelanto de "
                . Moneda::formato($reserva->adelanto()) . '.');
    }

    // Tipos activos que tienen habitaciones activas (con su foto principal)
    protected function tiposReservables()
    {
        return TipoHabitacion::query()
            ->where('activo', true)
            ->whereHas('habitaciones.estado', fn ($q) => $q->where('nombre', \App\Models\EstadoHabitacion::ACTIVA))
            ->with('fotoPrincipal')
            ->orderBy('id')
            ->get();
    }

    // Fecha que llega en la URL (desde la landing), solo si es válida y no es pasada
    protected function fechaDeConsulta(Request $request, string $clave): ?string
    {
        $valor = (string) $request->query($clave);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) && $valor >= today()->toDateString() ? $valor : null;
    }

    // Mismas reglas de fechas que el buscador de la landing (máx. 90 noches)
    private function validarFechas(Request $request): array
    {
        $datos = $request->validate([
            'llegada' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'salida'  => ['bail', 'required', 'date_format:Y-m-d', 'after:llegada'],
        ], [
            'llegada.required'       => 'Elige la fecha de llegada.',
            'llegada.date_format'    => 'La fecha de llegada no es válida.',
            'llegada.after_or_equal' => 'La llegada no puede ser en el pasado.',
            'salida.required'        => 'Elige la fecha de salida.',
            'salida.date_format'     => 'La fecha de salida no es válida.',
            'salida.after'           => 'La salida debe ser después de la llegada.',
        ]);

        $llegada = Carbon::parse($datos['llegada']);
        $salida  = Carbon::parse($datos['salida']);

        if ($llegada->diffInDays($salida) > Disponibilidad::MAX_NOCHES) {
            throw ValidationException::withMessages([
                'salida' => 'Se puede reservar hasta ' . Disponibilidad::MAX_NOCHES . ' noches.',
            ]);
        }

        return [$llegada, $salida];
    }
}
