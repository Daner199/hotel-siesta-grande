@extends('layouts.panel')

{{--
    Cobro con QR dinámico. public/js/cobro-qr.js lleva la cuenta regresiva y pregunta el estado
    cada 2 segundos; cuando Banco Siesta avisa el pago (webhook), muestra "Pago recibido" y va al recibo.
--}}
@use('App\Models\CobroQr')
@use('App\Support\Moneda')

@php
    $reserva = $cobro->reserva;
    $listado = $rol === 'RECEPCIONISTA' ? 'Reservas' : ($rol === 'AGENCIA' ? 'Reservas de la agencia' : 'Mis reservas');
    $segundos = max(0, (int) now()->diffInSeconds($cobro->vence_en, false));
@endphp

@section('titulo', 'Pago con QR')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/cobro-qr.js') }}"></script>
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
            <span>Pago con QR</span>
        </nav>
        <h1>Pago con QR</h1>
    </div>
</header>

<div class="cobro-qr" data-cobro-qr
     data-estado-url="{{ route($rutaCobros . '.estado', $cobro) }}"
     data-estado="{{ $cobro->estado }}" data-segundos="{{ $segundos }}">

    <section class="cobro-qr-codigo" aria-label="Código QR">
        <div class="cobro-qr-imagen" data-qr-imagen>
            {!! $qr !!}
        </div>
        <p class="cobro-qr-instruccion">Escanea el código con la cámara de tu celular o desde la app de tu banco.</p>
        <p class="cobro-qr-url">
            <span>Dirección del cobro</span>
            <a href="{{ $urlQr }}" target="_blank" rel="noopener">{{ $urlQr }}</a>
        </p>
        @if ($soloPc)
            <p class="aviso-plano">
                Esta dirección solo funciona en esta computadora. Para escanear con el celular (en la misma red WiFi),
                configura <code>PAGOS_URL_PUBLICA</code> con la IP de esta PC o ábrela con esa IP. Ver el README.
            </p>
        @endif
    </section>

    <section class="cobro-qr-datos" aria-live="polite">
        <dl class="lista-datos">
            <div><dt>Comercio</dt><dd>{{ $hotel['nombre'] }}</dd></div>
            <div><dt>Reserva</dt><dd>{{ $reserva->codigo }}</dd></div>
            <div class="dato-grande"><dt>Monto a pagar</dt><dd>{{ Moneda::formato($cobro->monto) }}</dd></div>
            <div><dt>Vence a las</dt><dd>{{ $cobro->vence_en->format('H:i') }} <span class="gris" data-cuenta-regresiva></span></dd></div>
        </dl>

        {{-- Estados (cobro-qr.js muestra uno) --}}
        <div class="cobro-estado" data-panel-estado="{{ CobroQr::PENDIENTE }}" @if ($cobro->estado !== CobroQr::PENDIENTE) hidden @endif>
            <span class="procesando" aria-hidden="true"></span>
            <div>
                <strong>Esperando el pago</strong>
                <p>La pantalla se actualiza sola cuando el banco confirme.</p>
            </div>
        </div>

        <div class="cobro-estado cobro-exito" data-panel-estado="{{ CobroQr::PAGADO }}" @if ($cobro->estado !== CobroQr::PAGADO) hidden @endif>
            <svg class="check-exito" viewBox="0 0 52 52" aria-hidden="true">
                <circle cx="26" cy="26" r="24"/><path d="M15 27l7 7 15-15"/>
            </svg>
            <div>
                <strong>Pago recibido</strong>
                <p>Nro. de transacción <span data-transaccion>{{ $cobro->pago?->referencia }}</span>. Abriendo el recibo…</p>
            </div>
        </div>

        <div class="cobro-estado cobro-error" data-panel-estado="{{ CobroQr::RECHAZADO }}" @if ($cobro->estado !== CobroQr::RECHAZADO) hidden @endif>
            <div>
                <strong>El banco rechazó el pago</strong>
                <p>Fondos insuficientes en la cuenta de débito. No se cobró nada.</p>
            </div>
        </div>

        <div class="cobro-estado cobro-error" data-panel-estado="{{ CobroQr::VENCIDO }}" @if ($cobro->estado !== CobroQr::VENCIDO) hidden @endif>
            <div>
                <strong>El QR venció</strong>
                <p>Pasaron {{ config('pagos.qr_minutos') }} minutos sin pago. Genera un QR nuevo.</p>
            </div>
        </div>

        <div class="cobro-estado cobro-error" data-panel-estado="{{ CobroQr::ANULADO }}" @if ($cobro->estado !== CobroQr::ANULADO) hidden @endif>
            <div>
                <strong>Este QR fue anulado</strong>
                <p>Se generó otro QR o la reserva cambió.</p>
            </div>
        </div>

        <div class="cobro-acciones">
            <a href="{{ route($prefijo . '.pagar', $reserva) }}" class="btn btn-secundario" data-generar-otro
               @if ($cobro->estado === CobroQr::PENDIENTE || $cobro->estado === CobroQr::PAGADO) hidden @endif>Generar otro QR</a>

            @if ($cobro->estado === CobroQr::PENDIENTE)
                <form method="POST" action="{{ route($rutaCobros . '.anular', $cobro) }}" data-anular
                      data-confirmar="¿Anular este QR? Si alguien ya lo escaneó, ya no podrá pagarlo.">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-secundario">Anular QR</button>
                </form>
            @endif
        </div>
    </section>
</div>
@endsection
