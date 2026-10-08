-- Módulo 2 (paso 2.5): datos del hotel editables por el administrador
--
-- Tabla de UNA sola fila (CHECK id = 1). config/hotel.php queda como respaldo si falta un dato.
-- Las fotos (logo, portada, piscina, restaurante, fachada) se guardan como ruta en storage/app/public.
-- "Desde 21/06/2016" y "60 habitaciones" no están aquí: son datos fijos de los requisitos (config).
--
-- Deshacer: DROP TABLE hotel;

SET client_encoding = 'UTF8';

BEGIN;

CREATE TABLE hotel (
    id               smallint     PRIMARY KEY DEFAULT 1,
    nombre           varchar(100) NOT NULL,
    eslogan          varchar(150),
    direccion        varchar(200) NOT NULL,
    referencia       varchar(255),
    ciudad           varchar(100) NOT NULL,
    telefono         varchar(30),
    whatsapp         varchar(30),
    correo           varchar(150),
    facebook         varchar(255),
    instagram        varchar(255),
    tiktok           varchar(255),
    check_in         time         NOT NULL,
    check_out        time         NOT NULL,
    latitud          numeric(9,6),
    longitud         numeric(9,6),
    logo             varchar(255),
    portada          varchar(255),
    foto_piscina     varchar(255),
    foto_restaurante varchar(255),
    foto_fachada     varchar(255),
    updated_at       timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT chk_hotel_una_fila  CHECK (id = 1),
    CONSTRAINT chk_hotel_telefono  CHECK (telefono IS NULL OR telefono ~ '^\+[1-9][0-9]{6,14}$'),
    CONSTRAINT chk_hotel_whatsapp  CHECK (whatsapp IS NULL OR whatsapp ~ '^\+[1-9][0-9]{6,14}$'),
    CONSTRAINT chk_hotel_latitud   CHECK (latitud  IS NULL OR latitud  BETWEEN -90  AND 90),
    CONSTRAINT chk_hotel_longitud  CHECK (longitud IS NULL OR longitud BETWEEN -180 AND 180),
    CONSTRAINT chk_hotel_ubicacion CHECK ((latitud IS NULL) = (longitud IS NULL))
);

-- Fila inicial con los mismos datos de config/hotel.php
INSERT INTO hotel (id, nombre, eslogan, direccion, referencia, ciudad, telefono, whatsapp, correo,
                   facebook, instagram, tiktok, check_in, check_out, latitud, longitud)
VALUES (1,
        'Hotel Siesta Grande',
        'Descanso con alma cruceña',
        'Av. Monseñor Rivero N.º 245',
        'Entre 1.er y 2.º anillo, a cuatro cuadras de la Plaza 24 de Septiembre',
        'Santa Cruz de la Sierra',
        '+59133345678',
        '+59177012345',
        'reservas@siestagrande.com',
        'https://www.facebook.com/hotelsiestagrande',
        'https://www.instagram.com/hotelsiestagrande',
        'https://www.tiktok.com/@hotelsiestagrande',
        '14:00', '12:00',
        -17.775600, -63.186800)
ON CONFLICT (id) DO NOTHING;

COMMIT;
