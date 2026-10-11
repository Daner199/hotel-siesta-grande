-- Módulo 5 (Pagos): reglas de integridad de los pagos.
--
-- 1. Todo pago APROBADO o DEVUELTO tiene referencia (PAY-… de la API simulada o EFE-… en efectivo).
--    Los RECHAZADO se guardan como historial, sin referencia.
-- 2. Una referencia no se repite.
-- 3. Índice para sumar rápido los pagos de una reserva.
--
-- Deshacer:
--   ALTER TABLE pago DROP CONSTRAINT chk_pago_referencia;
--   DROP INDEX uq_pago_referencia, idx_pago_reserva;

SET client_encoding = 'UTF8';

BEGIN;

ALTER TABLE pago
    ADD CONSTRAINT chk_pago_referencia
    CHECK (estado NOT IN ('APROBADO', 'DEVUELTO') OR referencia IS NOT NULL);

CREATE UNIQUE INDEX uq_pago_referencia ON pago (referencia) WHERE referencia IS NOT NULL;
CREATE INDEX idx_pago_reserva ON pago (reserva_id);

COMMIT;
