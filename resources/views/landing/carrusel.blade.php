{{--
    Carrusel de fotos (deslizable con el dedo, flechas y puntos). landing.js le da vida.
    Recibe: $fotos (colección de Foto), $nombre (para el alt), $icono (si no hay fotos)
--}}
@if ($fotos->isEmpty())
    <div class="carrusel carrusel-vacio">
        @include('landing.fondo', ['icono' => $icono])
    </div>
@else
    <div class="carrusel" data-carrusel>
        <ul class="carrusel-pista" data-carrusel-pista tabindex="0" aria-label="Fotos de {{ $nombre }}">
            @foreach ($fotos as $foto)
                <li class="carrusel-foto">
                    <img src="{{ $foto->url() }}" alt="{{ $nombre }}, foto {{ $loop->iteration }} de {{ $loop->count }}"
                         loading="lazy" decoding="async">
                </li>
            @endforeach
        </ul>

        @if ($fotos->count() > 1)
            <button type="button" class="carrusel-flecha carrusel-anterior" data-carrusel-anterior aria-label="Foto anterior">
                <i data-lucide="chevron-left"></i>
            </button>
            <button type="button" class="carrusel-flecha carrusel-siguiente" data-carrusel-siguiente aria-label="Foto siguiente">
                <i data-lucide="chevron-right"></i>
            </button>
            <div class="carrusel-puntos" data-carrusel-puntos>
                @foreach ($fotos as $foto)
                    <button type="button" @class(['activo' => $loop->first]) aria-label="Ver foto {{ $loop->iteration }}"></button>
                @endforeach
            </div>
        @endif
    </div>
@endif
