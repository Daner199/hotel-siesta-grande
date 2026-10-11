{{-- Bloqueos por fechas de una habitación (se incluye al editar la habitación) --}}
@php
    $hoy        = today();
    $todos      = $habitacion->bloqueos()->get();
    $actuales   = $todos->filter(fn ($b) => $b->fecha_hasta->gt($hoy));
    $terminados = $todos->filter(fn ($b) => $b->fecha_hasta->lte($hoy))->sortByDesc('fecha_desde');
    $errores    = $errors->getBag('bloqueo');
@endphp

<section class="bloque bloqueos" id="bloqueos" aria-labelledby="titulo-bloqueos">
    <div class="bloque-cabecera">
        <h2 id="titulo-bloqueos">Bloqueos por fechas</h2>
        <p>Para mantenimientos cortos: en esas fechas la habitación no se puede reservar, aunque siga activa.</p>
    </div>

    <form method="POST" action="{{ route('admin.habitaciones.bloqueos.store', $habitacion) }}"
          class="form-bloqueo" novalidate data-validar-form>
        @csrf
        <div class="rejilla-form rejilla-tres">
            <div class="campo">
                <label for="fecha_desde">Bloquear desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" required
                       min="{{ $hoy->toDateString() }}" value="{{ old('fecha_desde') }}"
                       data-validar="fecha" data-vacio="Elige desde qué día se bloquea.">
                @if ($errores->has('fecha_desde')) <p class="campo-error">{{ $errores->first('fecha_desde') }}</p> @endif
            </div>

            <div class="campo">
                <label for="fecha_hasta">Libre de nuevo el</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" required
                       min="{{ $hoy->copy()->addDay()->toDateString() }}" value="{{ old('fecha_hasta') }}"
                       data-validar="fecha" data-vacio="Elige el día en que vuelve a estar libre.">
                @if ($errores->has('fecha_hasta')) <p class="campo-error">{{ $errores->first('fecha_hasta') }}</p> @endif
            </div>

            <div class="campo">
                <label for="motivo">Motivo</label>
                <input type="text" id="motivo" name="motivo" maxlength="200" required
                       placeholder="Ej.: Pintura" value="{{ old('motivo') }}"
                       data-vacio="Escribe el motivo (ej.: pintura, plomería).">
                @if ($errores->has('motivo')) <p class="campo-error">{{ $errores->first('motivo') }}</p> @endif
            </div>
        </div>

        <div class="acciones-form acciones-form-compactas">
            <button type="submit" class="btn btn-primario">
                <i data-lucide="calendar-x-2"></i>
                Bloquear fechas
            </button>
        </div>
    </form>

    @if ($actuales->isEmpty())
        <p class="nota-pie">
            <i data-lucide="calendar-check"></i>
            No tiene bloqueos en curso ni programados.
        </p>
    @else
        <div class="tabla-envoltura">
            <table class="tabla tabla-compacta">
                <thead>
                    <tr>
                        <th>Fechas</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($actuales as $b)
                        @php
                            $enCurso = $b->fecha_desde->lte($hoy);
                            $dias = (int) $b->fecha_desde->diffInDays($b->fecha_hasta);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $b->fecha_desde->format('d/m/Y') }}</strong>
                                <i data-lucide="arrow-right" class="flecha-fechas" aria-label="hasta"></i>
                                <strong>{{ $b->fecha_hasta->format('d/m/Y') }}</strong>
                                <span class="dato-secundario">{{ $dias }} {{ $dias === 1 ? 'noche' : 'noches' }}</span>
                            </td>
                            <td>{{ $b->motivo }}</td>
                            <td>
                                <span @class(['etiqueta', 'etiqueta-alerta' => $enCurso])>
                                    {{ $enCurso ? 'En curso' : 'Programado' }}
                                </span>
                            </td>
                            <td>
                                <div class="acciones">
                                    <form method="POST"
                                          action="{{ route('admin.habitaciones.bloqueos.destroy', [$habitacion, $b]) }}"
                                          data-confirmar="¿Quitar el bloqueo del {{ $b->fecha_desde->format('d/m/Y') }} al {{ $b->fecha_hasta->format('d/m/Y') }}? La habitación se podrá reservar en esas fechas.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="boton-accion boton-peligro">
                                            <i data-lucide="calendar-check"></i>
                                            <span>Quitar</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($terminados->isNotEmpty())
        <details class="anuladas">
            <summary>
                <i data-lucide="chevron-right"></i>
                {{ $terminados->count() }} {{ $terminados->count() === 1 ? 'bloqueo terminado' : 'bloqueos terminados' }}
            </summary>
            <ul>
                @foreach ($terminados as $b)
                    <li>{{ $b->fecha_desde->format('d/m/Y') }} al {{ $b->fecha_hasta->format('d/m/Y') }} · {{ $b->motivo }}</li>
                @endforeach
            </ul>
        </details>
    @endif
</section>
