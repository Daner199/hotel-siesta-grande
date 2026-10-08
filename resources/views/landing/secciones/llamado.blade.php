{{-- Franja final antes del pie: invitación a reservar o escribir por WhatsApp --}}
<section class="llamado" aria-labelledby="titulo-llamado">
    <div class="contenedor llamado-contenido revelar">
        <h2 id="titulo-llamado">Tu próxima estadía en {{ $hotel['direccion']['departamento'] }} empieza aquí</h2>
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
