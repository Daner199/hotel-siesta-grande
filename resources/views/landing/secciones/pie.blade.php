{{-- Pie de página --}}
<footer class="pie">
    <div class="contenedor pie-rejilla">
        <div class="pie-marca">
            <a href="#inicio" class="marca-landing">
                @if ($hotel['fotos']['logo'])
                    <img src="{{ $hotel['fotos']['logo'] }}" alt="" class="marca-logo" loading="lazy">
                @else
                    <span class="marca-monograma" aria-hidden="true">{{ $monograma }}</span>
                @endif
                <span class="marca-nombre">{{ $hotel['nombre'] }}</span>
            </a>
            @if ($hotel['eslogan'])
                <p>{{ $hotel['eslogan'] }}</p>
            @endif
            <p class="pie-direccion">{{ $hotel['direccion']['calle'] }} · {{ $hotel['direccion']['ciudad'] }}</p>
        </div>

        <nav aria-label="Secciones (pie)">
            <h2 class="pie-titulo">El hotel</h2>
            <ul>
                <li><a href="#disponibilidad">Disponibilidad</a></li>
                <li><a href="#habitaciones">Habitaciones</a></li>
                <li><a href="#servicios">Servicios</a></li>
                <li><a href="#ubicacion">Ubicación</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ul>
        </nav>

        <nav aria-label="Tu cuenta">
            <h2 class="pie-titulo">Tu cuenta</h2>
            <ul>
                @auth
                    <li><a href="{{ $usuario->rutaInicio() }}">Ir a mi panel</a></li>
                @else
                    <li><a href="{{ route('login') }}">Iniciar sesión</a></li>
                    <li><a href="{{ route('registro') }}">Crear cuenta</a></li>
                @endauth
            </ul>
        </nav>
    </div>

    <div class="contenedor pie-final">
        <p>© {{ now()->year }} {{ $hotel['nombre'] }} · Desde el {{ \Carbon\Carbon::parse($hotel['desde'])->format('d/m/Y') }}</p>
        
    </div>
</footer>
