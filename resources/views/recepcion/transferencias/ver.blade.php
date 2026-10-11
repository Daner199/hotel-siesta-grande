@extends('layouts.panel')

{{-- Revisar una transferencia: comprobante a la izquierda, datos y decisión a la derecha --}}
@use('App\Models\Pago')
@use('App\Support\Moneda')

@php
    $t        = $pago->transferencia;
    $urlComp  = route('recepcion.pagos.comprobante', $pago);
    $total    = $reserva->total();
    $pagado   = $reserva->totalPagado();
    $pendiente = $pago->estado === Pago::PENDIENTE;
@endphp

@section('titulo', "Transferencia {$t->numero_operacion}")

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('recepcion.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route('recepcion.transferencias.index') }}">Transferencias</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $t->numero_operacion }}</span>
        </nav>
        <h1>Transferencia de {{ Moneda::formato($pago->monto) }}</h1>
    </div>
</header>

<div class="revision">
    <section class="caja-plana revision-comprobante" aria-label="Comprobante">
        <h2>Comprobante</h2>
        @if (! $existe)
            <p class="vacio-plano">No se encontró el archivo del comprobante.</p>
        @elseif ($esPdf)
            <iframe src="{{ $urlComp }}" title="Comprobante en PDF" class="comprobante-pdf"></iframe>
        @else
            <a href="{{ $urlComp }}" target="_blank" rel="noopener">
                <img src="{{ $urlComp }}" alt="Comprobante de la transferencia {{ $t->numero_operacion }}" class="comprobante-imagen">
            </a>
        @endif
        @if ($existe)
            <a href="{{ $urlComp }}" target="_blank" rel="noopener" class="enlace-volver">Abrir en otra pestaña</a>
        @endif
    </section>

    <div>
        <section class="caja-plana">
            <h2>Datos declarados</h2>
            <dl class="lista-datos">
                <div><dt>Nro. de operación</dt><dd>{{ $t->numero_operacion }}</dd></div>
                <div class="dato-grande"><dt>Monto</dt><dd>{{ Moneda::formato($pago->monto) }}</dd></div>
                <div><dt>Enviada</dt><dd>{{ $pago->fecha_pago->format('d/m/Y H:i') }}</dd></div>
                <div><dt>Por</dt><dd>{{ $pago->registradoPor?->nombreCompleto() }}</dd></div>
            </dl>
        </section>

        <section class="caja-plana">
            <h2>Reserva {{ $reserva->codigo }}</h2>
            <dl class="lista-datos">
                <div><dt>Titular</dt><dd>{{ $reserva->nombre_titular }}</dd></div>
                @if ($reserva->agencia)
                    <div><dt>Agencia</dt><dd>{{ $reserva->agencia->nombre }}</dd></div>
                @endif
                <div><dt>Estado</dt><dd>{{ $reserva->estado->etiqueta() }}</dd></div>
                <div><dt>Total de la estadía</dt><dd>{{ Moneda::formato($total) }}</dd></div>
                <div><dt>Pagado (aprobado)</dt><dd>{{ Moneda::formato($pagado) }}</dd></div>
                <div><dt>Adelanto ({{ \App\Support\Disponibilidad::ADELANTO }} %)</dt><dd>{{ Moneda::formato($reserva->adelanto()) }}</dd></div>
            </dl>
            <a href="{{ route('recepcion.reservas.show', $reserva) }}" class="enlace-volver">Ver la reserva</a>
        </section>

        @if ($pendiente)
            <section class="caja-plana">
                <h2>Decisión</h2>
                <form method="POST" action="{{ route('recepcion.transferencias.aprobar', $pago) }}"
                      data-confirmar="¿Aprobar la transferencia de {{ Moneda::formato($pago->monto) }}? Confirma antes que el dinero llegó a la cuenta del hotel.">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primario btn-bloque">Aprobar y emitir recibo</button>
                </form>

                <form method="POST" action="{{ route('recepcion.transferencias.rechazar', $pago) }}" class="form-rechazo" novalidate>
                    @csrf
                    @method('PATCH')
                    <div class="campo">
                        <label for="motivo_rechazo">Motivo del rechazo</label>
                        <input type="text" id="motivo_rechazo" name="motivo_rechazo" maxlength="255" list="motivos"
                               value="{{ old('motivo_rechazo') }}" placeholder="Elige o escribe el motivo">
                        <datalist id="motivos">
                            @foreach ($motivos as $m) <option value="{{ $m }}"> @endforeach
                        </datalist>
                        @error('motivo_rechazo') <p class="campo-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-secundario btn-bloque">Rechazar</button>
                </form>
            </section>
        @else
            <section class="caja-plana">
                <h2>{{ $pago->estado === Pago::APROBADO ? 'Aprobada' : 'Rechazada' }}</h2>
                <dl class="lista-datos">
                    <div><dt>Revisada por</dt><dd>{{ $t->revisor?->nombreCompleto() }}</dd></div>
                    <div><dt>Fecha</dt><dd>{{ $t->fecha_revision?->format('d/m/Y H:i') }}</dd></div>
                    @if ($t->motivo_rechazo)
                        <div><dt>Motivo</dt><dd>{{ $t->motivo_rechazo }}</dd></div>
                    @endif
                    @if ($pago->recibo)
                        <div><dt>Recibo</dt><dd><a href="{{ route('recepcion.recibos.show', $pago->recibo) }}">{{ $pago->recibo->codigo() }}</a></dd></div>
                    @endif
                </dl>
            </section>
        @endif
    </div>
</div>
@endsection
