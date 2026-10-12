{{-- Filtros de cada reporte (GET). $reporte['filtros'] trae los valores usados (o los de por defecto). --}}
@php $f = $reporte['filtros']; @endphp

@switch($clave)
    @case('disponibles')
        <div class="campo">
            <label for="llegada">Llegada</label>
            <input type="date" id="llegada" name="llegada" value="{{ $f['llegada'] }}" required>
        </div>
        <div class="campo">
            <label for="salida">Salida</label>
            <input type="date" id="salida" name="salida" value="{{ $f['salida'] }}" required>
        </div>
        <div class="campo">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                @foreach ($opciones['tipos'] as $t)
                    <option value="{{ $t->id }}" @selected((int) $f['tipo'] === $t->id)>{{ $t->nombreVisible() }}</option>
                @endforeach
            </select>
        </div>
        @break

    @case('agencias')
        <div class="campo">
            <label for="desde">Entrada desde</label>
            <input type="date" id="desde" name="desde" value="{{ $f['desde'] }}" required>
        </div>
        <div class="campo">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ $f['hasta'] }}" required>
        </div>
        <div class="campo">
            <label for="agencia">Agencia</label>
            <select id="agencia" name="agencia">
                <option value="">Todas</option>
                @foreach ($opciones['agencias'] as $a)
                    <option value="{{ $a->id }}" @selected((int) $f['agencia'] === $a->id)>{{ $a->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                @foreach ($opciones['estados'] as $e)
                    <option value="{{ $e->nombre }}" @selected($f['estado'] === $e->nombre)>{{ $e->etiqueta() }}</option>
                @endforeach
            </select>
        </div>
        @break

    @case('no-show')
        <div class="campo">
            <label for="desde">Llegada desde</label>
            <input type="date" id="desde" name="desde" value="{{ $f['desde'] }}" required>
        </div>
        <div class="campo">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ $f['hasta'] }}" required>
        </div>
        @break

    @case('alojados')
        <div class="campo">
            <label for="piso">Piso</label>
            <select id="piso" name="piso">
                <option value="">Todos</option>
                @foreach ($opciones['pisos'] as $p)
                    <option value="{{ $p }}" @selected((int) $f['piso'] === (int) $p)>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        @break

    @case('cuenta')
        <div class="campo campo-codigo">
            <label for="codigo">Código de reserva</label>
            <input type="text" id="codigo" name="codigo" value="{{ $f['codigo'] }}" maxlength="30" list="codigos-recientes"
                   placeholder="SG-{{ now()->year }}-0001" autocomplete="off" class="mayusculas" required>
            <datalist id="codigos-recientes">
                @foreach ($opciones['codigos'] as $codigo => $titular)
                    <option value="{{ $codigo }}">{{ $titular }}</option>
                @endforeach
            </datalist>
        </div>
        @break

    @case('ocupacion')
        <div class="campo">
            <label for="mes">Mes</label>
            <input type="month" id="mes" name="mes" value="{{ $f['mes'] }}" required>
        </div>
        @break
@endswitch

<button type="submit" class="btn btn-primario">Ver</button>

@if ($errors->any())
    <p class="campo-error filtros-error">{{ $errors->first() }}</p>
@endif
