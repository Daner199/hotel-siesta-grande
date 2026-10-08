{{--
    Galería de fotos reutilizable (tipos y habitaciones).
    Recibe:
      $fotos   → colección de fotos en orden
      $prefijo → prefijo de rutas, ej. 'admin.tipos.fotos' o 'admin.habitaciones.fotos'
      $padre   → el tipo o la habitación dueño de las fotos
      $nombre  → texto para el alt de las imágenes, ej. 'SUITE' o 'habitación 214'
      $vacio   → (opcional) texto cuando no hay fotos
--}}
@use('App\Support\Galeria')

@php
    $total  = $fotos->count();
    $libres = Galeria::MAX_FOTOS - $total;
    $erroresFotos = collect($errors->get('fotos'))->merge(collect($errors->get('fotos.*'))->flatten())->unique();
@endphp

<section class="bloque galeria" id="fotos" aria-labelledby="titulo-fotos">
    <div class="bloque-cabecera galeria-cabecera">
        <div>
            <h2 id="titulo-fotos">Fotos</h2>
            <p>{{ $total }} de {{ Galeria::MAX_FOTOS }}. La principal es la que se muestra primero.</p>
        </div>
    </div>

    {{-- ===== Subir ===== --}}
    @if ($libres > 0)
        <form method="POST" action="{{ route("{$prefijo}.store", $padre) }}" enctype="multipart/form-data"
              class="subir-fotos" data-subir-fotos data-libres="{{ $libres }}" data-max-kb="{{ Galeria::MAX_KB }}">
            @csrf
            <label class="zona-subida">
                <input type="file" name="fotos[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                       class="sr-only" aria-describedby="ayuda-fotos">
                <span class="zona-subida-icono"><i data-lucide="image-plus"></i></span>
                <strong>Elige fotos o arrástralas aquí</strong>
                <span id="ayuda-fotos">JPG, PNG o WEBP · máximo 2 MB cada una · puedes agregar {{ $libres }} más</span>
            </label>

            <ul class="vista-previa" data-vista-previa aria-live="polite"></ul>
            <p class="campo-error" data-error-fotos @if ($erroresFotos->isEmpty()) hidden @endif>
                {{ $erroresFotos->implode(' ') }}
            </p>

            <div class="subir-fotos-acciones">
                <button type="submit" class="btn btn-primario" data-boton-subir disabled>
                    <i data-lucide="upload"></i>
                    Subir fotos
                </button>
            </div>
        </form>
    @else
        <p class="nota-pie"><i data-lucide="info"></i> La galería está completa. Elimina una foto para subir otra.</p>
    @endif

    {{-- ===== Fotos actuales ===== --}}
    @if ($fotos->isEmpty())
        <div class="vacio-panel vacio-fotos">
            <span class="vacio-icono"><i data-lucide="images"></i></span>
            <h3>Todavía no hay fotos</h3>
            <p>{{ $vacio ?? 'Sin fotos se muestra un fondo elegante en su lugar.' }}</p>
        </div>
    @else
        <ul class="galeria-rejilla">
            @foreach ($fotos as $foto)
                <li @class(['foto-tarjeta', 'es-principal' => $foto->es_principal])>
                    <div class="foto-marco">
                        <img src="{{ $foto->url() }}" alt="Foto {{ $loop->iteration }} de {{ $nombre }}"
                             loading="lazy" decoding="async">
                        @if ($foto->es_principal)
                            <span class="foto-sello"><i data-lucide="star"></i> Principal</span>
                        @endif
                    </div>

                    <div class="foto-acciones">
                        <form method="POST" action="{{ route("{$prefijo}.mover", [$padre, $foto]) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="direccion" value="izquierda">
                            <button type="submit" class="boton-icono-claro" @disabled($loop->first)
                                    title="Mover a la izquierda" aria-label="Mover la foto {{ $loop->iteration }} a la izquierda">
                                <i data-lucide="arrow-left"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route("{$prefijo}.mover", [$padre, $foto]) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="direccion" value="derecha">
                            <button type="submit" class="boton-icono-claro" @disabled($loop->last)
                                    title="Mover a la derecha" aria-label="Mover la foto {{ $loop->iteration }} a la derecha">
                                <i data-lucide="arrow-right"></i>
                            </button>
                        </form>

                        @unless ($foto->es_principal)
                            <form method="POST" action="{{ route("{$prefijo}.principal", [$padre, $foto]) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="boton-icono-claro"
                                        title="Hacer principal" aria-label="Hacer principal la foto {{ $loop->iteration }}">
                                    <i data-lucide="star"></i>
                                </button>
                            </form>
                        @endunless

                        <form method="POST" action="{{ route("{$prefijo}.destroy", [$padre, $foto]) }}"
                              data-confirmar="¿Eliminar esta foto? No se puede deshacer.">
                            @csrf @method('DELETE')
                            <button type="submit" class="boton-icono-claro boton-icono-peligro"
                                    title="Eliminar" aria-label="Eliminar la foto {{ $loop->iteration }}">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
