<?php

namespace App\Http\Controllers\Recepcion;

use App\Http\Controllers\Controller;
use App\Models\EstadoReserva;
use App\Models\Habitacion;
use App\Models\Reserva;
use App\Models\ReservaHabitacion;
use App\Models\TipoConsumo;
use App\Models\Consumo;
use App\Support\Cuentas;
use App\Support\Estadias;
use App\Support\NoShow;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Check-in / Check-out: llegadas de hoy, huéspedes alojados, salidas de hoy y no-show.
 * Desde la cuenta del huésped se cargan consumos, se cobra el saldo y se registra la salida.
 */
class EstadiaController extends Controller
{
    public const PESTANAS = [
        'llegadas' => 'Llegan hoy',
        'alojados' => 'Alojados',
        'salidas'  => 'Salen hoy',
        'noshow'   => 'No-show',
    ];

    public function index(Request $request)
    {
        NoShow::siCorresponde();

        $pestana = array_key_exists($request->query('pestana'), self::PESTANAS) ? $request->query('pestana') : 'llegadas';
        $buscar  = trim((string) $request->query('buscar'));
        $texto   = '%' . addcslashes($buscar, '%_\\') . '%';

        $reservas = $this->consulta($pestana)
            ->with(['estado', 'habitaciones.habitacion.tipo', 'agencia'])
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('codigo', 'ILIKE', $texto)
                ->orWhereRaw('unaccent(nombre_titular) ILIKE unaccent(?)', [$texto])
                ->orWhereHas('habitaciones.habitacion', fn ($h) => $h->where('numero', 'ILIKE', $texto))))
            ->paginate(20)
            ->withQueryString();

        return view('recepcion.estadias.index', [
            'reservas' => $reservas,
            'pestana'  => $pestana,
            'buscar'   => $buscar,
            'conteos'  => collect(self::PESTANAS)->map(fn ($t, $clave) => $this->consulta($clave)->count()),
        ]);
    }

    public function checkIn(Request $request, Reserva $reserva)
    {
        try {
            Estadias::checkIn($reserva, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', "{$reserva->codigo}: " . collect($e->errors())->flatten()->first());
        }

        $reserva->load('habitaciones.habitacion');

        return back()->with('exito', "Check-in de {$reserva->codigo} · hab. "
            . $reserva->habitaciones->map(fn ($rh) => $rh->habitacion->numero)->join(', ') . '.');
    }

    // Check-out: solo con el saldo en 0 (estadía + consumos ya cobrados)
    public function checkOut(Request $request, Reserva $reserva)
    {
        try {
            Estadias::checkOut($reserva, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('recepcion.estadias.cuenta', $reserva)
            ->with('exito', "Check-out de {$reserva->nombre_titular} registrado.");
    }

    // Cuenta del huésped: habitaciones, consumos, pagos y saldo
    public function cuenta(Reserva $reserva)
    {
        $reserva->load(['estado', 'habitaciones.habitacion.tipo', 'agencia', 'cliente', 'checkInPor', 'checkOutPor']);
        $activa = in_array($reserva->estado->nombre, EstadoReserva::OCUPAN, true);

        return view('recepcion.estadias.cuenta', [
            'reserva'      => $reserva,
            'cuenta'       => Cuentas::de($reserva),
            'sinCheckIn'   => Estadias::motivoSinCheckIn($reserva),
            'plazo'        => Estadias::plazoNoShow($reserva),
            'alternativas' => $activa
                ? $reserva->habitaciones->mapWithKeys(fn ($rh) => [$rh->id => Estadias::alternativas($rh)])
                : collect(),
            'tiposConsumo' => TipoConsumo::orderBy('id')->get(),
            // Sugerencias para escribir rápido: lo que más se cargó últimamente
            'sugerencias'  => Consumo::selectRaw('descripcion, MAX(id) AS ultimo')->groupBy('descripcion')
                ->orderByDesc('ultimo')->limit(40)->pluck('descripcion'),
        ]);
    }

    public function cambiarHabitacion(Request $request, Reserva $reserva, ReservaHabitacion $habitacion)
    {
        abort_unless($habitacion->reserva_id === $reserva->id, 404);
        $request->validate(['habitacion_id' => ['required', 'integer']], ['habitacion_id.*' => 'Elige la habitación nueva.']);

        $anterior = $habitacion->habitacion->numero;
        $nueva    = Habitacion::findOrFail($request->integer('habitacion_id'));

        try {
            Estadias::cambiarHabitacion($habitacion, $nueva);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('exito', "Habitación {$anterior} cambiada por la {$nueva->numero}.");
    }

    // Botón "Procesar no-show" (además corre solo cada 5 minutos)
    public function procesarNoShow()
    {
        $r = NoShow::procesar();

        return redirect()->route('recepcion.estadias.index', ['pestana' => 'noshow'])->with('exito',
            $r['no_show'] + $r['liberadas'] === 0
                ? 'No hay reservas vencidas.'
                : "No-show: {$r['no_show']} · pendientes liberadas: {$r['liberadas']}.");
    }

    // Filtro de cada pestaña
    private function consulta(string $pestana)
    {
        $hoy = today()->toDateString();
        $estado = fn (array $nombres) => fn ($q) => $q->whereIn('nombre', $nombres);

        return match ($pestana) {
            // Confirmadas (y pendientes, para cobrar el adelanto) que llegan hoy o ayer dentro del plazo
            'llegadas' => Reserva::whereHas('estado', $estado([EstadoReserva::CONFIRMADA, EstadoReserva::PENDIENTE]))
                ->where('fecha_entrada', '<=', $hoy)
                ->where('fecha_entrada', '>=', today()->subDay()->toDateString())
                ->orderBy('fecha_entrada')->orderBy('codigo'),
            'alojados' => Reserva::whereHas('estado', $estado([EstadoReserva::CHECK_IN]))
                ->orderBy('fecha_salida')->orderBy('codigo'),
            'salidas' => Reserva::whereHas('estado', $estado([EstadoReserva::CHECK_IN]))
                ->where('fecha_salida', '<=', $hoy)
                ->orderBy('fecha_salida')->orderBy('codigo'),
            // Últimos 30 días: no-show y pendientes liberadas sin pago
            default => Reserva::where(fn ($q) => $q
                    ->whereHas('estado', $estado([EstadoReserva::NO_SHOW]))
                    ->orWhere(fn ($w) => $w->whereHas('estado', $estado([EstadoReserva::CANCELADA]))
                        ->where('observacion', 'LIKE', '%' . NoShow::NOTA_VENCIDA . '%')))
                ->where('fecha_entrada', '>=', today()->subDays(30)->toDateString())
                ->orderByDesc('fecha_entrada')->orderBy('codigo'),
        };
    }
}
