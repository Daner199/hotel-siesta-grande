<?php

/*
| RESPALDO de los datos del Hotel Siesta Grande.
| Los datos reales los edita el admin en "Datos del hotel" (tabla `hotel`).
| Las vistas NO leen este archivo directo: usan App\Models\Hotel::datos(), que toma la BD
| y solo usa estos valores si la tabla `hotel` no existe o está vacía.
| "desde", "habitaciones", "departamento", "pais" y "recepcion" salen siempre de aquí.
|
| Teléfonos en E.164 (igual que en la BD). Para mostrarlos: App\Support\Paises::formatearTelefono()
*/

return [

    'nombre'       => 'Hotel Siesta Grande',
    'eslogan'      => 'Descanso con alma cruceña',
    'desde'        => '2016-06-21',
    'habitaciones' => 60,

    // ---------- Ubicación ----------
    'direccion' => [
        'calle'        => 'Av. Monseñor Rivero N.º 245',
        'referencia'   => 'Entre 1.er y 2.º anillo, a cuatro cuadras de la Plaza 24 de Septiembre',
        'ciudad'       => 'Santa Cruz de la Sierra',
        'departamento' => 'Santa Cruz',
        'pais'         => 'Bolivia',
    ],

    // Para el mapa de la landing (zona de la Av. Monseñor Rivero)
    'mapa' => [
        'latitud'  => -17.7756,
        'longitud' => -63.1868,
    ],

    // ---------- Contacto ----------
    'telefono' => '+59133345678',  // fijo: (3) 334-5678
    'whatsapp' => '+59177012345',  // celular: 770 12345
    'correo'   => 'reservas@siestagrande.com',

    // ---------- Horarios ----------
    'check_in'  => '14:00',
    'check_out' => '12:00',
    'recepcion' => 'Las 24 horas',

    // ---------- Redes sociales ----------
    'redes' => [
        'facebook'  => 'https://www.facebook.com/hotelsiestagrande',
        'instagram' => 'https://www.instagram.com/hotelsiestagrande',
        'tiktok'    => 'https://www.tiktok.com/@hotelsiestagrande',
    ],

    // Las fotos (logo, portada, piscina...) NO van aquí: las sube el admin en "Datos del hotel"
    // y en la galería de cada tipo. Si falta una, la vista muestra un fondo elegante.

];
