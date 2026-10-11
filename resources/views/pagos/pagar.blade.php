@extends('layouts.panel')

{{--
    Pagar una reserva (cliente, agencia y recepción).
    QR y tarjeta pasan por la pasarela simulada; el efectivo solo aparece en recepción.
    De la tarjeta solo se usan los 4 últimos dígitos: el número y el CVV nunca se guardan.
--}}
@use('App\Models\EstadoReserva')
@use('App\Models\MetodoPago')
@use('App\Support\Disponibilidad')
@use('App\Support\Moneda')

@php
    $listado   = $rol === 'RECEPCIONISTA' ? 'Reservas' : ($rol === 'AGENCIA' ? 'Reservas de la agencia' : 'Mis reservas');
    $pendiente = $reserva->esEstado(EstadoReserva::PENDIENTE);
    $texto     = fn ($n) => number_format($n, 2, ',', '.');
    $metodoSel = old('metodo', $metodos->first()->nombre);

    // QR decorativo (simulado): cuadrícula de 25 × 25 a partir del código de la reserva
    $semilla = md5($reserva->codigo . $limites['minimo']) . md5($reserva->codigo);
    $bits = '';
    foreach (str_split($semilla) as $c) {
        $bits .= str_pad(base_convert($c, 16, 2), 4, '0', STR_PAD_LEFT);
    }
    $esOjo = fn ($x, $y) => ($x < 7 && $y < 7) || ($x > 17 && $y < 7) || ($x < 7 && $y > 17);
@endphp

@section('titulo', "Pagar {$reserva->codigo}")

@push('scripts')
    <script src="{{ asset('js/validacion.js') }}"></script>
    <script src="{{ asset('js/pagos.js') }}"></script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route($inicio) }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route($prefijo . '.index') }}">{{ $listado }}</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route($prefijo . '.show', $reserva) }}">{{ $reserva->codigo }}</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $rol === 'RECEPCIONISTA' ? 'Registrar pago' : 'Pagar' }}</span>
        </nav>
        <h1>{{ $rol === 'RECEPCIONISTA' ? 'Registrar pago' : 'Pagar la reserva' }}</h1>
        <p>
            @if ($pendiente)
                Paga al menos el adelanto ({{ Disponibilidad::ADELANTO }} %) para confirmar la reserva.
            @else
                La reserva ya está confirmada: puedes pagar cualquier monto hasta el saldo.
            @endif
        </p>
    </div>
</header>

<form method="POST" action="{{ route($prefijo . '.pagar.store', $reserva) }}" class="reserva-nueva" novalidate
      data-validar-form data-pago>
    @csrf

    <div class="reserva-columnas">
        <div class="reserva-pasos">

            {{-- ===== 1. Monto ===== --}}
            <section class="bloque">
                <fieldset class="seccion-form">
                    <legend>
                        <span class="seccion-icono"><i data-lucide="coins"></i></span>
                        Monto
                    </legend>

                    <div class="atajos-monto">
                        @if ($pendiente && $limites['minimo'] < $limites['maximo'])
                            <button type="button" class="chip-monto" data-monto="{{ $texto($limites['minimo']) }}">
                                <span>Adelanto ({{ Disponibilidad::ADELANTO }} %)</span>
                                <strong>{{ Moneda::formato($limites['minimo']) }}</strong>
                            </button>
                        @endif
                        <button type="button" class="chip-monto" data-monto="{{ $texto($limites['maximo']) }}">
                            <span>Todo el saldo</span>
                            <strong>{{ Moneda::formato($limites['maximo']) }}</strong>
                        </button>
                    </div>

                    <div class="campo campo-monto">
                        <label for="monto">Monto a pagar</label>
                        <div class="campo-moneda">
                            <span aria-hidden="true">Bs</span>
                            <input type="text" id="monto" name="monto" inputmode="decimal" maxlength="12" required
                                   value="{{ old('monto', $texto($limites['minimo'])) }}"
                                   data-validar="monto" data-min="{{ $limites['minimo'] }}" data-max="{{ $limites['maximo'] }}"
                                   data-vacio="Escribe el monto.">
                        </div>
                        @error('monto') <p class="campo-error">{{ $message }}</p> @enderror
                        <p class="ayuda-campo">
                            Entre {{ Moneda::formato($limites['minimo']) }} y {{ Moneda::formato($limites['maximo']) }}.
                        </p>
                    </div>
                </fieldset>
            </section>

            {{-- ===== 2. Método ===== --}}
            <section class="bloque">
                <fieldset class="seccion-form">
                    <legend>
                        <span class="seccion-icono"><i data-lucide="wallet"></i></span>
                        Método de pago
                    </legend>

                    @error('metodo') <p class="campo-error campo-error-bloque">{{ $message }}</p> @enderror
                    @error('pago') <p class="campo-error campo-error-bloque">{{ $message }}</p> @enderror

                    <div class="metodos-pago" role="radiogroup" aria-label="Método de pago">
                        @foreach ($metodos as $m)
                            <label class="metodo-pago">
                                <input type="radio" name="metodo" value="{{ $m->nombre }}" @checked($metodoSel === $m->nombre)>
                                <span>
                                    <i data-lucide="{{ $m->icono() }}"></i>
                                    {{ $m->etiqueta() }}
                                </span>
                            </label>
                        @endforeach
                    </div>

                    {{-- QR simulado --}}
                    <div class="panel-metodo panel-qr" data-panel="{{ MetodoPago::QR }}" @if ($metodoSel !== MetodoPago::QR) hidden @endif>
                        <svg viewBox="0 0 25 25" class="qr-simulado" role="img" aria-label="Código QR de prueba">
                            <rect width="25" height="25" fill="#fff"/>
                            @for ($y = 0; $y < 25; $y++)
                                @for ($x = 0; $x < 25; $x++)
                                    @if (! $esOjo($x, $y) && $bits[($y * 25 + $x) % strlen($bits)] === '1')
                                        <rect x="{{ $x }}" y="{{ $y }}" width="1" height="1" fill="#0E241B"/>
                                    @endif
                                @endfor
                            @endfor
                            @foreach ([[0, 0], [18, 0], [0, 18]] as [$ox, $oy])
                                <rect x="{{ $ox }}" y="{{ $oy }}" width="7" height="7" fill="#0E241B"/>
                                <rect x="{{ $ox + 1 }}" y="{{ $oy + 1 }}" width="5" height="5" fill="#fff"/>
                                <rect x="{{ $ox + 2 }}" y="{{ $oy + 2 }}" width="3" height="3" fill="#C4507A"/>
                            @endforeach
                        </svg>
                        <div>
                            <strong>Pago con QR (simulado)</strong>
                            <p>En un banco real escanearías este código con tu app. Aquí basta con pulsar "Pagar":
                               la pasarela simulada aprueba el pago y devuelve su referencia.</p>
                        </div>
                    </div>

                    {{-- Tarjeta (solo se usan los 4 últimos dígitos) --}}
                    <fieldset class="panel-metodo" data-panel="{{ MetodoPago::TARJETA }}"
                              @if ($metodoSel !== MetodoPago::TARJETA) hidden disabled @endif>
                        <div class="rejilla-form rejilla-tres">
                            <div class="campo">
                                <label for="tarjeta_numero">Número de tarjeta</label>
                                <input type="text" id="tarjeta_numero" name="tarjeta_numero" inputmode="numeric"
                                       autocomplete="off" maxlength="23" required placeholder="4242 4242 4242 4242"
                                       data-validar="tarjeta" data-vacio="Escribe el número de la tarjeta.">
                                @error('tarjeta_numero') <p class="campo-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="campo">
                                <label for="tarjeta_vence">Vence</label>
                                <input type="text" id="tarjeta_vence" name="tarjeta_vence" inputmode="numeric"
                                       autocomplete="off" maxlength="5" required placeholder="MM/AA"
                                       data-validar="vence" data-vacio="Escribe el vencimiento (MM/AA).">
                                @error('tarjeta_vence') <p class="campo-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="campo">
                                <label for="tarjeta_cvv">Código de seguridad</label>
                                <input type="password" id="tarjeta_cvv" name="tarjeta_cvv" inputmode="numeric"
                                       autocomplete="off" maxlength="4" required placeholder="123"
                                       data-validar="cvv" data-vacio="Escribe el código de seguridad.">
                                @error('tarjeta_cvv') <p class="campo-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="ayuda-form">
                            <i data-lucide="shield-check"></i>
                            Pago simulado: no se guarda el número ni el código, solo los 4 últimos dígitos.
                            La tarjeta de prueba que termina en {{ config('pagos.tarjeta_rechazada') }} es rechazada por el banco.
                        </p>
                    </fieldset>

                    {{-- Efectivo (solo recepción) --}}
                    <div class="panel-metodo panel-efectivo" data-panel="{{ MetodoPago::EFECTIVO }}" @if ($metodoSel !== MetodoPago::EFECTIVO) hidden @endif>
                        <i data-lucide="banknote"></i>
                        <p>Recibe el efectivo en recepción y registra el pago. Se aprueba al instante con una referencia EFE-…</p>
                    </div>
                </fieldset>
            </section>
        </div>

        {{-- ===== Cuenta ===== --}}
        <aside class="resumen-reserva" aria-labelledby="titulo-cuenta">
            <div class="resumen-tarjeta">
                <p class="resumen-titulo" id="titulo-cuenta">Reserva {{ $reserva->codigo }}</p>

                <p class="resumen-total">
                    <strong>{{ Moneda::formato($reserva->total()) }}</strong>
                    <span>Total de la estadía</span>
                </p>

                <dl class="resumen-cuenta">
                    <div>
                        <dt>Pagado</dt>
                        <dd>{{ Moneda::formato($pagado) }}</dd>
                    </div>
                    @if ($pendiente)
                        <div>
                            <dt>Adelanto que falta</dt>
                            <dd>{{ Moneda::formato($limites['minimo']) }}</dd>
                        </div>
                    @endif
                    <div class="resumen-cuenta-falta">
                        <dt>Saldo</dt>
                        <dd>{{ Moneda::formato($limites['maximo']) }}</dd>
                    </div>
                </dl>

                <button type="submit" class="btn btn-tajibo btn-bloque">
                    <i data-lucide="lock"></i>
                    <span data-texto-pagar>{{ $rol === 'RECEPCIONISTA' ? 'Registrar pago' : 'Pagar' }}</span>
                </button>

                <p class="resumen-nota">
                    {{ $reserva->fecha_entrada->format('d/m/Y') }} al {{ $reserva->fecha_salida->format('d/m/Y') }}
                    · {{ $reserva->nombre_titular }}
                </p>
            </div>
        </aside>
    </div>
</form>
@endsection
