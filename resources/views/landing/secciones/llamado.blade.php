{{-- Franja final antes del pie: invitación a reservar o escribir por WhatsApp --}}
<section class="llamado" aria-labelledby="titulo-llamado">
    <div class="contenedor llamado-contenido revelar">
        <div>
            <h2 id="titulo-llamado">Reservas</h2>
            <p>
                @if ($precioDesde) Desde {{ \App\Support\Moneda::formato($precioDesde) }} por noche · @endif
                Adelanto del {{ \App\Support\Disponibilidad::ADELANTO }} % · Cancelación sin costo hasta 48 h antes de la llegada
            </p>
        </div>
        <div class="llamado-botones">
            <a href="#disponibilidad" class="boton boton-tajibo">
                <i data-lucide="calendar-check"></i> Reservar
            </a>
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" class="boton boton-whatsapp" target="_blank" rel="noopener">
                    <i data-lucide="message-circle"></i> Escríbenos por WhatsApp
                </a>
            @endif
        </div>
    </div>
</section>
