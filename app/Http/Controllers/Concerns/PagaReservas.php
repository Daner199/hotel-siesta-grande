<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CobroQr;
use App\Models\EstadoReserva;
use App\Models\Hotel;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Recibo;
use App\Models\Reserva;
use App\Support\CobrosQr;
use App\Support\Moneda;
use App\Support\Pagos;
use App\Support\Qr;
use App\Support\Recibos;
use App\Support\Tarjetas;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pagos de una reserva (Módulo 5), compartidos por cliente, agencia y recepción:
 * pagar (efectivo, tarjeta con 3-D Secure, QR dinámico, transferencia), cobro con QR,
 * recibos, comprobantes de transferencia y lista de pagos.
 * Lo usa ReservaBaseController (necesita autorizar(), prefijo(), misReservas() y datosVista()).
 */
trait PagaReservas
{
    // Métodos que puede usar este rol (recepción cambia esto: efectivo sí, transferencia no)
    protected function metodosPermitidos(): array
    {
        $metodos = MetodoPago::EN_LINEA;

        // Transferencia solo si el hotel cargó su cuenta en "Datos del hotel"
        if (Hotel::datos()['cuenta']) {
            $metodos[] = MetodoPago::TRANSFERENCIA;
        }

        return $metodos;
    }

    public function formularioPago(Reserva $reserva)
    {
        $this->autorizar($reserva);
        $reserva->load(['estado', 'habitaciones']);

        $limites = Pagos::limites($reserva);
        if (! $limites) {
            return redirect()->route($this->prefijo() . '.show', $reserva)->with('error', Pagos::motivoSinPago($reserva));
        }

        $hotel = Hotel::datos();

        return view('pagos.pagar', [
            'reserva' => $reserva,
            'limites' => $limites,
            'pagado'  => $reserva->totalPagado(),
            'metodos' => MetodoPago::whereIn('nombre', $this->metodosPermitidos())->orderBy('id')->get(),
            'cuenta'  => $hotel['cuenta'],
            'hotel'   => $hotel,
        ] + $this->datosVista());
    }

    public function pagar(Request $request, Reserva $reserva)
    {
        $this->autorizar($reserva);
        $reserva->load(['estado', 'habitaciones']);

        $request->merge([
            'monto'            => Moneda::leer($request->input('monto')),
            'tarjeta_numero'   => preg_replace('/\D/', '', (string) $request->input('tarjeta_numero')) ?: null,
            'tarjeta_cvv'      => preg_replace('/\D/', '', (string) $request->input('tarjeta_cvv')) ?: null,
            'numero_operacion' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('numero_operacion'))) ?: null,
        ]);

        $tarjeta = MetodoPago::TARJETA;
        $transf  = MetodoPago::TRANSFERENCIA;

        $datos = $request->validate([
            'metodo'           => ['required', Rule::in($this->metodosPermitidos())],
            'monto'            => ['bail', 'required', 'numeric', 'decimal:0,2', 'gt:0'],
            'tarjeta_numero'   => ["required_if:metodo,{$tarjeta}", 'nullable', 'digits_between:13,16'],
            'tarjeta_vence'    => ["required_if:metodo,{$tarjeta}", 'nullable', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'tarjeta_cvv'      => ["required_if:metodo,{$tarjeta}", 'nullable', 'digits_between:3,4'],
            'numero_operacion' => ["required_if:metodo,{$transf}", 'nullable', 'regex:/^[A-Z0-9-]{4,30}$/',
                                   Rule::unique('transferencia', 'numero_operacion')],
            'comprobante'      => ["required_if:metodo,{$transf}", 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ], [
            'metodo.required'               => 'Elige la forma de pago.',
            'metodo.in'                     => $request->input('metodo') === MetodoPago::EFECTIVO
                ? 'El efectivo solo se paga en la recepción del hotel.'
                : 'Elige la forma de pago.',
            'monto.required'                => 'Escribe el monto.',
            'monto.numeric'                 => 'Escribe un monto válido, por ejemplo 716,25.',
            'monto.decimal'                 => 'El monto puede tener como máximo 2 decimales.',
            'monto.gt'                      => 'El monto debe ser mayor que 0.',
            'tarjeta_numero.required_if'    => 'Escribe el número de la tarjeta.',
            'tarjeta_numero.digits_between' => 'El número de la tarjeta tiene 16 dígitos.',
            'tarjeta_vence.required_if'     => 'Escribe el vencimiento (MM/AA).',
            'tarjeta_vence.regex'           => 'El vencimiento debe ser MM/AA, por ejemplo 08/28.',
            'tarjeta_cvv.required_if'       => 'Escribe el código de seguridad.',
            'tarjeta_cvv.digits_between'    => 'El código de seguridad tiene 3 o 4 dígitos.',
            'numero_operacion.required_if'  => 'Escribe el número de operación del comprobante.',
            'numero_operacion.regex'        => 'El número de operación tiene de 4 a 30 letras o números.',
            'numero_operacion.unique'       => 'Ese número de operación ya fue registrado.',
            'comprobante.required_if'       => 'Sube la foto o el PDF del comprobante.',
            'comprobante.file'              => 'El comprobante no se pudo subir.',
            'comprobante.mimes'             => 'El comprobante debe ser JPG, PNG, WEBP o PDF.',
            'comprobante.max'               => 'El comprobante puede pesar como máximo 2 MB.',
        ]);

        $monto = (float) $datos['monto'];

        switch ($datos['metodo']) {
            case MetodoPago::QR:
                // Se genera el cobro y se muestra el QR (lo paga Banco Siesta desde el celular)
                $cobro = CobrosQr::crear($reserva, $request->user(), $monto);

                return redirect()->route($this->rutaCobros() . '.show', $cobro);

            case MetodoPago::TRANSFERENCIA:
                Pagos::registrarTransferencia($reserva, $request->user(), $monto,
                    $datos['numero_operacion'], $request->file('comprobante'));

                return redirect()->route($this->prefijo() . '.show', $reserva)
                    ->with('exito', 'Recibimos tu transferencia. Queda en revisión hasta que recepción la verifique.');

            case MetodoPago::TARJETA:
                $numero = $datos['tarjeta_numero'];
                if ($this->tarjetaVencida($datos['tarjeta_vence'])) {
                    throw ValidationException::withMessages(['tarjeta_vence' => 'La tarjeta está vencida.']);
                }
                if (! Tarjetas::luhn($numero)) {
                    throw ValidationException::withMessages(['tarjeta_numero' => 'El número de tarjeta no es válido. Revísalo.']);
                }
                Pagos::validarMonto($reserva, $monto);

                // De la tarjeta solo se usan la marca y los 4 últimos dígitos
                $marca    = Tarjetas::marca($numero);
                $ultimos4 = substr($numero, -4);

                if (Tarjetas::pide3ds($numero)) {
                    // 3-D Secure: el banco "envía un SMS". En la sesión NO va el número de la tarjeta.
                    session()->put($this->clave3ds($reserva), [
                        'monto'    => $monto,
                        'ultimos4' => $ultimos4,
                        'marca'    => $marca,
                        'codigo'   => (string) random_int(100000, 999999),
                        'vence'    => now()->addMinutes(5)->timestamp,
                        'intentos' => 0,
                    ]);

                    return redirect()->route($this->prefijo() . '.pagar.3ds', $reserva);
                }

                return $this->cobrarTarjeta($request, $reserva, $monto, $ultimos4, $marca);

            default: // EFECTIVO (solo recepción)
                $estabaPendiente = $reserva->esEstado(EstadoReserva::PENDIENTE);
                $pago = Pagos::registrar($reserva, $request->user(), MetodoPago::EFECTIVO, $monto);

                return $this->resultadoPago($reserva, $pago, $estabaPendiente);
        }
    }

    // ---------- 3-D Secure (tarjeta de prueba terminada en 3220) ----------

    public function verificacion3ds(Reserva $reserva)
    {
        $this->autorizar($reserva);

        $datos = session($this->clave3ds($reserva));
        if (! $datos || $datos['vence'] < now()->timestamp) {
            session()->forget($this->clave3ds($reserva));

            return redirect()->route($this->prefijo() . '.pagar', $reserva)
                ->with('error', 'La verificación venció. Vuelve a ingresar la tarjeta. No se cobró nada.');
        }

        return view('pagos.verificacion', [
            'reserva'  => $reserva,
            'datos'    => $datos,
            'telefono' => $this->telefonoEnmascarado(),
            'hotel'    => Hotel::datos(),
        ] + $this->datosVista());
    }

    public function confirmar3ds(Request $request, Reserva $reserva)
    {
        $this->autorizar($reserva);

        $clave = $this->clave3ds($reserva);
        $datos = session($clave);
        if (! $datos || $datos['vence'] < now()->timestamp) {
            session()->forget($clave);

            return redirect()->route($this->prefijo() . '.pagar', $reserva)
                ->with('error', 'La verificación venció. Vuelve a ingresar la tarjeta. No se cobró nada.');
        }

        $codigo = preg_replace('/\D/', '', (string) $request->input('codigo_sms'));
        if ($codigo !== $datos['codigo']) {
            $datos['intentos']++;
            if ($datos['intentos'] >= 3) {
                session()->forget($clave);

                return redirect()->route($this->prefijo() . '.pagar', $reserva)
                    ->with('error', 'Superaste los 3 intentos del código. El pago se canceló y no se cobró nada.');
            }
            session()->put($clave, $datos);
            $quedan = 3 - $datos['intentos'];

            throw ValidationException::withMessages([
                'codigo_sms' => "El código no es correcto. Te " . ($quedan === 1 ? 'queda 1 intento.' : "quedan {$quedan} intentos."),
            ]);
        }

        session()->forget($clave);
        $reserva->load(['estado', 'habitaciones']);

        return $this->cobrarTarjeta($request, $reserva, (float) $datos['monto'], $datos['ultimos4'], $datos['marca']);
    }

    public function reenviar3ds(Reserva $reserva)
    {
        $this->autorizar($reserva);

        $clave = $this->clave3ds($reserva);
        if ($datos = session($clave)) {
            session()->put($clave, ['codigo' => (string) random_int(100000, 999999), 'vence' => now()->addMinutes(5)->timestamp] + $datos);
        }

        return redirect()->route($this->prefijo() . '.pagar.3ds', $reserva)->with('exito', 'Te enviamos un código nuevo.');
    }

    // ---------- Cobro con QR dinámico ----------

    public function cobro(CobroQr $cobro)
    {
        $this->autorizar($cobro->reserva);
        CobrosQr::actualizar($cobro);

        $url = CobrosQr::urlBanco($cobro);

        return view('pagos.qr', [
            'cobro'  => $cobro->load('reserva'),
            'urlQr'  => $url,
            'qr'     => Qr::svg($url),
            // El celular no llega a 127.0.0.1 ni a localhost: avisar
            'soloPc' => (bool) preg_match('#^https?://(127\.0\.0\.1|localhost)(:|/|$)#', $url),
            'hotel'  => Hotel::datos(),
        ] + $this->datosVista());
    }

    // JSON para public/js/cobro-qr.js (se consulta cada 2 segundos)
    public function estadoCobro(CobroQr $cobro): JsonResponse
    {
        $this->autorizar($cobro->reserva);
        CobrosQr::actualizar($cobro);

        $recibo = $cobro->estado === CobroQr::PAGADO ? $cobro->pago?->recibo : null;

        return response()->json([
            'estado'      => $cobro->estado,
            'segundos'    => max(0, (int) now()->diffInSeconds($cobro->vence_en, false)),
            'transaccion' => $cobro->pago?->referencia,
            'recibo'      => $recibo ? route($this->rutaRecibos() . '.show', $recibo) : null,
        ]);
    }

    public function anularCobro(CobroQr $cobro)
    {
        $this->autorizar($cobro->reserva);
        CobrosQr::anular($cobro);

        return redirect()->route($this->prefijo() . '.pagar', $cobro->reserva_id)->with('exito', 'El QR quedó anulado.');
    }

    // ---------- Recibos y comprobantes ----------

    public function recibo(Recibo $recibo)
    {
        $this->autorizar($recibo->pago->reserva);

        return view('recibos.ver', Recibos::datosVista($recibo) + $this->datosVista());
    }

    public function reciboPdf(Recibo $recibo)
    {
        $this->autorizar($recibo->pago->reserva);

        return Pdf::loadView('recibos.pdf', Recibos::datosVista($recibo))
            ->setPaper('letter')
            ->setOption('isFontSubsettingEnabled', true) // solo las letras usadas: PDF liviano
            ->download($recibo->codigo() . '.pdf');
    }

    // Foto o PDF del comprobante de una transferencia (archivo privado: solo el dueño y recepción)
    public function comprobante(Pago $pago)
    {
        $this->autorizar($pago->reserva);
        abort_unless($pago->transferencia && Storage::disk('local')->exists($pago->transferencia->comprobante), 404);

        return Storage::disk('local')->response($pago->transferencia->comprobante);
    }

    // ---------- Lista de pagos ("Mis pagos" / "Pagos") ----------

    public function pagos(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $estado = in_array($request->query('estado'), [Pago::APROBADO, Pago::PENDIENTE, Pago::RECHAZADO, Pago::DEVUELTO], true)
            ? $request->query('estado') : null;
        $metodo = $request->integer('metodo') ?: null;
        $texto  = '%' . addcslashes($buscar, '%_\\') . '%';

        $consulta = Pago::query()
            ->whereIn('reserva_id', $this->misReservas()->select('id'))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('referencia', 'ILIKE', $texto)
                ->orWhereHas('reserva', fn ($r) => $r->where('codigo', 'ILIKE', $texto)
                    ->orWhereRaw('unaccent(nombre_titular) ILIKE unaccent(?)', [$texto]))))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($metodo, fn ($q) => $q->where('metodo_pago_id', $metodo));

        $totales = (clone $consulta)->reorder()
            ->selectRaw("COALESCE(SUM(monto) FILTER (WHERE estado = 'APROBADO'), 0) AS aprobado,
                         COALESCE(SUM(monto) FILTER (WHERE estado = 'DEVUELTO'), 0) AS devuelto")
            ->first();

        $pagos = $consulta->with(['reserva', 'metodo', 'registradoPor', 'recibo', 'transferencia'])
            ->orderByDesc('fecha_pago')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('pagos.index', [
            'pagos'   => $pagos,
            'totales' => $totales,
            'metodos' => MetodoPago::orderBy('id')->get(),
            'buscar'  => $buscar,
            'estado'  => $estado,
            'metodo'  => $metodo,
        ] + $this->datosVista());
    }

    // ---------- Ayudas ----------

    private function cobrarTarjeta(Request $request, Reserva $reserva, float $monto, string $ultimos4, ?string $marca)
    {
        $estabaPendiente = $reserva->esEstado(EstadoReserva::PENDIENTE);
        $pago = Pagos::registrar($reserva, $request->user(), MetodoPago::TARJETA, $monto, $ultimos4, null, $marca);

        return $this->resultadoPago($reserva, $pago, $estabaPendiente);
    }

    // Aprobado → al recibo. Rechazado → de vuelta al formulario (el intento queda en el historial).
    private function resultadoPago(Reserva $reserva, Pago $pago, bool $estabaPendiente)
    {
        if ($pago->estado !== Pago::APROBADO) {
            return redirect()->route($this->prefijo() . '.pagar', $reserva)
                ->with('error', 'Pago rechazado por el banco: ' . mb_strtolower(rtrim(
                    preg_replace('/^Tarjeta [^.]*\.\s*/', '', (string) $pago->observacion) ?: 'no lo aprobó', '.')) . '. No se cobró nada.');
        }

        $mensaje = 'Pago aprobado: ' . Moneda::formato($pago->monto) . '.';
        if ($estabaPendiente && $reserva->fresh()->esEstado(EstadoReserva::CONFIRMADA)) {
            $mensaje .= ' La reserva quedó confirmada.';
        }

        return redirect()->route($this->rutaRecibos() . '.show', $pago->recibo)->with('exito', $mensaje);
    }

    // "08/28" → vencida si ya pasó el último día de ese mes
    private function tarjetaVencida(string $vence): bool
    {
        [$mes, $anio] = array_map('intval', explode('/', $vence));

        return Carbon::create(2000 + $anio, $mes, 1)->endOfMonth()->lt(today());
    }

    // "+59171234567" → "+591 ••••••67" (el celular registrado del usuario)
    private function telefonoEnmascarado(): ?string
    {
        $tel = (string) auth()->user()->telefono;

        return strlen($tel) >= 8
            ? substr($tel, 0, 4) . ' ' . str_repeat('•', strlen($tel) - 6) . substr($tel, -2)
            : null;
    }

    private function clave3ds(Reserva $reserva): string
    {
        return "pago_3ds.{$reserva->id}";
    }

    protected function rutaCobros(): string
    {
        return str_replace('.reservas', '.cobros', $this->prefijo());
    }

    protected function rutaRecibos(): string
    {
        return str_replace('.reservas', '.recibos', $this->prefijo());
    }
}
