{{--
    Habitaciones: un bloque grande por tipo activo, alternando foto izquierda/derecha.
    Foto a media pantalla con zoom suave al pasar el mouse y parallax. Todo de la BD.
--}}
@use('App\Support\Moneda')

<section class="seccion seccion-clara seccion-habitaciones" id="habitaciones" aria-labelledby="titulo-habitaciones">
    <div class="contenedor">
        <header class="seccion-cabecera revelar">
            <h2 id="titulo-habitaciones" class="titulo-seccion">Habitaciones</h2>
            <p>Tarifa de hoy por habitación y por noche. Más de {{ \App\Support\Disponibilidad::NOCHES_PRECIO_NORMAL }} noches: {{ \App\Support\Disponibilidad::DESCUENTO_LARGA }} % menos desde la {{ \App\Support\Disponibilidad::NOCHES_PRECIO_NORMAL + 1 }}.ª.</p>
        </header>
    </div>

    @forelse ($tipos as $tipo)
        <article class="bloque-habitacion" aria-labelledby="tipo-{{ $tipo->id }}">
            <div class="bloque-habitacion-foto con-parallax revelar" data-parallax="40">
                @include('landing.carrusel', [
                    'fotos'  => $tipo->fotos,
                    'nombre' => "habitación {$tipo->nombreVisible()}",
                    'icono'  => 'bed-double',
                ])
            </div>

            <div class="bloque-habitacion-texto revelar">
                <h3 id="tipo-{{ $tipo->id }}">{{ $tipo->nombreVisible() }}</h3>
                @if ($tipo->descripcion)
                    <p>{{ $tipo->descripcion }}</p>
                @endif

                <ul class="datos-habitacion">
                    <li><i data-lucide="users"></i> {{ $tipo->capacidad }} {{ $tipo->capacidad === 1 ? 'persona' : 'personas' }}</li>
                    <li><i data-lucide="door-closed"></i> {{ $tipo->habitaciones_count }} {{ $tipo->habitaciones_count === 1 ? 'habitación' : 'habitaciones' }}</li>
                </ul>

                <div class="bloque-habitacion-pie">
                    @if ($tipo->vigente)
                        <p class="precio-landing">
                            <strong>{{ Moneda::formato($tipo->vigente->precio_noche) }}</strong>
                            <span>por noche</span>
                        </p>
                    @else
                        <p class="precio-landing"><span>Consulta el precio</span></p>
                    @endif

                    <button type="button" class="boton boton-selva" data-elegir-tipo="{{ $tipo->id }}">
                        Ver disponibilidad
                    </button>
                </div>
            </div>
        </article>
    @empty
        <div class="contenedor">
            <p class="vacio-landing">Sin habitaciones publicadas.</p>
        </div>
    @endforelse
</section>
