-- Módulo 2 (paso 2.3): el piso de la habitación es obligatorio también en la BD
-- (requisito 9: cada habitación tiene número, piso, tipo y estado; Laravel ya lo exige)
-- Deshacer: ALTER TABLE habitacion ALTER COLUMN piso DROP NOT NULL;

SET client_encoding = 'UTF8';

BEGIN;

ALTER TABLE habitacion ALTER COLUMN piso SET NOT NULL;

-- El "piso IS NULL OR ..." del CHECK ya no sirve: se vuelve a crear limpio
ALTER TABLE habitacion DROP CONSTRAINT chk_habitacion_piso;
ALTER TABLE habitacion ADD CONSTRAINT chk_habitacion_piso CHECK (piso > 0);

COMMIT;
