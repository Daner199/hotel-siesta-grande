@extends('layouts.panel')

{{--
    Pagar una reserva (cliente, agencia y recepción). Diseño plano, como una pasarela de pago real.
    - QR: genera un cobro con QR dinámico (Banco Siesta).
    - Tarjeta: vista previa, marca, Luhn y 3-D Secure (el número y el CVV nunca se guardan).
    - Transferencia: datos de la cuenta del hotel + nro. de operación + comprobante (lo revisa recepción).
    - Efectivo: solo recepción.
    public/js/pagos.js maneja los atajos de monto, los paneles y la vista previa de la tarjeta.
--}}
@use('App\Models\EstadoReserva')
@use('App\Models\MetodoPago')
@use('App\Support\Disponibilidad')
@use('App\Support\Moneda')

@php
    $listado   = $rol === 'RECEPCIONISTA' ? 'Reservas' : ($rol === 'AGENCIA' ? 'Reservas de la agencia' : 'Mis reservas');
    $pendiente = $reserva->esEstado(EstadoReserva::PENDIENTE);
    $texto     = fn ($n) => number_format($n, 2, ',', '.');
    $metodoSel = old('metodo', $metodos->first()->nombre);
    $montoIni  = old('monto', $texto($pendiente ? $limites['minimo'] : $limites['maximo']));
    $consumos  = $reserva->totalConsumos();

    $descripciones = [
        MetodoPago::EFECTIVO      => 'En caja',
        MetodoPago::QR            => 'Simple QR desde la app de tu banco',
        MetodoPago::TARJETA       => 'Visa o Mastercard',
        MetodoPago::TRANSFERENCIA => 'Transferencia bancaria',
    ];
@endphp

@section('titulo', "Pagar {$reserva->codigo}")

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/validacion.js') }}"></script>
    <script src="{{ asset('js/pagos.js') }}"></script>
@endpush

@section('panel')
<header class="cabecera-pagina">
    <div>
        <nav class="migas" aria-label="Ruta">
            <a href="{{ route($inicio) }}">Inicio</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route($prefijo . '.index') }}">{{ $listado }}</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route($prefijo . '.show', $reserva) }}">{{ $reserva->codigo }}</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $rol === 'RECEPCIONISTA' ? 'Registrar pago' : 'Pagar' }}</span>
        </nav>
        <h1>{{ $rol === 'RECEPCIONISTA' ? 'Registrar pago' : 'Pagar la reserva' }}</h1>
    </div>
</header>

<form method="POST" action="{{ route($prefijo . '.pagar.store', $reserva) }}" enctype="multipart/form-data"
      class="pago" novalidate data-validar-form data-pago>
    @csrf

    <div class="pago-columnas">
        <div class="pago-pasos">

            {{-- ===== Monto ===== --}}
            <section class="caja-plana">
                <h2>Monto a pagar</h2>

                <div class="opciones" role="radiogroup" aria-label="Monto a pagar">
                    @if ($pendiente && $limites['minimo'] < $limites['maximo'])
                        <label class="opcion">
                            <input type="radio" name="opcion_monto" value="{{ $texto($limites['minimo']) }}" data-opcion-monto
                                   @checked($montoIni === $texto($limites['minimo']))>
                            <span class="opcion-texto">
                                Adelanto para confirmar ({{ Disponibilidad::ADELANTO }} %)
                            </span>
                            <span class="opcion-valor">{{ Moneda::formato($limites['minimo']) }}</span>
                        </label>
                    @endif
                    <label class="opcion">
                        <input type="radio" name="opcion_monto" value="{{ $texto($limites['maximo']) }}" data-opcion-monto
                               @checked($montoIni === $texto($limites['maximo']))>
                        <span class="opcion-texto">Saldo total</span>
                        <span class="opcion-valor">{{ Moneda::formato($limites['maximo']) }}</span>
                    </label>
                    <label class="opcion">
                        <input type="radio" name="opcion_monto" value="otro" data-opcion-otro
                               @checked(! in_array($montoIni, [$texto($limites['minimo']), $texto($limites['maximo'])], true))>
                        <span class="opcion-texto">Otro monto</span>
                    </label>
                </div>

                <div class="campo campo-monto">
                    <label for="monto">Monto (Bs)</label>
                    <div class="campo-moneda">
                        <span aria-hidden="true">Bs</span>
                        <input type="text" id="monto" name="monto" inputmode="decimal" maxlength="12" required
                               value="{{ $montoIni }}" data-validar="monto"
                               data-min="{{ $limites['minimo'] }}" data-max="{{ $limites['maximo'] }}" data-vacio="Escribe el monto.">
                    </div>
                    @error('monto') <p class="campo-error">{{ $message }}</p> @enderror
                    <p class="ayuda-campo">Entre {{ Moneda::formato($limites['minimo']) }} y {{ Moneda::formato($limites['maximo']) }}.</p>
                </div>
            </section>

            {{-- ===== Forma de pago ===== --}}
            <section class="caja-plana">
                <h2>Forma de pago</h2>
                @error('metodo') <p class="campo-error campo-error-bloque">{{ $message }}</p> @enderror
                @error('pago') <p class="campo-error campo-error-bloque">{{ $message }}</p> @enderror

                <div class="opciones" role="radiogroup" aria-label="Forma de pago">
                    @foreach ($metodos as $m)
                        <label class="opcion">
                            <input type="radio" name="metodo" value="{{ $m->nombre }}" @checked($metodoSel === $m->nombre)>
                            <i data-lucide="{{ $m->icono() }}" class="opcion-icono"></i>
                            <span class="opcion-texto">
                                {{ $m->etiqueta() }}
                                <small>{{ $descripciones[$m->nombre] ?? '' }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>

                {{-- QR --}}
                <div class="panel-metodo nota-metodo" data-panel="{{ MetodoPago::QR }}" @if ($metodoSel !== MetodoPago::QR) hidden @endif>
                    <p>QR válido por {{ config('pagos.qr_minutos') }} minutos.</p>
                </div>

                {{-- Tarjeta --}}
                <fieldset class="panel-metodo" data-panel="{{ MetodoPago::TARJETA }}"
                          @if ($metodoSel !== MetodoPago::TARJETA) hidden disabled @endif>
                    <legend class="sr-only">Datos de la tarjeta</legend>

                    <div class="tarjeta-zona">
                        <div class="tarjeta-vista" data-tarjeta-vista aria-hidden="true">
                            <div class="tarjeta-cara tarjeta-frente">
                                <div class="tarjeta-fila">
                                    <span class="tarjeta-chip"></span>
                                    <span class="tarjeta-marca" data-vista-marca></span>
                                </div>
                                <div class="tarjeta-numero" data-vista-numero>•••• •••• •••• ••••</div>
                                <div class="tarjeta-fila">
                                    <span class="tarjeta-banco">Tarjeta de débito o crédito</span>
                                    <span class="tarjeta-vence"><small>Vence</small><span data-vista-vence>MM/AA</span></span>
                                </div>
                            </div>
                            <div class="tarjeta-cara tarjeta-dorso">
                                <div class="tarjeta-banda"></div>
                                <div class="tarjeta-firma"><span data-vista-cvv>•••</span></div>
                                <small>Código de seguridad</small>
                            </div>
                        </div>

                        <div class="tarjeta-campos">
                            <div class="campo">
                                <label for="tarjeta_numero">Número de tarjeta</label>
                                <input type="text" id="tarjeta_numero" name="tarjeta_numero" inputmode="numeric"
                                       autocomplete="off" maxlength="19" required placeholder="0000 0000 0000 0000"
                                       data-validar="tarjeta" data-vacio="Escribe el número de la tarjeta.">
                                @error('tarjeta_numero') <p class="campo-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="tarjeta-doble">
                                <div class="campo">
                                    <label for="tarjeta_vence">Vencimiento</label>
                                    <input type="text" id="tarjeta_vence" name="tarjeta_vence" inputmode="numeric"
                                           autocomplete="off" maxlength="5" required placeholder="MM/AA"
                                           data-validar="vence" data-vacio="Escribe el vencimiento (MM/AA).">
                                    @error('tarjeta_vence') <p class="campo-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="campo">
                                    <label for="tarjeta_cvv">CVV</label>
                                    <input type="text" id="tarjeta_cvv" name="tarjeta_cvv" inputmode="numeric"
                                           autocomplete="off" maxlength="4" required placeholder="123"
                                           data-validar="cvv" data-vacio="Escribe el código de seguridad.">
                                    @error('tarjeta_cvv') <p class="campo-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="pago-seguro"><i data-lucide="lock"></i> Pago seguro</p>
                </fieldset>

                {{-- Transferencia --}}
                @if ($cuenta)
                    <fieldset class="panel-metodo" data-panel="{{ MetodoPago::TRANSFERENCIA }}"
                              @if ($metodoSel !== MetodoPago::TRANSFERENCIA) hidden disabled @endif>
                        <legend class="sr-only">Datos de la transferencia</legend>

                        <p class="paso-texto">Cuenta de destino</p>
                        <dl class="lista-datos lista-recuadro">
                            <div><dt>Banco</dt><dd>{{ $cuenta['banco'] }}</dd></div>
                            <div><dt>Tipo de cuenta</dt><dd>{{ $cuenta['tipo'] }}</dd></div>
                            <div>
                                <dt>Nro. de cuenta</dt>
                                <dd>
                                    <span data-copiable>{{ $cuenta['numero'] }}</span>
                                    <button type="button" class="enlace-copiar" data-copiar="{{ $cuenta['numero'] }}">Copiar</button>
                                </dd>
                            </div>
                            <div><dt>Titular</dt><dd>{{ $cuenta['titular'] }}</dd></div>
                            <div><dt>Glosa</dt><dd>Reserva {{ $reserva->codigo }}</dd></div>
                            <div><dt>Monto a transferir</dt><dd>Bs <span data-monto-espejo>{{ $montoIni }}</span></dd></div>
                        </dl>

                        <p class="paso-texto">Datos del comprobante</p>
                        <div class="rejilla-form">
                            <div class="campo">
                                <label for="numero_operacion">Nro. de operación</label>
                                <input type="text" id="numero_operacion" name="numero_operacion" maxlength="30" required
                                       autocomplete="off" class="mayusculas" placeholder="Ej.: 84720193"
                                       value="{{ old('numero_operacion') }}"
                                       data-validar="operacion" data-vacio="Escribe el número de operación del comprobante.">
                                @error('numero_operacion') <p class="campo-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="campo">
                                <label for="comprobante">Comprobante</label>
                                <input type="file" id="comprobante" name="comprobante" required
                                       accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                       data-validar="archivo" data-max-kb="2048" data-vacio="Sube la foto o el PDF del comprobante.">
                                @error('comprobante') <p class="campo-error">{{ $message }}</p> @enderror
                                <p class="ayuda-campo">JPG, PNG o PDF · máx. 2 MB</p>
                            </div>
                        </div>
                    </fieldset>
                @endif

            </section>
        </div>

        {{-- ===== Detalle ===== --}}
        <aside class="caja-plana pago-resumen">
            <h2>Detalle</h2>
            <dl class="lista-datos">
                <div><dt>Reserva</dt><dd>{{ $reserva->codigo }}</dd></div>
                <div><dt>Estadía</dt><dd>{{ $reserva->fecha_entrada->format('d/m/Y') }} al {{ $reserva->fecha_salida->format('d/m/Y') }}</dd></div>
                <div>
                    <dt>Habitaciones</dt>
                    <dd>
                        @foreach (\App\Support\Reservas::lineasEstadia($reserva) as $linea)
                            <span class="linea-estadia">{{ $linea }}</span>
                        @endforeach
                    </dd>
                </div>
                <div><dt>Total de la estadía</dt><dd>{{ Moneda::formato($reserva->total()) }}</dd></div>
                @if ($consumos > 0)
                    <div><dt>Consumos</dt><dd>{{ Moneda::formato($consumos) }}</dd></div>
                @endif
                <div><dt>Pagado</dt><dd>{{ Moneda::formato($pagado) }}</dd></div>
                @if ($pendiente)
                    <div><dt>Adelanto que falta</dt><dd>{{ Moneda::formato($limites['minimo']) }}</dd></div>
                @endif
                <div><dt>Saldo</dt><dd>{{ Moneda::formato($limites['maximo']) }}</dd></div>
                <div class="dato-grande"><dt>Monto a pagar</dt><dd>Bs <span data-monto-espejo>{{ $montoIni }}</span></dd></div>
            </dl>

            <button type="submit" class="btn btn-primario btn-bloque" data-boton-pagar>Continuar</button>
            <a href="{{ route($prefijo . '.show', $reserva) }}" class="enlace-volver">Volver a la reserva</a>
        </aside>
    </div>
</form>
@endsection
