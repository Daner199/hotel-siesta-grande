{{--
    Servicios: piscina y restaurante a pantalla completa con el texto encima (fotos de Datos del hotel).
    Salones activos de la BD como bloques grandes (se ocultan si no hay).
--}}
@use('App\Support\Moneda')

@php
    $servicios = [
        ['titulo' => 'Piscina', 'foto' => $hotel['fotos']['piscina'], 'icono' => 'waves',
         'texto' => $hotel['textos']['piscina']],
        ['titulo' => 'Restaurante', 'foto' => $hotel['fotos']['restaurante'], 'icono' => 'utensils',
         'texto' => $hotel['textos']['restaurante']],
    ];
@endphp

<div id="servicios">
    @foreach ($servicios as $s)
        <section class="servicio-pantalla" aria-labelledby="servicio-{{ $loop->index }}">
            <div class="servicio-pantalla-foto con-parallax" data-parallax="70" aria-hidden="true">
                @if ($s['foto'])
                    <img src="{{ $s['foto'] }}" alt="" loading="lazy" decoding="async">
                @else
                    @include('landing.fondo', ['icono' => $s['icono']])
                @endif
            </div>
            <div class="servicio-pantalla-velo" aria-hidden="true"></div>

            <div class="contenedor servicio-pantalla-texto revelar">
                <h2 id="servicio-{{ $loop->index }}">{{ $s['titulo'] }}</h2>
                @if ($s['texto'])
                    <p>{{ $s['texto'] }}</p>
                @endif
            </div>
        </section>
    @endforeach

    {{-- Salón de eventos: solo si el admin registró alguno (Módulo 8) --}}
    @if ($salones->isNotEmpty())
        <section class="seccion seccion-clara" aria-labelledby="titulo-salones">
            <div class="contenedor">
                <header class="seccion-cabecera revelar">
                    <h2 id="titulo-salones" class="titulo-seccion">
                        {{ $salones->count() === 1 ? 'Salón de eventos' : 'Salones de eventos' }}
                    </h2>
                    @if ($hotel['textos']['salon'])
                        <p>{{ $hotel['textos']['salon'] }}</p>
                    @endif
                </header>
            </div>

            @foreach ($salones as $salon)
                <article class="bloque-habitacion" aria-labelledby="salon-{{ $salon->id }}">
                    <div class="bloque-habitacion-foto con-parallax revelar" data-parallax="40">
                        @include('landing.carrusel', [
                            'fotos'  => $salon->fotos,
                            'nombre' => $salon->nombre,
                            'icono'  => 'party-popper',
                        ])
                    </div>
                    <div class="bloque-habitacion-texto revelar">
                        <h3 id="salon-{{ $salon->id }}">{{ $salon->nombre }}</h3>
                        @if ($salon->descripcion)
                            <p>{{ $salon->descripcion }}</p>
                        @endif
                        <ul class="datos-habitacion">
                            <li><i data-lucide="users"></i> Hasta {{ $salon->capacidad }} personas</li>
                        </ul>
                        <div class="bloque-habitacion-pie">
                            <p class="precio-landing">
                                <strong>{{ Moneda::formato($salon->costo_hora) }}</strong>
                                <span>por hora</span>
                            </p>
                            <a href="#contacto" class="boton boton-selva">Consultar</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
</div>
