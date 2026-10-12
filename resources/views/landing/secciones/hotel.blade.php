{{-- El hotel: historia (solo datos reales) y foto de la fachada con parallax suave --}}
@php
    $desde = \Carbon\Carbon::parse($hotel['desde'])->locale('es');
@endphp

<section class="seccion seccion-hotel" id="hotel" aria-labelledby="titulo-hotel">
    <div class="contenedor hotel-rejilla">
        <figure class="hotel-foto con-parallax revelar" data-parallax="50">
            @if ($hotel['fotos']['fachada'])
                <img src="{{ $hotel['fotos']['fachada'] }}" alt="Fachada del {{ $hotel['nombre'] }}" loading="lazy" decoding="async">
            @else
                @include('landing.fondo', ['icono' => 'building'])
            @endif
            <figcaption class="hotel-sello">
                <span>Desde</span>
                <strong>{{ $desde->year }}</strong>
            </figcaption>
        </figure>

        <div class="hotel-texto revelar">
            <h2 id="titulo-hotel" class="titulo-seccion">El hotel</h2>
            <p>
                Abrimos nuestras puertas el {{ $desde->isoFormat('D [de] MMMM [de] YYYY') }} en
                {{ $hotel['direccion']['ciudad'] }}.
            </p>
            {{-- Historia escrita por el admin en "Datos del hotel" (una línea en blanco = otro párrafo) --}}
            @foreach (preg_split('/\n\s*\n/', (string) $hotel['textos']['historia'], -1, PREG_SPLIT_NO_EMPTY) as $parrafo)
                <p>{!! nl2br(e(trim($parrafo))) !!}</p>
            @endforeach
            <p>
                Tenemos <strong>{{ $habitaciones }} habitaciones</strong>
                @if ($pisos > 1) en {{ $pisos }} pisos @endif
                y una recepción que te atiende {{ mb_strtolower($hotel['recepcion']) }}.
            </p>

            <dl class="datos-hotel">
                <div><dt>Check-in</dt><dd>Desde las {{ $hotel['check_in'] }}</dd></div>
                <div><dt>Check-out</dt><dd>Hasta las {{ $hotel['check_out'] }}</dd></div>
                <div><dt>Recepción</dt><dd>{{ $hotel['recepcion'] }}</dd></div>
                <div><dt>Dirección</dt><dd>{{ $hotel['direccion']['calle'] }}, {{ $hotel['direccion']['ciudad'] }}</dd></div>
            </dl>
        </div>
    </div>
</section>
