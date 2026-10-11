{{--
    Banco Siesta: banco SIMULADO. Esta página se abre en el celular al escanear el QR del hotel.
    Pantallas (public/js/banco.js): PIN → detalle → procesando → comprobante (o rechazo).
    Fuente del sistema y barra superior como una app nativa. No usa el diseño del hotel a propósito.
--}}
@use('App\Models\CobroQr')
@use('App\Support\Moneda')

@php
    $saldo    = (float) $banco['saldo'];
    $cuenta   = $banco['cuenta'] . ' ****' . $banco['numero'];
    $segundos = $cobro ? max(0, (int) now()->diffInSeconds($cobro->vence_en, false)) : 0;
    $error = match (true) {
        ! $cobro                                => ['No encontramos este cobro', 'El código QR no es válido. Vuelve a escanearlo.'],
        $cobro->estado === CobroQr::PAGADO      => ['Este cobro ya fue pagado', 'No necesitas volver a pagarlo.'],
        $cobro->estado === CobroQr::VENCIDO     => ['Este cobro venció', 'Pide al comercio que genere un QR nuevo.'],
        $cobro->estado !== CobroQr::PENDIENTE   => ['Este cobro ya no está disponible', 'Pide al comercio que genere un QR nuevo.'],
        default                                 => null,
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1D3A2E">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $banco['nombre'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/banco.css') }}">
</head>
<body>
<div class="app" data-banco
     data-pagar-url="{{ $cobro ? route('banco.qr.pagar', $cobro->token) : '' }}"
     data-segundos="{{ $segundos }}"
     data-saldo="{{ Moneda::formato($saldo - (float) ($cobro->monto ?? 0)) }}">

    <header class="barra">
        <button type="button" class="barra-boton" data-atras aria-label="Atrás" hidden>
            <svg viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg>
        </button>
        <span class="barra-titulo" data-titulo>{{ $banco['nombre'] }}</span>
        <span class="barra-marca" aria-hidden="true">BS</span>
    </header>

    @if ($error)
        {{-- Cobro inexistente, vencido o ya pagado --}}
        <main class="pantalla">
            <section class="aviso">
                <h1>{{ $error[0] }}</h1>
                <p>{{ $error[1] }}</p>
            </section>
        </main>
    @else
        {{-- 1. PIN --}}
        <main class="pantalla pantalla-pin" data-pantalla="pin">
            <p class="pin-titulo">Ingresa tu PIN</p>
            <p class="pin-sub">Clave de 4 dígitos de tu banca móvil</p>
            <div class="pin-puntos" data-pin-puntos aria-live="polite" aria-label="0 de 4 dígitos">
                <span></span><span></span><span></span><span></span>
            </div>
            <div class="teclado" role="group" aria-label="Teclado numérico">
                @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9] as $n)
                    <button type="button" data-tecla="{{ $n }}">{{ $n }}</button>
                @endforeach
                <span></span>
                <button type="button" data-tecla="0">0</button>
                <button type="button" data-borrar aria-label="Borrar">
                    <svg viewBox="0 0 24 24"><path d="M9 6h11v12H9l-6-6z M13 10l4 4 M17 10l-4 4"/></svg>
                </button>
            </div>
        </main>

        {{-- 2. Detalle del pago --}}
        <main class="pantalla" data-pantalla="detalle" hidden>
            <p class="seccion">Pago con QR</p>
            <dl class="lista">
                <div><dt>Comercio</dt><dd>{{ $hotel['nombre'] }}<small>{{ $hotel['direccion']['ciudad'] }}</small></dd></div>
                <div><dt>Concepto</dt><dd>Reserva {{ $cobro->reserva->codigo }}</dd></div>
            </dl>

            <div class="monto">
                <span>Monto a pagar</span>
                <strong>{{ Moneda::formato($cobro->monto) }}</strong>
            </div>

            <dl class="lista">
                <div><dt>Cuenta de débito</dt><dd>{{ $cuenta }}</dd></div>
                <div><dt>Saldo disponible</dt><dd>{{ Moneda::formato($saldo) }}</dd></div>
                <div><dt>El cobro vence en</dt><dd data-vence>—</dd></div>
            </dl>

            <p class="prueba">
                Modo de prueba:
                <button type="button" class="enlace" data-fondos>simular fondos insuficientes</button>
            </p>

            <div class="pie-fijo">
                <button type="button" class="boton" data-confirmar>Confirmar pago</button>
            </div>
        </main>

        {{-- 3. Procesando --}}
        <main class="pantalla pantalla-centro" data-pantalla="procesando" hidden>
            <span class="girando" aria-hidden="true"></span>
            <p>Procesando...</p>
        </main>

        {{-- 4. Comprobante del banco --}}
        <main class="pantalla" data-pantalla="exito" hidden>
            <div class="resultado">
                <svg class="check" viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="24"/><path d="M15 27l7 7 15-15"/></svg>
                <h1>Pago exitoso</h1>
                <strong class="resultado-monto">{{ Moneda::formato($cobro->monto) }}</strong>
            </div>
            <dl class="lista">
                <div><dt>Nro. de transacción</dt><dd data-transaccion></dd></div>
                <div><dt>Fecha y hora</dt><dd data-fecha></dd></div>
                <div><dt>Comercio</dt><dd>{{ $hotel['nombre'] }}</dd></div>
                <div><dt>Concepto</dt><dd>Reserva {{ $cobro->reserva->codigo }}</dd></div>
                <div><dt>Cuenta de débito</dt><dd>{{ $cuenta }}</dd></div>
            </dl>
            <div class="pie-fijo pie-doble">
                <button type="button" class="boton boton-claro" data-compartir>Compartir</button>
                <button type="button" class="boton" data-inicio>Volver al inicio</button>
            </div>
        </main>

        {{-- 4b. Rechazo --}}
        <main class="pantalla" data-pantalla="rechazo" hidden>
            <div class="resultado">
                <svg class="equis" viewBox="0 0 52 52" aria-hidden="true"><circle cx="26" cy="26" r="24"/><path d="M18 18l16 16M34 18L18 34"/></svg>
                <h1>Pago rechazado</h1>
                <p data-motivo>Fondos insuficientes en la cuenta de débito.</p>
            </div>
            <div class="pie-fijo">
                <button type="button" class="boton" data-inicio>Volver al inicio</button>
            </div>
        </main>

        {{-- 5. Inicio del banco --}}
        <main class="pantalla" data-pantalla="inicio" hidden>
            <p class="seccion">Mis cuentas</p>
            <dl class="lista">
                <div><dt>{{ $banco['cuenta'] }}</dt><dd>****{{ $banco['numero'] }}</dd></div>
                <div><dt>Saldo disponible</dt><dd data-saldo-inicio>{{ Moneda::formato($saldo) }}</dd></div>
            </dl>
            <p class="nota">Ya puedes cerrar esta ventana.</p>
        </main>
    @endif
</div>

<script src="{{ asset('js/banco.js') }}"></script>
</body>
</html>
