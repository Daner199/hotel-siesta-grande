-- Módulo 4 (Reservas): reglas de integridad en la base de datos.
--
-- 1. Una reserva es de un cliente O de una agencia (o de ninguno si la crea recepción), nunca de los dos.
-- 2. Una habitación no se puede reservar en fechas que se solapan, rango [entrada, salida):
--    si una reserva sale el 15, otra puede entrar el 15. Solo ocupan PENDIENTE, CONFIRMADA y CHECK_IN.
--    Tampoco se puede reservar en fechas bloqueadas (bloqueo_habitacion, mismo rango [desde, hasta)).
-- 3. No se puede bloquear una habitación en fechas que ya tienen reservas activas.
--
-- Los errores usan SQLSTATE 23P01 (exclusion_violation) para que Laravel los reconozca.
-- La fila de la habitación se bloquea (FOR UPDATE) para que dos reservas simultáneas no se cuelen.
--
-- Deshacer:
--   DROP TRIGGER trg_reserva_habitacion_sin_solape ON reserva_habitacion;
--   DROP TRIGGER trg_reserva_sin_solape ON reserva;
--   DROP TRIGGER trg_bloqueo_sin_reservas ON bloqueo_habitacion;
--   DROP FUNCTION fn_reserva_habitacion_sin_solape(), fn_reserva_sin_solape(), fn_bloqueo_sin_reservas(),
--                 fn_verificar_habitacion_libre(bigint, date, date, bigint);
--   DROP INDEX idx_reserva_habitacion_habitacion, idx_bloqueo_habitacion_fechas;
--   ALTER TABLE reserva DROP CONSTRAINT chk_reserva_cliente_o_agencia;

SET client_encoding = 'UTF8';

BEGIN;

-- 1. Cliente o agencia, nunca los dos
ALTER TABLE reserva
    ADD CONSTRAINT chk_reserva_cliente_o_agencia
    CHECK (NOT (cliente_id IS NOT NULL AND agencia_id IS NOT NULL));

-- Índices para que la búsqueda de solapamientos sea rápida
CREATE INDEX idx_reserva_habitacion_habitacion ON reserva_habitacion (habitacion_id);
CREATE INDEX idx_bloqueo_habitacion_fechas     ON bloqueo_habitacion (habitacion_id, fecha_desde, fecha_hasta);

-- 2. Revisa que una habitación esté libre en [entrada, salida), ignorando la propia reserva
CREATE FUNCTION fn_verificar_habitacion_libre(p_habitacion_id bigint, p_entrada date,
                                              p_salida date, p_reserva_id bigint)
RETURNS void
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
    v_numero varchar;
    v_codigo varchar;
    v_desde  date;
    v_hasta  date;
BEGIN
    -- Bloquea la habitación hasta el final de la transacción (evita reservas simultáneas)
    SELECT numero INTO v_numero FROM habitacion WHERE id = p_habitacion_id FOR UPDATE;

    SELECT r.codigo, r.fecha_entrada, r.fecha_salida INTO v_codigo, v_desde, v_hasta
    FROM reserva_habitacion rh
    JOIN reserva r         ON r.id = rh.reserva_id
    JOIN estado_reserva er ON er.id = r.estado_reserva_id
    WHERE rh.habitacion_id = p_habitacion_id
      AND r.id <> p_reserva_id
      AND er.nombre IN ('PENDIENTE', 'CONFIRMADA', 'CHECK_IN')
      AND r.fecha_entrada < p_salida
      AND r.fecha_salida  > p_entrada
    LIMIT 1;

    IF FOUND THEN
        RAISE EXCEPTION 'La habitación % ya está reservada del % al % (reserva %).',
            v_numero, to_char(v_desde, 'DD/MM/YYYY'), to_char(v_hasta, 'DD/MM/YYYY'), v_codigo
            USING ERRCODE = 'exclusion_violation';
    END IF;

    SELECT b.fecha_desde, b.fecha_hasta INTO v_desde, v_hasta
    FROM bloqueo_habitacion b
    WHERE b.habitacion_id = p_habitacion_id
      AND b.fecha_desde < p_salida
      AND b.fecha_hasta > p_entrada
    LIMIT 1;

    IF FOUND THEN
        RAISE EXCEPTION 'La habitación % está bloqueada del % al %.',
            v_numero, to_char(v_desde, 'DD/MM/YYYY'), to_char(v_hasta, 'DD/MM/YYYY')
            USING ERRCODE = 'exclusion_violation';
    END IF;
END;
$$;

-- Al agregar (o cambiar) una habitación de una reserva activa
CREATE FUNCTION fn_reserva_habitacion_sin_solape()
RETURNS trigger
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
    v_entrada date;
    v_salida  date;
    v_estado  varchar;
BEGIN
    SELECT r.fecha_entrada, r.fecha_salida, er.nombre INTO v_entrada, v_salida, v_estado
    FROM reserva r
    JOIN estado_reserva er ON er.id = r.estado_reserva_id
    WHERE r.id = NEW.reserva_id;

    IF v_estado IN ('PENDIENTE', 'CONFIRMADA', 'CHECK_IN') THEN
        PERFORM fn_verificar_habitacion_libre(NEW.habitacion_id, v_entrada, v_salida, NEW.reserva_id);
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_reserva_habitacion_sin_solape
    BEFORE INSERT OR UPDATE OF reserva_id, habitacion_id ON reserva_habitacion
    FOR EACH ROW EXECUTE FUNCTION fn_reserva_habitacion_sin_solape();

-- Al cambiar las fechas de una reserva o volver a activarla
CREATE FUNCTION fn_reserva_sin_solape()
RETURNS trigger
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
    v_activos   text[] := ARRAY['PENDIENTE', 'CONFIRMADA', 'CHECK_IN'];
    v_nuevo     varchar;
    v_anterior  varchar;
    v_habitacion bigint;
BEGIN
    SELECT nombre INTO v_nuevo    FROM estado_reserva WHERE id = NEW.estado_reserva_id;
    SELECT nombre INTO v_anterior FROM estado_reserva WHERE id = OLD.estado_reserva_id;

    -- Solo revisa si queda activa y antes no lo estaba, o si cambiaron sus fechas
    IF v_nuevo = ANY (v_activos)
       AND (NOT (v_anterior = ANY (v_activos))
            OR NEW.fecha_entrada <> OLD.fecha_entrada
            OR NEW.fecha_salida  <> OLD.fecha_salida) THEN
        FOR v_habitacion IN
            SELECT habitacion_id FROM reserva_habitacion WHERE reserva_id = NEW.id
        LOOP
            PERFORM fn_verificar_habitacion_libre(v_habitacion, NEW.fecha_entrada, NEW.fecha_salida, NEW.id);
        END LOOP;
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_reserva_sin_solape
    BEFORE UPDATE OF fecha_entrada, fecha_salida, estado_reserva_id ON reserva
    FOR EACH ROW EXECUTE FUNCTION fn_reserva_sin_solape();

-- 3. Un bloqueo no puede caer encima de reservas activas
CREATE FUNCTION fn_bloqueo_sin_reservas()
RETURNS trigger
LANGUAGE plpgsql
SET search_path = public
AS $$
DECLARE
    v_numero varchar;
    v_codigo varchar;
BEGIN
    SELECT numero INTO v_numero FROM habitacion WHERE id = NEW.habitacion_id FOR UPDATE;

    SELECT r.codigo INTO v_codigo
    FROM reserva_habitacion rh
    JOIN reserva r         ON r.id = rh.reserva_id
    JOIN estado_reserva er ON er.id = r.estado_reserva_id
    WHERE rh.habitacion_id = NEW.habitacion_id
      AND er.nombre IN ('PENDIENTE', 'CONFIRMADA', 'CHECK_IN')
      AND r.fecha_entrada < NEW.fecha_hasta
      AND r.fecha_salida  > NEW.fecha_desde
    LIMIT 1;

    IF FOUND THEN
        RAISE EXCEPTION 'No se puede bloquear la habitación %: tiene la reserva % en esas fechas.',
            v_numero, v_codigo
            USING ERRCODE = 'exclusion_violation';
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_bloqueo_sin_reservas
    BEFORE INSERT OR UPDATE OF habitacion_id, fecha_desde, fecha_hasta ON bloqueo_habitacion
    FOR EACH ROW EXECUTE FUNCTION fn_bloqueo_sin_reservas();

COMMIT;
