-- Módulos 6 y 7: check-in, check-out y consumos.
--
-- 1. Hora y responsable del check-in y del check-out en la reserva.
-- 2. Coherencia: no hay check-out sin check-in, y la salida no es antes que la entrada.
-- 3. Solo se cargan consumos a reservas en CHECK_IN (huésped alojado).
-- 4. Índice para sumar rápido los consumos de una reserva.
--
-- Deshacer:
--   DROP TRIGGER trg_consumo_solo_alojado ON consumo;
--   DROP FUNCTION fn_consumo_solo_alojado();
--   DROP INDEX idx_consumo_reserva;
--   ALTER TABLE reserva DROP CONSTRAINT chk_reserva_check_in, DROP CONSTRAINT chk_reserva_check_out,
--     DROP CONSTRAINT chk_reserva_check_orden, DROP COLUMN check_in_en, DROP COLUMN check_in_por_usuario_id,
--     DROP COLUMN check_out_en, DROP COLUMN check_out_por_usuario_id;

SET client_encoding = 'UTF8';

BEGIN;

-- 1. Check-in y check-out
ALTER TABLE reserva
    ADD COLUMN check_in_en              timestamp,
    ADD COLUMN check_in_por_usuario_id  bigint,
    ADD COLUMN check_out_en             timestamp,
    ADD COLUMN check_out_por_usuario_id bigint,
    ADD CONSTRAINT fk_reserva_check_in_por FOREIGN KEY (check_in_por_usuario_id)
        REFERENCES usuario (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_reserva_check_out_por FOREIGN KEY (check_out_por_usuario_id)
        REFERENCES usuario (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    -- 2. Hora y responsable van juntos; no hay salida sin entrada; la salida no es antes
    ADD CONSTRAINT chk_reserva_check_in  CHECK ((check_in_en IS NULL) = (check_in_por_usuario_id IS NULL)),
    ADD CONSTRAINT chk_reserva_check_out CHECK ((check_out_en IS NULL) = (check_out_por_usuario_id IS NULL)
                                                AND (check_out_en IS NULL OR check_in_en IS NOT NULL)),
    ADD CONSTRAINT chk_reserva_check_orden CHECK (check_out_en IS NULL OR check_out_en >= check_in_en);

-- 3. Consumos solo para huéspedes alojados
CREATE FUNCTION fn_consumo_solo_alojado()
RETURNS trigger
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
    v_codigo varchar;
    v_estado varchar;
BEGIN
    SELECT r.codigo, er.nombre INTO v_codigo, v_estado
    FROM reserva r
    JOIN estado_reserva er ON er.id = r.estado_reserva_id
    WHERE r.id = NEW.reserva_id;

    IF v_estado IS DISTINCT FROM 'CHECK_IN' THEN
        RAISE EXCEPTION 'Solo se cargan consumos a huéspedes alojados (la reserva % está en %).', v_codigo, v_estado
            USING ERRCODE = 'check_violation';
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_consumo_solo_alojado
    BEFORE INSERT OR UPDATE OF reserva_id ON consumo
    FOR EACH ROW EXECUTE FUNCTION fn_consumo_solo_alojado();

-- 4. Índice
CREATE INDEX idx_consumo_reserva ON consumo (reserva_id);

COMMIT;
