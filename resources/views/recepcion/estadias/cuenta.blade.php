@extends('layouts.panel')

{{-- Cuenta del huésped (recepción): habitaciones, consumos, pagos, saldo, check-in y check-out --}}
@use('App\Models\EstadoReserva')
@use('App\Support\Moneda')

@php
    $estado   = $reserva->estado->nombre;
    $alojado  = $estado === EstadoReserva::CHECK_IN;
    $activa   = in_array($estado, EstadoReserva::OCUPAN, true);
    $saldo    = $cuenta['saldo'];
@endphp

@section('titulo', "Cuenta {$reserva->codigo}")

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/validacion.js') }}"></script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route('recepcion.inicio') }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route('recepcion.estadias.index', ['pestana' => $alojado ? 'alojados' : 'llegadas']) }}">Check-in / Check-out</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $reserva->codigo }}</span>
        </nav>
        <h1>{{ $reserva->nombre_titular }}</h1>
        <p class="cabecera-estado">
            <span class="estado estado-reserva estado-reserva-{{ strtolower($estado) }}">{{ $reserva->estado->etiqueta() }}</span>
            {{ $reserva->codigo }} · {{ $reserva->fecha_entrada->format('d/m/Y') }} al {{ $reserva->fecha_salida->format('d/m/Y') }}
            · {{ $reserva->cantidad_personas }} {{ $reserva->cantidad_personas === 1 ? 'persona' : 'personas' }}
        </p>
    </div>

    <div class="cabecera-acciones">
        @if ($estado === EstadoReserva::CONFIRMADA && ! $sinCheckIn)
            <form method="POST" action="{{ route('recepcion.estadias.check-in', $reserva) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primario">Check-in</button>
            </form>
        @endif
        @if ($alojado && \Illuminate\Support\Facades\Route::has('recepcion.estadias.check-out'))
            @if ($saldo > 0)
                <a href="{{ route('recepcion.reservas.pagar', $reserva) }}" class="btn btn-primario">Cobrar {{ Moneda::formato($saldo) }}</a>
            @else
                <form method="POST" action="{{ route('recepcion.estadias.check-out', $reserva) }}"
                      data-confirmar="¿Registrar la salida de {{ $reserva->nombre_titular }}?">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primario">Registrar salida</button>
                </form>
            @endif
        @endif
        <a href="{{ route('recepcion.reservas.show', $reserva) }}" class="btn btn-secundario">Reserva</a>
    </div>
</header>

<div class="revision">
    <div>
        {{-- Habitaciones --}}
        <section class="caja-plana">
            <h2>Habitaciones</h2>
            <dl class="lista-datos">
                @foreach ($reserva->habitaciones as $rh)
                    <div class="fila-habitacion">
                        <dt>
                            <strong class="numero-chico">{{ $rh->habitacion->numero }}</strong>
                            {{ $rh->habitacion->tipo->nombreVisible() }} · {{ Moneda::formato($rh->precio_noche_aplicado) }} por noche
                        </dt>
                        <dd>
                            @if ($activa && ($alternativas[$rh->id] ?? collect())->isNotEmpty())
                                <form method="POST" action="{{ route('recepcion.estadias.cambiar-habitacion', [$reserva, $rh]) }}" class="form-linea">
                                    @csrf
                                    @method('PATCH')
                                    <select name="habitacion_id" aria-label="Cambiar la habitación {{ $rh->habitacion->numero }}" required>
                                        <option value="">Cambiar a…</option>
                                        @foreach ($alternativas[$rh->id] as $alt)
                                            <option value="{{ $alt->id }}">{{ $alt->numero }} (piso {{ $alt->piso }})</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="boton-accion">Cambiar</button>
                                </form>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Consumos --}}
        <section class="caja-plana" id="consumos">
            <h2>Consumos</h2>

            @if ($alojado && \Illuminate\Support\Facades\Route::has('recepcion.consumos.store'))
                <form method="POST" action="{{ route('recepcion.consumos.store', $reserva) }}" class="form-consumo" novalidate data-validar-form>
                    @csrf
                    <div class="campo">
                        <label for="tipo_consumo_id">Tipo</label>
                        <select id="tipo_consumo_id" name="tipo_consumo_id" required>
                            @foreach ($tiposConsumo as $tc)
                                <option value="{{ $tc->id }}" @selected((int) old('tipo_consumo_id') === $tc->id)>{{ $tc->etiqueta() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="campo campo-ancho">
                        <label for="descripcion">Detalle</label>
                        <input type="text" id="descripcion" name="descripcion" maxlength="200" required list="sugerencias-consumo"
                               autocomplete="off" value="{{ old('descripcion') }}" placeholder="Agua mineral 500 ml"
                               data-vacio="Escribe qué consumió.">
                        <datalist id="sugerencias-consumo">
                            @foreach ($sugerencias as $s) <option value="{{ $s }}"> @endforeach
                        </datalist>
                        @error('descripcion') <p class="campo-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="campo campo-cantidad">
                        <label for="cantidad">Cant.</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" max="99" step="1" required inputmode="numeric"
                               value="{{ old('cantidad', 1) }}" data-validar="entero" data-vacio="Cantidad.">
                        @error('cantidad') <p class="campo-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="campo campo-precio">
                        <label for="precio_unitario">P. unit. (Bs)</label>
                        <input type="text" id="precio_unitario" name="precio_unitario" inputmode="decimal" maxlength="10" required
                               value="{{ old('precio_unitario') }}" placeholder="0,00" data-validar="precio" data-vacio="Precio.">
                        @error('precio_unitario') <p class="campo-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primario">Cargar</button>
                </form>
            @endif

            @include('cuenta.consumos', ['consumos' => $cuenta['consumos'], 'anular' => $alojado && \Illuminate\Support\Facades\Route::has('recepcion.consumos.destroy')])
        </section>

        {{-- Pagos --}}
        <section class="caja-plana">
            <h2>Pagos</h2>
            @if ($cuenta['pagos']->isEmpty())
                <p class="vacio-plano">Sin pagos.</p>
            @else
                <dl class="lista-datos">
                    @foreach ($cuenta['pagos'] as $p)
                        <div>
                            <dt>
                                {{ $p->fecha_pago->format('d/m H:i') }} · {{ $p->metodo->etiqueta() }}
                                @if ($p->recibo)
                                    · <a href="{{ route('recepcion.recibos.show', $p->recibo) }}">{{ $p->recibo->codigo() }}</a>
                                @endif
                            </dt>
                            <dd>{{ Moneda::formato($p->monto) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </section>
    </div>

    {{-- Resumen de la cuenta --}}
    <aside>
        <section class="caja-plana">
            <h2>Cuenta</h2>
            <dl class="lista-datos">
                @foreach ($cuenta['lineas'] as $linea)
                    <div><dt>{{ $linea }}</dt><dd></dd></div>
                @endforeach
                <div><dt>Estadía</dt><dd>{{ Moneda::formato($cuenta['estadia']) }}</dd></div>
                @foreach ($cuenta['porTipo'] as $tipo => $monto)
                    <div><dt>{{ $tipo }}</dt><dd>{{ Moneda::formato($monto) }}</dd></div>
                @endforeach
                <div><dt>Total</dt><dd>{{ Moneda::formato($cuenta['total']) }}</dd></div>
                <div><dt>Pagado</dt><dd>− {{ Moneda::formato($cuenta['pagado']) }}</dd></div>
                <div class="dato-grande"><dt>Saldo</dt><dd>{{ Moneda::formato($saldo) }}</dd></div>
            </dl>
        </section>

        <section class="caja-plana">
            <h2>Estadía</h2>
            <dl class="lista-datos">
                <div><dt>Llegada</dt><dd>{{ $reserva->fecha_entrada->format('d/m/Y') }}</dd></div>
                @if ($reserva->check_in_en)
                    <div><dt>Check-in</dt><dd>{{ $reserva->check_in_en->format('d/m/Y H:i') }} · {{ $reserva->checkInPor?->nombre }}</dd></div>
                @elseif ($sinCheckIn && $activa)
                    <div><dt>Check-in</dt><dd class="dato-alerta">{{ $sinCheckIn }}</dd></div>
                @elseif ($activa)
                    <div><dt>Plazo de llegada</dt><dd>{{ $plazo->format('d/m/Y H:i') }}</dd></div>
                @endif
                <div><dt>Salida</dt><dd>{{ $reserva->fecha_salida->format('d/m/Y') }}</dd></div>
                @if ($reserva->check_out_en)
                    <div><dt>Check-out</dt><dd>{{ $reserva->check_out_en->format('d/m/Y H:i') }} · {{ $reserva->checkOutPor?->nombre }}</dd></div>
                @endif
                @if ($reserva->agencia)
                    <div><dt>Agencia</dt><dd>{{ $reserva->agencia->nombre }}</dd></div>
                @endif
                @if ($reserva->observacion)
                    <div><dt>Observación</dt><dd>{{ $reserva->observacion }}</dd></div>
                @endif
            </dl>
        </section>
    </aside>
</div>
@endsection
