-- ============================================================
-- HOTEL SIESTA GRANDE - Datos iniciales
-- Catálogos, tipos, 60 habitaciones, tarifas, datos del hotel y 3 usuarios de prueba
-- (admin, recepción y agencia; los clientes se registran desde /registro).
-- Va al final de instalar.sql (después de la estructura). Se ejecuta sobre una BD vacía.
-- ============================================================

SET client_encoding = 'UTF8';
SET search_path = public;

BEGIN;

-- ---------- Catálogos ----------
INSERT INTO estado_habitacion (id, nombre) VALUES
    (1, 'ACTIVA'), (2, 'MANTENIMIENTO'), (3, 'FUERA_SERVICIO');

INSERT INTO estado_reserva (id, nombre) VALUES
    (1, 'PENDIENTE'), (2, 'CONFIRMADA'), (3, 'CANCELADA'),
    (4, 'NO_SHOW'), (5, 'CHECK_IN'), (6, 'CHECK_OUT');

INSERT INTO metodo_pago (id, nombre) VALUES
    (1, 'EFECTIVO'), (2, 'QR'), (3, 'TARJETA');

INSERT INTO tipo_consumo (id, nombre) VALUES
    (1, 'RESTAURANTE'), (2, 'FRIGOBAR');

INSERT INTO beneficio (id, nombre, descripcion, activo) VALUES
    (1, 'DESAYUNO',         'Desayuno incluido',             true),
    (2, 'ALMUERZO',         'Almuerzo incluido',             true),
    (3, 'CENA',             'Cena incluida',                 true),
    (4, 'ACCESO A PISCINA', 'Acceso a la piscina del hotel', true);

-- ---------- Tipos de habitación ----------
INSERT INTO tipo_habitacion (id, nombre, descripcion, capacidad, activo) VALUES
    (1, 'SIMPLE',      'Habitación para una persona',  1, true),
    (2, 'DOBLE',       'Habitación para dos personas', 2, true),
    (3, 'MATRIMONIAL', 'Habitación matrimonial',       2, true),
    (4, 'SUITE',       'Habitación tipo suite',        4, true);

-- ---------- 60 habitaciones: 4 pisos × 15 ----------
-- x01–x05 SIMPLE · x06–x10 DOBLE · x11–x13 MATRIMONIAL · x14–x15 SUITE
INSERT INTO habitacion (numero, piso, tipo_habitacion_id, estado_habitacion_id)
SELECT (p.piso * 100 + n.pos)::text,
       p.piso,
       CASE WHEN n.pos <= 5 THEN 1 WHEN n.pos <= 10 THEN 2 WHEN n.pos <= 13 THEN 3 ELSE 4 END,
       1
FROM generate_series(1, 4) AS p(piso)
CROSS JOIN generate_series(1, 15) AS n(pos);

-- ---------- Tarifas iniciales (Bs por noche, sin fecha de fin) ----------
INSERT INTO tarifa_habitacion (tipo_habitacion_id, fecha_desde, fecha_hasta, precio_noche) VALUES
    (1, DATE '2026-10-07', NULL, 150.00),
    (2, DATE '2026-10-07', NULL, 250.00),
    (3, DATE '2026-10-07', NULL, 280.00),
    (4, DATE '2026-10-07', NULL, 450.00);

-- ---------- Datos del hotel (una fila; el admin los edita en "Datos del hotel") ----------
INSERT INTO hotel (id, nombre, eslogan, direccion, referencia, ciudad, telefono, whatsapp, correo,
                   facebook, instagram, tiktok, check_in, check_out, latitud, longitud)
VALUES (1, 'Hotel Siesta Grande', 'Descanso con alma cruceña', 'Av. Monseñor Rivero N.º 245',
        'Entre 1.er y 2.º anillo, a cuatro cuadras de la Plaza 24 de Septiembre',
        'Santa Cruz de la Sierra', '+59133345678', '+59177012345', 'reservas@siestagrande.com',
        'https://www.facebook.com/hotelsiestagrande', 'https://www.instagram.com/hotelsiestagrande',
        'https://www.tiktok.com/@hotelsiestagrande', '14:00', '12:00', -17.775600, -63.186800);

-- ---------- Usuarios de prueba (contraseñas con hash bcrypt de Laravel) ----------
--   admin@siestagrande.com     / Admin12345    ADMINISTRADOR
--   recepcion@siestagrande.com / Recepcion123  RECEPCIONISTA
--   agencia@siestagrande.com   / Agencia123    AGENCIA (contacto de "Viajes Bolivia")
-- Sin cliente de prueba: los clientes se registran solos desde /registro.
INSERT INTO usuario (nombre, apellido, telefono, email, password, rol, activo) VALUES
    ('Administrador', 'General', NULL, 'admin@siestagrande.com',
     '$2y$12$mrv6mCHdqOlwxxWF97bueeX6V/5AB4jyqalJLV6T22uXgabuVl69.', 'ADMINISTRADOR', true),
    ('Lucía', 'Rojas', NULL, 'recepcion@siestagrande.com',
     '$2y$12$50BH846rpqg/AogxtH2wBuUZ6PFraYD0/udlL607i5xjY9lHY568.', 'RECEPCIONISTA', true),
    ('Ana', 'Gutiérrez', NULL, 'agencia@siestagrande.com',
     '$2y$12$3q/DXoNO7Z3568iz6ua7m.uwYxosTd6gX6VnrSAcqakWXn583FP2i', 'AGENCIA', true);

INSERT INTO agencia (usuario_id, nombre, nit, telefono, activa)
SELECT id, 'Viajes Bolivia', '1020304050', NULL, true
FROM usuario WHERE email = 'agencia@siestagrande.com';

-- ---------- Secuencias: que el próximo id siga después de los cargados ----------
DO $$
DECLARE
    tabla text;
    secuencia text;
BEGIN
    FOREACH tabla IN ARRAY ARRAY['estado_habitacion', 'estado_reserva', 'metodo_pago', 'tipo_consumo',
                                 'beneficio', 'tipo_habitacion', 'habitacion', 'tarifa_habitacion',
                                 'usuario', 'agencia']
    LOOP
        secuencia := pg_get_serial_sequence('public.' || tabla, 'id');
        IF secuencia IS NOT NULL THEN
            EXECUTE format('SELECT setval(%L, (SELECT COALESCE(max(id), 0) + 1 FROM public.%I), false)',
                           secuencia, tabla);
        END IF;
    END LOOP;
END $$;

COMMIT;
