-- Módulo 1: el NIT de la agencia es obligatorio también en la BD
-- (Laravel ya lo exigía; así PostgreSQL protege la regla aunque se inserte por otro camino)
-- Deshacer: ALTER TABLE agencia ALTER COLUMN nit DROP NOT NULL;

BEGIN;

ALTER TABLE agencia ALTER COLUMN nit SET NOT NULL;

-- El "nit IS NULL OR ..." del CHECK ya no sirve: se vuelve a crear limpio
ALTER TABLE agencia DROP CONSTRAINT chk_agencia_nit;
ALTER TABLE agencia ADD CONSTRAINT chk_agencia_nit CHECK (nit ~ '^[0-9]{7,12}$');

COMMIT;
