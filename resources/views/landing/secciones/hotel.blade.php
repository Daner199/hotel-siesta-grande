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
            <p class="antetitulo antetitulo-oscuro">El hotel</p>
            <h2 id="titulo-hotel" class="titulo-seccion">Hospitalidad cruceña desde {{ $desde->year }}</h2>
            <p>
                Abrimos nuestras puertas el {{ $desde->isoFormat('D [de] MMMM [de] YYYY') }} en
                {{ $hotel['direccion']['ciudad'] }}. Desde entonces recibimos a quienes llegan por trabajo,
                de paseo o de paso, con habitaciones cómodas, piscina y restaurante en un mismo lugar.
            </p>
            <p>
                Tenemos <strong>{{ $habitaciones }} habitaciones</strong>
                @if ($pisos > 1) en {{ $pisos }} pisos @endif
                y una recepción que te atiende {{ mb_strtolower($hotel['recepcion']) }}.
            </p>

            <ul class="horarios-hotel">
                <li><i data-lucide="log-in"></i> Check-in desde las <strong>{{ $hotel['check_in'] }}</strong></li>
                <li><i data-lucide="log-out"></i> Check-out hasta las <strong>{{ $hotel['check_out'] }}</strong></li>
            </ul>
        </div>
    </div>
</section>
