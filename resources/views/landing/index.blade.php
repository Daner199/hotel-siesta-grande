@extends('layouts.base')

{{--
    Página pública del hotel. TODO sale de la BD (LandingController):
    $hotel (Hotel::datos()), $tipos, $habitaciones, $pisos, $anios, $beneficios, $promociones, $salones.
    Promociones y salones se ocultan solos si no hay datos.
--}}

@use('App\Support\Paises')

@php
    // Iniciales para el monograma cuando no hay logo: "Hotel Siesta Grande" → "SG"
    $palabras  = array_filter(explode(' ', $hotel['nombre']), fn ($p) => mb_strtolower($p) !== 'hotel');
    $monograma = mb_strtoupper(implode('', array_map(fn ($p) => mb_substr($p, 0, 1), array_slice($palabras, 0, 2))));

    $whatsapp = $hotel['whatsapp']
        ? 'https://wa.me/' . ltrim($hotel['whatsapp'], '+') . '?text='
            . rawurlencode("Hola, quiero consultar disponibilidad en el {$hotel['nombre']}.")
        : null;

    // "Reservar" en los resultados: sin sesión → login; con sesión → su panel (hasta el Módulo 4)
    $reservar = ['texto' => 'Reservar', 'url' => $usuario ? $usuario->rutaInicio() : route('login')];
@endphp

@section('titulo', $hotel['eslogan'] ?: 'Bienvenido')

@push('meta')
    <meta name="description" content="{{ $hotel['nombre'] }} en {{ $hotel['direccion']['ciudad'] }}, {{ $hotel['direccion']['pais'] }}. {{ $habitaciones }} habitaciones, piscina y restaurante. Consulta disponibilidad y precios en bolivianos.">
    <meta name="theme-color" content="#0E241B">
    <meta property="og:title" content="{{ $hotel['nombre'] }}">
    <meta property="og:description" content="{{ $hotel['eslogan'] }}">
    @if ($hotel['fotos']['portada'])
        <meta property="og:image" content="{{ $hotel['fotos']['portada'] }}">
        <link rel="preload" as="image" href="{{ $hotel['fotos']['portada'] }}">
    @endif
    @if ($hotel['fotos']['logo'])
        <link rel="icon" href="{{ $hotel['fotos']['logo'] }}">
    @endif
@endpush

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
@endpush

@push('scripts')
    <script type="application/json" id="config-landing">
        {!! json_encode([
            'disponibilidad' => route('disponibilidad'),
            'portada3d'      => asset('js/portada-3d.js'),
            'mapa'           => asset('js/mapa-hotel.js'),
            'leaflet'        => [
                'css'    => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                'cssSri' => 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=',
                'js'     => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                'jsSri'  => 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=',
            ],
            'reservar' => $reservar,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
    </script>
    <script src="{{ asset('js/landing.js') }}"></script>
@endpush

@section('contenido')
<a href="#contenido" class="saltar">Saltar al contenido</a>

@include('landing.secciones.cabecera')

<main id="contenido">
    @include('landing.secciones.portada')
    @include('landing.secciones.buscador')
    @include('landing.secciones.hotel')
    @include('landing.secciones.cifras')
    @include('landing.secciones.habitaciones')
    @include('landing.secciones.servicios')
    @include('landing.secciones.promociones')
    @include('landing.secciones.ubicacion')
    @include('landing.secciones.contacto')
    @include('landing.secciones.llamado')
</main>

@include('landing.secciones.pie')

@if ($whatsapp)
    <a href="{{ $whatsapp }}" class="whatsapp-flotante" target="_blank" rel="noopener"
       aria-label="Escribir por WhatsApp al {{ Paises::formatearTelefono($hotel['whatsapp']) }}">
        <i data-lucide="message-circle"></i>
        <span>WhatsApp</span>
    </a>
@endif

{{-- Celular: barra fija abajo con "Reservar" (aparece después de la portada) --}}
<div class="barra-reservar" data-barra-reservar>
    @if ($precioDesde)
        <p><span>Desde</span> <strong>{{ \App\Support\Moneda::formato($precioDesde) }}</strong> <span>por noche</span></p>
    @else
        <p><strong>{{ $hotel['nombre'] }}</strong></p>
    @endif
    <a href="#disponibilidad" class="boton boton-tajibo boton-chico">Reservar</a>
</div>
@endsection
