@extends('layouts.panel')

{{-- Recibo en pantalla (el mismo documento que el PDF) --}}
@php
    $reserva = $recibo->pago->reserva;
    $listado = $rol === 'RECEPCIONISTA' ? 'Reservas' : ($rol === 'AGENCIA' ? 'Reservas de la agencia' : 'Mis reservas');
@endphp

@section('titulo', $recibo->codigo())

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
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
            <span>{{ $recibo->codigo() }}</span>
        </nav>
        <h1>Recibo {{ $recibo->codigo() }}</h1>
    </div>

    <div class="cabecera-acciones">
        @if ($rol === 'RECEPCIONISTA' && $reserva->estado?->nombre === 'CHECK_IN')
            <a href="{{ route('recepcion.estadias.cuenta', $reserva) }}" class="btn btn-secundario">Volver a la cuenta</a>
        @else
            <a href="{{ route($prefijo . '.show', $reserva) }}" class="btn btn-secundario">Ver la reserva</a>
        @endif
        <a href="{{ route($rutaRecibo . '.pdf', $recibo) }}" class="btn btn-primario">
            <i data-lucide="download"></i>
            Descargar PDF
        </a>
    </div>
</header>

<article class="hoja-recibo" aria-label="Recibo {{ $recibo->codigo() }}">
    @include('recibos.documento')
</article>
@endsection
