@extends('layouts.panel')

{{-- 3-D Secure (simulado): el banco pide un código de 6 dígitos "enviado por SMS". El código de prueba se muestra en pantalla. --}}
@use('App\Support\Moneda')

@section('titulo', 'Verificación del pago')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/pagos.css') }}">
@endpush

@section('panel')
<div class="verificacion-3ds">
    <p class="v3ds-banco">{{ $datos['marca'] ?? 'Tarjeta' }} · Verificación de seguridad</p>
    <h1>Confirma que eres tú</h1>
    <p class="v3ds-texto">
        Tu banco te envió un código de 6 dígitos por SMS
        {{ $telefono ? "al {$telefono}" : 'al celular registrado en tu banco' }}.
    </p>

    <dl class="lista-datos">
        <div><dt>Comercio</dt><dd>{{ $hotel['nombre'] }}</dd></div>
        <div><dt>Monto</dt><dd>{{ Moneda::formato($datos['monto']) }}</dd></div>
        <div><dt>Tarjeta</dt><dd>{{ $datos['marca'] }} •••• {{ $datos['ultimos4'] }}</dd></div>
        <div><dt>Fecha</dt><dd>{{ now()->format('d/m/Y H:i') }}</dd></div>
    </dl>

    <p class="aviso-plano">Código de prueba: <strong>{{ $datos['codigo'] }}</strong></p>

    <form method="POST" action="{{ route($prefijo . '.pagar.3ds.store', $reserva) }}" class="v3ds-form" novalidate>
        @csrf
        <div class="campo">
            <label for="codigo_sms">Código de verificación</label>
            <input type="text" id="codigo_sms" name="codigo_sms" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" required autofocus class="codigo-sms" placeholder="000000">
            @error('codigo_sms') <p class="campo-error">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn btn-primario btn-bloque">Verificar y pagar</button>
    </form>

    <div class="v3ds-acciones">
        <form method="POST" action="{{ route($prefijo . '.pagar.3ds.reenviar', $reserva) }}">
            @csrf
            <button type="submit" class="enlace-volver">Reenviar código</button>
        </form>
        <a href="{{ route($prefijo . '.pagar', $reserva) }}" class="enlace-volver">Cancelar</a>
    </div>
</div>
@endsection
