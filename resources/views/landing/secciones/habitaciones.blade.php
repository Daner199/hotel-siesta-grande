{{--
    Habitaciones: un bloque grande por tipo activo, alternando foto izquierda/derecha.
    Foto a media pantalla con zoom suave al pasar el mouse y parallax. Todo de la BD.
--}}
@use('App\Support\Moneda')

<section class="seccion seccion-clara seccion-habitaciones" id="habitaciones" aria-labelledby="titulo-habitaciones">
    <div class="contenedor">
        <header class="seccion-cabecera revelar">
            <p class="antetitulo antetitulo-oscuro">Habitaciones</p>
            <h2 id="titulo-habitaciones" class="titulo-seccion">Elige cómo quieres descansar</h2>
            <p>Tarifas de hoy por noche, en bolivianos.</p>
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
                <p class="bloque-numero">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} / {{ str_pad($loop->count, 2, '0', STR_PAD_LEFT) }}</p>
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
            <p class="vacio-landing">Muy pronto publicaremos nuestras habitaciones.</p>
        </div>
    @endforelse
</section>
