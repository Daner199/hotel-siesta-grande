{{-- Lista de consumos de una reserva. Variables: $consumos, $anular (mostrar botón Anular, solo recepción) --}}
@use('App\Support\Moneda')

@if ($consumos->isEmpty())
    <p class="vacio-plano">Sin consumos.</p>
@else
    <div class="tabla-envoltura">
        <table class="tabla tabla-compacta">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Detalle</th>
                    <th>Cant.</th>
                    <th>P. unit.</th>
                    <th>Importe</th>
                    @if ($anular) <th><span class="sr-only">Acciones</span></th> @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($consumos as $c)
                    <tr>
                        <td>{{ $c->fecha_consumo->format('d/m H:i') }}</td>
                        <td>
                            {{ $c->descripcion }}
                            <span class="dato-secundario">{{ $c->tipo->etiqueta() }}</span>
                        </td>
                        <td>{{ $c->cantidad }}</td>
                        <td>{{ Moneda::formato($c->precio_unitario) }}</td>
                        <td><span class="precio">{{ Moneda::formato($c->importe()) }}</span></td>
                        @if ($anular)
                            <td>
                                <form method="POST" action="{{ route('recepcion.consumos.destroy', $c) }}"
                                      data-confirmar="¿Anular «{{ $c->descripcion }}» ({{ Moneda::formato($c->importe()) }})?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="boton-accion boton-peligro">Anular</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
