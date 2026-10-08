{{-- Cifras grandes animadas: habitaciones (contadas en la BD), años abiertos y horas de recepción --}}
@php
    // "Las 24 horas" → 24 (si el texto no trae un número, se muestra tal cual)
    $horas = preg_match('/\d+/', $hotel['recepcion'], $m) ? (int) $m[0] : null;
@endphp

<section class="cifras-franja" aria-label="El hotel en cifras">
    <div class="contenedor cifras">
        <div class="cifra revelar">
            <strong><span data-contar="{{ $habitaciones }}">{{ $habitaciones }}</span></strong>
            <span>Habitaciones</span>
        </div>
        <div class="cifra revelar">
            <strong><span data-contar="{{ $anios }}">{{ $anios }}</span></strong>
            <span>{{ $anios === 1 ? 'Año' : 'Años' }} recibiendo huéspedes</span>
        </div>
        <div class="cifra revelar">
            @if ($horas)
                <strong><span data-contar="{{ $horas }}">{{ $horas }}</span><small> h</small></strong>
                <span>Recepción abierta</span>
            @else
                <strong class="cifra-texto">{{ $hotel['recepcion'] }}</strong>
                <span>Recepción</span>
            @endif
        </div>
    </div>
</section>
