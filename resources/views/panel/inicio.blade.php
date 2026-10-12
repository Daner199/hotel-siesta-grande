@extends('layouts.panel')

{{-- Inicio de cada panel: cifras del día y listas de trabajo (panel/inicio/<rol>) --}}
@section('titulo', 'Inicio')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@php
    $hora = now()->hour;
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
@endphp

@section('panel')
<header class="cabecera-pagina">
    <div>
        <p class="saludo-inicio">{{ $saludo }}, {{ auth()->user()->nombre }}</p>
        <h1>{{ $titulo }}</h1>
    </div>
    @if (! empty($acciones))
        <div class="cabecera-acciones">
            @foreach ($acciones as $accion)
                <a href="{{ $accion['ruta'] }}" @class(['btn', 'btn-primario' => $accion['principal'] ?? false, 'btn-secundario' => ! ($accion['principal'] ?? false)])>
                    <i data-lucide="{{ $accion['icono'] }}"></i> {{ $accion['texto'] }}
                </a>
            @endforeach
        </div>
    @endif
</header>

<dl class="cifras-dia">
    @foreach ($cifras as $cifra)
        <div @class(['cifra-alerta' => $cifra['alerta'] ?? false])>
            <dt>
                @if (! empty($cifra['ruta']))
                    <a href="{{ $cifra['ruta'] }}">{{ $cifra['texto'] }}</a>
                @else
                    {{ $cifra['texto'] }}
                @endif
            </dt>
            <dd>{{ $cifra['valor'] }}</dd>
            @if (! empty($cifra['detalle']))
                <dd class="cifra-detalle">{{ $cifra['detalle'] }}</dd>
            @endif
        </div>
    @endforeach
</dl>

@include('panel.inicio.' . ($rol === 'agencia' || $rol === 'cliente' ? 'cuenta' : $rol))
@endsection
