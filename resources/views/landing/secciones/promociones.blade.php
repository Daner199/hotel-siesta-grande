{{--
    Beneficios (tabla beneficio, activos) y promociones vigentes (tabla promocion) con sus
    beneficios y tipos. Si no hay ninguno de los dos, la sección no se muestra.
--}}
@use('App\Support\Moneda')

@if ($beneficios->isNotEmpty() || $promociones->isNotEmpty())
<section class="seccion seccion-oscura" id="promociones" aria-labelledby="titulo-promociones">
    <div class="contenedor">
        <header class="seccion-cabecera revelar">
            <p class="antetitulo">{{ $promociones->isNotEmpty() ? 'Promociones y beneficios' : 'Beneficios' }}</p>
            <h2 id="titulo-promociones" class="titulo-seccion">
                {{ $promociones->isNotEmpty() ? 'Más por tu estadía' : 'Suma beneficios a tu estadía' }}
            </h2>
        </header>

        @if ($promociones->isNotEmpty())
            <div class="promociones">
                @foreach ($promociones as $promo)
                    <article class="promocion revelar">
                        <div class="promocion-encabezado">
                            <h3>{{ $promo->nombre }}</h3>
                            @if ((float) $promo->porcentaje_descuento > 0)
                                <span class="promocion-descuento">
                                    −{{ rtrim(rtrim(number_format((float) $promo->porcentaje_descuento, 2, ',', '.'), '0'), ',') }} %
                                </span>
                            @endif
                        </div>

                        @if ($promo->descripcion)
                            <p>{{ $promo->descripcion }}</p>
                        @endif

                        <p class="promocion-vigencia">
                            <i data-lucide="calendar-days"></i>
                            @if ($promo->fecha_hasta)
                                Válida hasta el {{ $promo->fecha_hasta->format('d/m/Y') }}
                            @else
                                Vigente desde el {{ $promo->fecha_desde->format('d/m/Y') }}
                            @endif
                        </p>

                        @if ($promo->beneficios->isNotEmpty())
                            <ul class="chips">
                                @foreach ($promo->beneficios as $b)
                                    <li><i data-lucide="{{ $b->icono() }}"></i> {{ $b->etiqueta() }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="promocion-tipos">
                            @if ($promo->tipos->isEmpty())
                                <span>Aplica a todas las habitaciones</span>
                            @else
                                <span>Aplica a:</span>
                                <ul>
                                    @foreach ($promo->tipos as $t)
                                        <li>
                                            {{ $t->nombreVisible() }}
                                            @if ($t->pivot->precio_noche)
                                                · <strong>{{ Moneda::formato($t->pivot->precio_noche) }}</strong> por noche
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($beneficios->isNotEmpty())
            <ul class="beneficios revelar" aria-label="Beneficios disponibles">
                @foreach ($beneficios as $b)
                    <li class="beneficio">
                        <span class="beneficio-icono"><i data-lucide="{{ $b->icono() }}"></i></span>
                        <strong>{{ $b->etiqueta() }}</strong>
                        @if ($b->descripcion)
                            <span>{{ $b->descripcion }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
