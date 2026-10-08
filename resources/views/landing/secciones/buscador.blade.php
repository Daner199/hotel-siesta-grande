{{-- Buscador de disponibilidad: landing.js consulta GET /disponibilidad y pinta el resultado --}}
<section class="buscador" id="disponibilidad" aria-labelledby="titulo-buscador">
    <div class="contenedor">
        <div class="buscador-tarjeta">
            <div class="buscador-encabezado">
                <h2 id="titulo-buscador">Consulta disponibilidad</h2>
                <p>Precios en bolivianos por habitación, para toda la estadía.</p>
            </div>

            <form class="buscador-form" action="{{ route('disponibilidad') }}" method="GET" novalidate data-buscador>
                <div class="buscador-campo">
                    <label for="llegada">Llegada</label>
                    <input type="date" id="llegada" name="llegada" required
                           min="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}">
                </div>

                <div class="buscador-campo">
                    <label for="salida">Salida</label>
                    <input type="date" id="salida" name="salida" required
                           min="{{ today()->addDay()->toDateString() }}" value="{{ today()->addDay()->toDateString() }}">
                </div>

                <div class="buscador-campo">
                    <label for="tipo">Tipo de habitación</label>
                    <select id="tipo" name="tipo">
                        <option value="">Todos los tipos</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombreVisible() }} · {{ $tipo->capacidad }} {{ $tipo->capacidad === 1 ? 'persona' : 'personas' }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="boton boton-tajibo buscador-boton" data-buscador-boton>
                    <i data-lucide="search"></i> Buscar
                </button>
            </form>

            <p class="buscador-error" data-buscador-error role="alert" hidden></p>
            <div class="buscador-resultados" data-buscador-resultados aria-live="polite"></div>

            <p class="buscador-nota">
                <i data-lucide="badge-percent"></i>
                Si te quedas más de 7 noches, desde la 8.ª noche tienes 15 % de descuento.
                Check-in desde las {{ $hotel['check_in'] }} · check-out hasta las {{ $hotel['check_out'] }}.
            </p>
        </div>
    </div>
</section>
