{{-- Cabecera: transparente sobre la portada, sólida al bajar (landing.js agrega .solida) --}}
<header class="cabecera" data-cabecera>
    <div class="contenedor cabecera-fila">
        <a href="#inicio" class="marca-landing" aria-label="{{ $hotel['nombre'] }}, ir al inicio">
            @if ($hotel['fotos']['logo'])
                <img src="{{ $hotel['fotos']['logo'] }}" alt="" class="marca-logo">
            @else
                <span class="marca-monograma" aria-hidden="true">{{ $monograma }}</span>
            @endif
            <span class="marca-nombre">{{ $hotel['nombre'] }}</span>
        </a>

        <button type="button" class="boton-menu-landing" data-boton-menu
                aria-expanded="false" aria-controls="menu-principal" aria-label="Abrir menú">
            <i data-lucide="menu"></i>
        </button>

        <nav class="menu-landing" id="menu-principal" data-menu aria-label="Secciones">
            <ul>
                <li><a href="#habitaciones">Habitaciones</a></li>
                <li><a href="#servicios">Servicios</a></li>
                @if ($beneficios->isNotEmpty() || $promociones->isNotEmpty())
                    <li><a href="#promociones">Promociones</a></li>
                @endif
                <li><a href="#ubicacion">Ubicación</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ul>

            <div class="menu-acciones">
                @auth
                    <a href="{{ $usuario->rutaInicio() }}" class="boton boton-tajibo boton-chico">
                        <i data-lucide="layout-dashboard"></i> Ir a mi panel
                    </a>
                @else
                    <a href="{{ route('login') }}" class="boton boton-fantasma boton-chico">
                        <i data-lucide="log-in"></i> Iniciar sesión
                    </a>
                    <a href="{{ route('registro') }}" class="boton boton-tajibo boton-chico">
                        Crear cuenta
                    </a>
                @endauth
            </div>
        </nav>
    </div>
</header>
