-- Módulo 2 (paso 2.1): datos iniciales de habitaciones y tarifas
--
-- 1. Corrige las tildes dañadas de tipo_habitacion ("Habitaci¢n" → "Habitación")
-- 2. Carga las 60 habitaciones: 4 pisos × 15
--      x01–x05 SIMPLE · x06–x10 DOBLE · x11–x13 MATRIMONIAL · x14–x15 SUITE
--      Total: 20 SIMPLE, 20 DOBLE, 12 MATRIMONIAL, 8 SUITE. Todas ACTIVAS.
-- 3. Carga la tarifa inicial de cada tipo, vigente desde el 07/10/2026 y sin fecha de fin
--
-- Se puede volver a ejecutar sin duplicar nada.
-- Deshacer (solo si todavía no hay reservas):
--   DELETE FROM tarifa_habitacion WHERE fecha_desde = '2026-10-07';
--   DELETE FROM habitacion;

SET client_encoding = 'UTF8';

BEGIN;

-- 1. Descripciones de los tipos
UPDATE tipo_habitacion SET descripcion = 'Habitación para una persona'  WHERE nombre = 'SIMPLE';
UPDATE tipo_habitacion SET descripcion = 'Habitación para dos personas' WHERE nombre = 'DOBLE';
UPDATE tipo_habitacion SET descripcion = 'Habitación matrimonial'       WHERE nombre = 'MATRIMONIAL';
UPDATE tipo_habitacion SET descripcion = 'Habitación tipo suite'        WHERE nombre = 'SUITE';

-- 2. Las 60 habitaciones (número = piso * 100 + posición)
INSERT INTO habitacion (numero, piso, tipo_habitacion_id, estado_habitacion_id)
SELECT
    (p.piso * 100 + n.pos)::text,
    p.piso,
    (SELECT id FROM tipo_habitacion WHERE nombre =
        CASE
            WHEN n.pos <= 5  THEN 'SIMPLE'
            WHEN n.pos <= 10 THEN 'DOBLE'
            WHEN n.pos <= 13 THEN 'MATRIMONIAL'
            ELSE 'SUITE'
        END),
    (SELECT id FROM estado_habitacion WHERE nombre = 'ACTIVA')
FROM generate_series(1, 4)  AS p(piso)
CROSS JOIN generate_series(1, 15) AS n(pos)
ON CONFLICT (numero) DO NOTHING;

-- 3. Tarifas iniciales (Bs por noche)
INSERT INTO tarifa_habitacion (tipo_habitacion_id, fecha_desde, fecha_hasta, precio_noche)
SELECT t.id, DATE '2026-10-07', NULL, v.precio
FROM (VALUES
        ('SIMPLE',      150.00),
        ('DOBLE',       250.00),
        ('MATRIMONIAL', 280.00),
        ('SUITE',       450.00)
     ) AS v(nombre, precio)
JOIN tipo_habitacion t ON t.nombre = v.nombre
WHERE NOT EXISTS (
    SELECT 1 FROM tarifa_habitacion th WHERE th.tipo_habitacion_id = t.id
);

COMMIT;
