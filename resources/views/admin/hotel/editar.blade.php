@extends('layouts.panel')

@section('titulo', 'Datos del hotel')

@php
    // Fotos del hotel: campo => [título, ayuda, clave en Hotel::datos()['fotos'], ícono]
    $tarjetasFoto = [
        'logo'             => ['Logo', 'Va en el encabezado de la página. Mejor PNG con fondo transparente.', 'logo', 'badge'],
        'portada'          => ['Portada', 'Imagen principal. También se muestra si el navegador no puede mostrar 3D.', 'portada', 'image'],
        'foto_fachada'     => ['Fachada', 'Para la sección "El hotel".', 'fachada', 'building'],
        'foto_piscina'     => ['Piscina', 'Para la sección de servicios.', 'piscina', 'waves'],
        'foto_restaurante' => ['Restaurante', 'Para la sección de servicios.', 'restaurante', 'utensils'],
    ];
    $hora = fn ($valor) => $valor ? substr((string) $valor, 0, 5) : '';
@endphp

@push('estilos')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/libphonenumber-js@1/bundle/libphonenumber-js.min.js"></script>
    <script src="{{ asset('js/validacion.js') }}"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="{{ asset('js/mapa-hotel.js') }}"></script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('admin.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <span>Datos del hotel</span>
        </nav>
        <h1>Datos del hotel</h1>
        <p>Todo lo que ves aquí aparece en la página pública. Las fotos del salón se suben en su módulo.</p>
    </div>

    <a href="{{ route('inicio') }}" class="btn btn-secundario" target="_blank" rel="noopener">
        <i data-lucide="external-link"></i>
        Ver página pública
    </a>
</header>

<form class="bloque formulario-panel" method="POST" action="{{ route('admin.hotel.update') }}"
      enctype="multipart/form-data" novalidate data-validar-form>
    @csrf
    @method('PUT')

    {{-- ===== Identidad ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="sparkles"></i></span>
            Identidad
        </legend>

        <div class="rejilla-form">
            <div class="campo">
                <label for="nombre">Nombre del hotel</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required
                       value="{{ old('nombre', $hotel->nombre) }}"
                       data-validar="empresa" data-vacio="Escribe el nombre del hotel.">
                @error('nombre') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="eslogan">Eslogan <span class="opcional">(opcional)</span></label>
                <input type="text" id="eslogan" name="eslogan" maxlength="150"
                       placeholder="Descanso con alma cruceña"
                       value="{{ old('eslogan', $hotel->eslogan) }}">
                @error('eslogan') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </fieldset>

    {{-- ===== Fotos ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="images"></i></span>
            Fotos del hotel
        </legend>
        <p class="ayuda-form">JPG, PNG o WEBP de máximo 2 MB. Si no hay foto, la página muestra un fondo elegante.</p>

        <div class="fotos-hotel">
            @foreach ($tarjetasFoto as $campo => [$titulo, $ayuda, $clave, $icono])
                <div class="foto-campo">
                    <div class="foto-campo-marco @if ($fotos[$clave]) con-foto @endif" id="vista-{{ $campo }}">
                        @if ($fotos[$clave])
                            <img src="{{ $fotos[$clave] }}" alt="{{ $titulo }} actual" loading="lazy">
                        @endif
                        <span class="foto-vacia" aria-hidden="true"><i data-lucide="{{ $icono }}"></i></span>
                    </div>

                    <div class="foto-campo-texto">
                        <strong>{{ $titulo }}</strong>
                        <span>{{ $ayuda }}</span>

                        <label class="boton-accion boton-archivo">
                            <i data-lucide="upload"></i>
                            {{ $fotos[$clave] ? 'Cambiar' : 'Subir' }}
                            <input type="file" name="{{ $campo }}" class="sr-only"
                                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                   data-foto-unica="#vista-{{ $campo }}">
                        </label>

                        @if ($fotos[$clave])
                            <label class="casilla">
                                <input type="checkbox" name="quitar[]" value="{{ $campo }}"
                                       @checked(in_array($campo, old('quitar', []), true))>
                                Quitar foto
                            </label>
                        @endif

                        <p class="campo-error" data-error-foto @error($campo) @else hidden @enderror>
                            @error($campo) {{ $message }} @enderror
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </fieldset>

    {{-- ===== Contacto ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="phone"></i></span>
            Contacto
        </legend>

        <div class="rejilla-form">
            <div class="campo">
                <label for="telefono">Teléfono fijo <span class="opcional">(opcional)</span></label>
                <div class="telefono">
                    <select id="telefono_pais" name="telefono_pais" aria-label="País del teléfono">
                        @foreach ($paises as $iso => $etiqueta)
                            <option value="{{ $iso }}" @selected(old('telefono_pais', $telefono['pais']) === $iso)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <input type="tel" id="telefono" name="telefono" inputmode="numeric" maxlength="15"
                           placeholder="33345678" value="{{ old('telefono', $telefono['numero']) }}"
                           data-validar="telefono" data-pais="telefono_pais">
                </div>
                @error('telefono_pais') <p class="campo-error">{{ $message }}</p> @enderror
                @error('telefono') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="whatsapp">WhatsApp <span class="opcional">(celular)</span></label>
                <div class="telefono">
                    <select id="whatsapp_pais" name="whatsapp_pais" aria-label="País del WhatsApp">
                        @foreach ($paises as $iso => $etiqueta)
                            <option value="{{ $iso }}" @selected(old('whatsapp_pais', $whatsapp['pais']) === $iso)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <input type="tel" id="whatsapp" name="whatsapp" inputmode="numeric" maxlength="15"
                           placeholder="77012345" value="{{ old('whatsapp', $whatsapp['numero']) }}"
                           data-validar="telefono" data-pais="whatsapp_pais">
                </div>
                @error('whatsapp_pais') <p class="campo-error">{{ $message }}</p> @enderror
                @error('whatsapp') <p class="campo-error">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="correo">Correo <span class="opcional">(opcional)</span></label>
                <input type="email" id="correo" name="correo" maxlength="150"
                       placeholder="reservas@siestagrande.com"
                       value="{{ old('correo', $hotel->correo) }}" data-validar="email">
                @error('correo') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="ayuda-form">El botón flotante de WhatsApp de la página usa este número.</p>
    </fieldset>

    {{-- ===== Redes ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="share-2"></i></span>
            Redes sociales
        </legend>
        <p class="ayuda-form">Pega la dirección completa del perfil. Si dejas una vacía, no se muestra.</p>

        <div class="rejilla-form rejilla-tres">
            @foreach (['facebook' => 'https://www.facebook.com/...', 'instagram' => 'https://www.instagram.com/...', 'tiktok' => 'https://www.tiktok.com/@...'] as $red => $ejemplo)
                <div class="campo">
                    <label for="{{ $red }}">{{ ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok'][$red] }}</label>
                    <input type="url" id="{{ $red }}" name="{{ $red }}" maxlength="255" placeholder="{{ $ejemplo }}"
                           value="{{ old($red, $hotel->$red) }}">
                    @error($red) <p class="campo-error">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    </fieldset>

    {{-- ===== Horarios ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="clock"></i></span>
            Horarios
        </legend>

        <div class="rejilla-form">
            <div class="campo">
                <label for="check_in">Check-in (entrada desde)</label>
                <input type="time" id="check_in" name="check_in" required
                       value="{{ old('check_in', $hora($hotel->check_in)) }}">
                @error('check_in') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
            <div class="campo">
                <label for="check_out">Check-out (salida hasta)</label>
                <input type="time" id="check_out" name="check_out" required
                       value="{{ old('check_out', $hora($hotel->check_out)) }}">
                @error('check_out') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </fieldset>

    {{-- ===== Ubicación ===== --}}
    <fieldset class="seccion-form">
        <legend>
            <span class="seccion-icono"><i data-lucide="map-pin"></i></span>
            Ubicación
        </legend>

        <div class="rejilla-form">
            <div class="campo">
                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" maxlength="200" required
                       value="{{ old('direccion', $hotel->direccion) }}">
                @error('direccion') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
            <div class="campo">
                <label for="ciudad">Ciudad</label>
                <input type="text" id="ciudad" name="ciudad" maxlength="100" required
                       value="{{ old('ciudad', $hotel->ciudad) }}">
                @error('ciudad') <p class="campo-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="campo">
            <label for="referencia">Cómo llegar <span class="opcional">(opcional)</span></label>
            <input type="text" id="referencia" name="referencia" maxlength="255"
                   placeholder="Entre 1.er y 2.º anillo, a cuatro cuadras de la Plaza 24 de Septiembre"
                   value="{{ old('referencia', $hotel->referencia) }}">
            @error('referencia') <p class="campo-error">{{ $message }}</p> @enderror
        </div>

        @php
            $lat = old('latitud', $hotel->latitud);
            $lng = old('longitud', $hotel->longitud);
        @endphp

        <div class="mapa mapa-admin" data-mapa data-editable
             data-lat="{{ $lat }}" data-lng="{{ $lng }}" data-titulo="{{ $hotel->nombre }}"
             data-input-lat="latitud" data-input-lng="longitud" data-texto="#coordenadas"
             role="application" aria-label="Mapa: haz clic para poner el pin del hotel"></div>

        <input type="hidden" id="latitud" name="latitud" value="{{ $lat }}">
        <input type="hidden" id="longitud" name="longitud" value="{{ $lng }}">

        <div class="mapa-pie">
            <p id="coordenadas" aria-live="polite"></p>
            <button type="button" class="boton-accion boton-peligro" data-quitar-pin>
                <i data-lucide="map-pin-off"></i> Quitar pin
            </button>
        </div>
        <p class="ayuda-form">Haz clic en el mapa para poner el pin o arrástralo. Sin pin, la página no muestra el mapa.</p>
        @error('latitud') <p class="campo-error">{{ $message }}</p> @enderror
    </fieldset>

    <div class="acciones-form">
        <a href="{{ route('admin.inicio') }}" class="btn btn-secundario">Cancelar</a>
        <button type="submit" class="btn btn-primario">
            <i data-lucide="save"></i>
            Guardar cambios
        </button>
    </div>
</form>
@endsection
