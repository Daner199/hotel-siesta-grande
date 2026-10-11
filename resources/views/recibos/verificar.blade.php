@extends('layouts.base')

{{-- Verificación pública de un recibo (se abre al escanear el QR del recibo) --}}
@use('App\Models\Pago')
@use('App\Support\Moneda')

@php
    $nombre = $recibo?->pago->reserva->nombre_titular;
    // "Lucía Rojas Paz" → "Lucía R. P." (no se muestra el nombre completo en una página pública)
    $nombreCorto = $nombre
        ? collect(explode(' ', $nombre))->map(fn ($p, $i) => $i === 0 ? $p : mb_substr($p, 0, 1) . '.')->join(' ')
        : null;
@endphp

@section('titulo', 'Verificar recibo')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@section('contenido')
<main class="verificacion">
    <p class="verificacion-hotel">{{ $hotel['nombre'] }}</p>

    @if ($recibo)
        <h1 class="verificacion-titulo">
            <span class="verificacion-icono verificacion-ok" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            </span>
            Recibo válido
        </h1>
        <p class="verificacion-texto">Este recibo fue emitido por el {{ $hotel['nombre'] }}.</p>

        <dl class="lista-datos">
            <div><dt>Recibo</dt><dd>{{ $recibo->codigo() }}</dd></div>
            <div><dt>Fecha de emisión</dt><dd>{{ $recibo->fecha_emision->format('d/m/Y H:i') }}</dd></div>
            <div><dt>Reserva</dt><dd>{{ $recibo->pago->reserva->codigo }}</dd></div>
            <div><dt>Titular</dt><dd>{{ $nombreCorto }}</dd></div>
            <div><dt>Monto</dt><dd>{{ Moneda::formato($recibo->pago->monto) }}</dd></div>
            <div><dt>Forma de pago</dt><dd>{{ $recibo->pago->metodo->etiqueta() }}</dd></div>
            <div>
                <dt>Estado del pago</dt>
                <dd>{{ $recibo->pago->estado === Pago::DEVUELTO ? 'Devuelto por cancelación' : 'Vigente' }}</dd>
            </div>
        </dl>
    @else
        <h1 class="verificacion-titulo">
            <span class="verificacion-icono verificacion-mal" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M7 7l10 10M17 7L7 17"/></svg>
            </span>
            Recibo no encontrado
        </h1>
        <p class="verificacion-texto">El código no corresponde a ningún recibo del {{ $hotel['nombre'] }}.
           Revisa que hayas escaneado el QR completo.</p>
    @endif

    <p class="verificacion-nota">Este documento no es una factura.</p>
</main>
@endsection
