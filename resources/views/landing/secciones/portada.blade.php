{{--
    Portada: foto del admin (si hay) bajo un velo selva.
    3D en DOS capas para dar profundidad real: tajibos y luciérnagas DETRÁS del título
    y algunos tajibos más cerca, pasando DELANTE del título (sin bloquear los clics).
    Sin WebGL o con "reducir movimiento": solo la foto (o el degradado si no hay foto).
--}}
@php
    $nombre = $hotel['nombre'];
    $titulo = str_starts_with(mb_strtolower($nombre), 'hotel ') ? mb_substr($nombre, 6) : $nombre;
@endphp

<section class="portada" id="inicio" data-portada>
    <div class="portada-fondo" aria-hidden="true">
        @if ($hotel['fotos']['portada'])
            <img src="{{ $hotel['fotos']['portada'] }}" alt="" fetchpriority="high" decoding="async">
        @endif
    </div>
    <div class="portada-velo" aria-hidden="true"></div>
    <canvas class="portada-3d portada-3d-fondo" data-portada-3d aria-hidden="true"></canvas>

    <div class="contenedor portada-contenido">
        <p class="antetitulo">
            <i data-lucide="map-pin"></i>
            Hotel en {{ $hotel['direccion']['ciudad'] }}
        </p>

        <h1 class="portada-titulo">
            <span class="sr-only">{{ $nombre }}</span>
            <span aria-hidden="true">{{ $titulo }}</span>
        </h1>

        @if ($hotel['eslogan'])
            <p class="portada-eslogan">{{ $hotel['eslogan'] }}</p>
        @endif

        <div class="portada-botones">
            <a href="#disponibilidad" class="boton boton-tajibo">
                <i data-lucide="calendar-search"></i> Ver disponibilidad
            </a>
            <a href="#habitaciones" class="boton boton-fantasma">Conoce las habitaciones</a>
        </div>
    </div>

    <canvas class="portada-3d portada-3d-frente" data-portada-3d-frente aria-hidden="true"></canvas>

    <a href="#disponibilidad" class="portada-bajar" aria-label="Bajar al buscador de disponibilidad">
        <i data-lucide="chevron-down"></i>
    </a>
</section>
