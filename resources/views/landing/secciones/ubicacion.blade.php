{{-- Ubicación: mapa con el pin del admin (Leaflet se carga solo al acercarse), dirección y "Cómo llegar" --}}
@php
    $d = $hotel['direccion'];
    $mapa = $hotel['mapa'];
    $comoLlegar = $mapa
        ? "https://www.google.com/maps/dir/?api=1&destination={$mapa['latitud']},{$mapa['longitud']}"
        : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode("{$d['calle']}, {$d['ciudad']}, {$d['pais']}");
@endphp

<section class="seccion seccion-clara" id="ubicacion" aria-labelledby="titulo-ubicacion">
    <div class="contenedor">
        <header class="seccion-cabecera revelar">
            <p class="antetitulo antetitulo-oscuro">Ubicación</p>
            <h2 id="titulo-ubicacion" class="titulo-seccion">Te esperamos en {{ $d['ciudad'] }}</h2>
        </header>

        <div @class(['ubicacion', 'ubicacion-sin-mapa' => ! $mapa])>
            @if ($mapa)
                <div class="mapa mapa-landing revelar" data-mapa data-mapa-diferido
                     data-lat="{{ $mapa['latitud'] }}" data-lng="{{ $mapa['longitud'] }}"
                     data-titulo="{{ $hotel['nombre'] }}"
                     role="region" aria-label="Mapa con la ubicación del hotel">
                    <span class="mapa-cargando">Cargando mapa…</span>
                </div>
            @endif

            <div class="direccion-tarjeta revelar">
                <span class="direccion-icono"><i data-lucide="map-pin"></i></span>
                <address>
                    <strong>{{ $d['calle'] }}</strong>
                    @if ($d['referencia'])
                        <span>{{ $d['referencia'] }}</span>
                    @endif
                    <span>{{ $d['ciudad'] }}, {{ $d['departamento'] }} · {{ $d['pais'] }}</span>
                </address>
                <a href="{{ $comoLlegar }}" class="boton boton-selva" target="_blank" rel="noopener">
                    <i data-lucide="navigation"></i> Cómo llegar
                </a>
            </div>
        </div>
    </div>
</section>
