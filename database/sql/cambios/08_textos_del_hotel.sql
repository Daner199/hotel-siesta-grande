-- Textos de la página pública y datos que antes estaban fijos en el código (config/hotel.php).
-- Ahora el admin los edita en "Datos del hotel" y la landing los lee de aquí.
--   historia, texto_piscina, texto_restaurante, texto_salon: opcionales (vacío = no se muestra)
--   recepcion, departamento, pais: obligatorios
-- "Desde 2016" sigue fijo en config/hotel.php (decisión de Daner).
--
-- Deshacer:
--   ALTER TABLE hotel DROP COLUMN historia, DROP COLUMN texto_piscina, DROP COLUMN texto_restaurante,
--     DROP COLUMN texto_salon, DROP COLUMN recepcion, DROP COLUMN departamento, DROP COLUMN pais;

SET client_encoding = 'UTF8';

BEGIN;

ALTER TABLE hotel
    ADD COLUMN historia          varchar(1500),
    ADD COLUMN texto_piscina     varchar(300),
    ADD COLUMN texto_restaurante varchar(300),
    ADD COLUMN texto_salon       varchar(300),
    ADD COLUMN recepcion         varchar(50),
    ADD COLUMN departamento      varchar(100),
    ADD COLUMN pais              varchar(100);

-- Los mismos textos que mostraba la landing hasta ahora
UPDATE hotel SET
    historia          = 'Desde entonces recibimos a quienes llegan por trabajo, de paseo o de paso, con habitaciones cómodas, piscina y restaurante en un mismo lugar.',
    texto_piscina     = 'Un respiro fresco para el calor cruceño, a pocos pasos de tu habitación.',
    texto_restaurante = 'Empieza el día con desayuno y termina con una buena cena sin salir del hotel.',
    texto_salon       = 'Para reuniones, celebraciones y eventos de empresa.',
    recepcion         = 'Las 24 horas',
    departamento      = 'Santa Cruz',
    pais              = 'Bolivia'
WHERE id = 1;

ALTER TABLE hotel
    ALTER COLUMN recepcion    SET NOT NULL,
    ALTER COLUMN departamento SET NOT NULL,
    ALTER COLUMN pais         SET NOT NULL;

COMMIT;
