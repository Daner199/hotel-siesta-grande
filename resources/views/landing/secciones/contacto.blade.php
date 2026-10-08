{{-- Contacto: solo se muestran los datos que el admin cargó --}}
@use('App\Support\Paises')

@php
    $redes = array_filter([
        'Facebook'  => [$hotel['redes']['facebook'], 'facebook'],
        'Instagram' => [$hotel['redes']['instagram'], 'instagram'],
        'TikTok'    => [$hotel['redes']['tiktok'], 'music-2'],
    ], fn ($r) => filled($r[0]));
@endphp

<section class="seccion seccion-contacto" id="contacto" aria-labelledby="titulo-contacto">
    <div class="contenedor">
        <header class="seccion-cabecera revelar">
            <p class="antetitulo">Contacto</p>
            <h2 id="titulo-contacto" class="titulo-seccion">Escríbenos o llámanos</h2>
            <p>Respondemos tus consultas sobre reservas, eventos y servicios.</p>
        </header>

        <ul class="contactos">
            @if ($hotel['telefono'])
                <li class="revelar">
                    <a href="tel:{{ $hotel['telefono'] }}" class="contacto">
                        <span class="contacto-icono"><i data-lucide="phone"></i></span>
                        <span>Teléfono</span>
                        <strong>{{ Paises::formatearTelefono($hotel['telefono']) }}</strong>
                    </a>
                </li>
            @endif
            @if ($whatsapp)
                <li class="revelar">
                    <a href="{{ $whatsapp }}" class="contacto" target="_blank" rel="noopener">
                        <span class="contacto-icono contacto-icono-whatsapp"><i data-lucide="message-circle"></i></span>
                        <span>WhatsApp</span>
                        <strong>{{ Paises::formatearTelefono($hotel['whatsapp']) }}</strong>
                    </a>
                </li>
            @endif
            @if ($hotel['correo'])
                <li class="revelar">
                    <a href="mailto:{{ $hotel['correo'] }}" class="contacto">
                        <span class="contacto-icono"><i data-lucide="mail"></i></span>
                        <span>Correo</span>
                        <strong>{{ $hotel['correo'] }}</strong>
                    </a>
                </li>
            @endif
        </ul>

        @if ($redes)
            <ul class="redes revelar" aria-label="Redes sociales">
                @foreach ($redes as $nombre => [$url, $icono])
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="red">
                            <i data-lucide="{{ $icono }}"></i> {{ $nombre }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
