@extends('layouts.panel')

@section('titulo', $titulo)

@section('panel')
@php
    $hora = now()->hour;
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
@endphp

{{-- Bienvenida --}}
<section class="bienvenida">
    <div class="bienvenida-texto">
        <p class="bienvenida-saludo">{{ $saludo }}, {{ auth()->user()->nombre }}</p>
        <h1>{{ $titulo }}</h1>
        <p>{{ $resumen }}</p>
    </div>
    <div class="bienvenida-adorno" aria-hidden="true">
        <span></span><span></span><span></span>
    </div>
</section>

{{-- Estadísticas (solo si el panel las tiene) --}}
@if (! empty($estadisticas))
    <section class="estadisticas" aria-label="Resumen">
        @foreach ($estadisticas as $dato)
            <article class="estadistica estadistica-{{ $dato['color'] }}" data-inclinar>
                <span class="estadistica-icono"><i data-lucide="{{ $dato['icono'] }}"></i></span>
                <div>
                    <p class="estadistica-valor" data-contar="{{ $dato['valor'] }}">{{ $dato['valor'] }}</p>
                    <p class="estadistica-texto">{{ $dato['texto'] }}</p>
                </div>
            </article>
        @endforeach
    </section>
@endif

{{-- Secciones del panel --}}
<section class="bloque">
    <div class="bloque-cabecera">
        <h2>Lo que podrás gestionar</h2>
        <p>Cada sección se activa cuando terminemos su módulo.</p>
    </div>

    <div class="accesos">
                        @foreach ($secciones as $seccion)
            @if (! empty($seccion['ruta']))
                {{-- Módulo listo: la tarjeta es un enlace --}}
                <a href="{{ route($seccion['ruta']) }}" class="acceso acceso-listo" data-inclinar>
                    <span class="acceso-icono"><i data-lucide="{{ $seccion['icono'] }}"></i></span>
                    <h3>{{ $seccion['nombre'] }}</h3>
                    <p>{{ $seccion['descripcion'] }}</p>
                    <span class="etiqueta etiqueta-lista">
                        Abrir <i data-lucide="arrow-right"></i>
                    </span>
                </a>
            @else
                {{-- Módulo pendiente: solo informa --}}
                <article class="acceso" data-inclinar>
                    <span class="acceso-icono"><i data-lucide="{{ $seccion['icono'] }}"></i></span>
                    <h3>{{ $seccion['nombre'] }}</h3>
                    <p>{{ $seccion['descripcion'] }}</p>
                    <span class="etiqueta">{{ $seccion['paso'] }}</span>
                </article>
            @endif
        @endforeach
    </div>
</section>
@endsection