-- ============================================================
-- HOTEL SIESTA GRANDE - Instalación completa de la base de datos
--
-- Crea TODA la estructura (tablas, restricciones, índices) y carga los datos iniciales
-- (catálogos, 4 tipos, 60 habitaciones, tarifas, datos del hotel y 3 usuarios de prueba: admin, recepción y agencia).
--
-- Uso (ver README): sobre una base VACÍA creada con ENCODING 'UTF8':
--   psql -U postgres -d hotel_siesta_grande -v ON_ERROR_STOP=1 -f database/sql/instalar.sql
--
-- NO usar "php artisan migrate": la BD de este proyecto se crea solo con este script.
-- Archivo generado: estructura = database/sql/estructura_actual.sql (sin las líneas
-- exclusivas de PostgreSQL 17/18) + database/sql/datos_iniciales.sql.
-- ============================================================

--
-- PostgreSQL database dump
--


-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: btree_gist; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS btree_gist WITH SCHEMA public;


--
-- Name: EXTENSION btree_gist; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION btree_gist IS 'support for indexing common datatypes in GiST';


--
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: agencia; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.agencia (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    nit character varying(50) NOT NULL,
    telefono character varying(30),
    activa boolean DEFAULT true NOT NULL,
    CONSTRAINT chk_agencia_nit CHECK (((nit)::text ~ '^[0-9]{7,12}$'::text)),
    CONSTRAINT chk_agencia_telefono CHECK (((telefono IS NULL) OR ((telefono)::text ~ '^\+[1-9][0-9]{6,14}$'::text)))
);


--
-- Name: agencia_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.agencia_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: agencia_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.agencia_id_seq OWNED BY public.agencia.id;


--
-- Name: beneficio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.beneficio (
    id bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: beneficio_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.beneficio_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: beneficio_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.beneficio_id_seq OWNED BY public.beneficio.id;


--
-- Name: bloqueo_habitacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.bloqueo_habitacion (
    id bigint NOT NULL,
    habitacion_id bigint NOT NULL,
    fecha_desde date NOT NULL,
    fecha_hasta date NOT NULL,
    motivo text NOT NULL,
    CONSTRAINT chk_bloqueo_fechas CHECK ((fecha_hasta > fecha_desde))
);


--
-- Name: bloqueo_habitacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.bloqueo_habitacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bloqueo_habitacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.bloqueo_habitacion_id_seq OWNED BY public.bloqueo_habitacion.id;


--
-- Name: comision_recepcionista; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.comision_recepcionista (
    id bigint NOT NULL,
    reserva_id bigint NOT NULL,
    recepcionista_id bigint NOT NULL,
    monto numeric(12,2) DEFAULT 5.00 NOT NULL,
    fecha_generacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    pagada boolean DEFAULT false NOT NULL,
    CONSTRAINT chk_comision_monto CHECK ((monto > (0)::numeric))
);


--
-- Name: comision_recepcionista_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.comision_recepcionista_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: comision_recepcionista_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.comision_recepcionista_id_seq OWNED BY public.comision_recepcionista.id;


--
-- Name: consumo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.consumo (
    id bigint NOT NULL,
    reserva_id bigint NOT NULL,
    tipo_consumo_id smallint NOT NULL,
    descripcion character varying(200) NOT NULL,
    cantidad integer NOT NULL,
    precio_unitario numeric(12,2) NOT NULL,
    fecha_consumo timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    registrado_por_usuario_id bigint NOT NULL,
    CONSTRAINT chk_consumo_cantidad CHECK ((cantidad > 0)),
    CONSTRAINT chk_consumo_precio CHECK ((precio_unitario > (0)::numeric))
);


--
-- Name: consumo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.consumo_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: consumo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.consumo_id_seq OWNED BY public.consumo.id;


--
-- Name: estado_habitacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estado_habitacion (
    id smallint NOT NULL,
    nombre character varying(30) NOT NULL
);


--
-- Name: estado_habitacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.estado_habitacion_id_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: estado_habitacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.estado_habitacion_id_seq OWNED BY public.estado_habitacion.id;


--
-- Name: estado_reserva; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estado_reserva (
    id smallint NOT NULL,
    nombre character varying(30) NOT NULL
);


--
-- Name: estado_reserva_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.estado_reserva_id_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: estado_reserva_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.estado_reserva_id_seq OWNED BY public.estado_reserva.id;


--
-- Name: foto_habitacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.foto_habitacion (
    id bigint NOT NULL,
    habitacion_id bigint NOT NULL,
    ruta character varying(255) NOT NULL,
    orden smallint DEFAULT 0 NOT NULL,
    es_principal boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_foto_habitacion_orden CHECK ((orden >= 0))
);


--
-- Name: foto_habitacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.foto_habitacion ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.foto_habitacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: foto_salon_evento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.foto_salon_evento (
    id bigint NOT NULL,
    salon_evento_id bigint NOT NULL,
    ruta character varying(255) NOT NULL,
    orden smallint DEFAULT 0 NOT NULL,
    es_principal boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_foto_salon_orden CHECK ((orden >= 0))
);


--
-- Name: foto_salon_evento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.foto_salon_evento ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.foto_salon_evento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: foto_tipo_habitacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.foto_tipo_habitacion (
    id bigint NOT NULL,
    tipo_habitacion_id bigint NOT NULL,
    ruta character varying(255) NOT NULL,
    orden smallint DEFAULT 0 NOT NULL,
    es_principal boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_foto_tipo_orden CHECK ((orden >= 0))
);


--
-- Name: foto_tipo_habitacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.foto_tipo_habitacion ALTER COLUMN id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.foto_tipo_habitacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: habitacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.habitacion (
    id bigint NOT NULL,
    numero character varying(10) NOT NULL,
    piso integer NOT NULL,
    tipo_habitacion_id bigint NOT NULL,
    estado_habitacion_id smallint NOT NULL,
    descripcion text,
    CONSTRAINT chk_habitacion_piso CHECK ((piso > 0))
);


--
-- Name: habitacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.habitacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: habitacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.habitacion_id_seq OWNED BY public.habitacion.id;


--
-- Name: hotel; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.hotel (
    id smallint DEFAULT 1 NOT NULL,
    nombre character varying(100) NOT NULL,
    eslogan character varying(150),
    direccion character varying(200) NOT NULL,
    referencia character varying(255),
    ciudad character varying(100) NOT NULL,
    telefono character varying(30),
    whatsapp character varying(30),
    correo character varying(150),
    facebook character varying(255),
    instagram character varying(255),
    tiktok character varying(255),
    check_in time without time zone NOT NULL,
    check_out time without time zone NOT NULL,
    latitud numeric(9,6),
    longitud numeric(9,6),
    logo character varying(255),
    portada character varying(255),
    foto_piscina character varying(255),
    foto_restaurante character varying(255),
    foto_fachada character varying(255),
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_hotel_latitud CHECK (((latitud IS NULL) OR ((latitud >= ('-90'::integer)::numeric) AND (latitud <= (90)::numeric)))),
    CONSTRAINT chk_hotel_longitud CHECK (((longitud IS NULL) OR ((longitud >= ('-180'::integer)::numeric) AND (longitud <= (180)::numeric)))),
    CONSTRAINT chk_hotel_telefono CHECK (((telefono IS NULL) OR ((telefono)::text ~ '^\+[1-9][0-9]{6,14}$'::text))),
    CONSTRAINT chk_hotel_ubicacion CHECK (((latitud IS NULL) = (longitud IS NULL))),
    CONSTRAINT chk_hotel_una_fila CHECK ((id = 1)),
    CONSTRAINT chk_hotel_whatsapp CHECK (((whatsapp IS NULL) OR ((whatsapp)::text ~ '^\+[1-9][0-9]{6,14}$'::text)))
);


--
-- Name: metodo_pago; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.metodo_pago (
    id smallint NOT NULL,
    nombre character varying(30) NOT NULL
);


--
-- Name: metodo_pago_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.metodo_pago_id_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: metodo_pago_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.metodo_pago_id_seq OWNED BY public.metodo_pago.id;


--
-- Name: pago; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pago (
    id bigint NOT NULL,
    reserva_id bigint NOT NULL,
    metodo_pago_id smallint NOT NULL,
    monto numeric(12,2) NOT NULL,
    fecha_pago timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    estado character varying(20) DEFAULT 'APROBADO'::character varying NOT NULL,
    referencia character varying(100),
    observacion text,
    registrado_por_usuario_id bigint,
    CONSTRAINT chk_pago_estado CHECK (((estado)::text = ANY ((ARRAY['PENDIENTE'::character varying, 'APROBADO'::character varying, 'RECHAZADO'::character varying, 'DEVUELTO'::character varying])::text[]))),
    CONSTRAINT chk_pago_monto CHECK ((monto > (0)::numeric))
);


--
-- Name: pago_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pago_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pago_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pago_id_seq OWNED BY public.pago.id;


--
-- Name: promocion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.promocion (
    id bigint NOT NULL,
    nombre character varying(150) NOT NULL,
    descripcion text,
    fecha_desde date NOT NULL,
    fecha_hasta date,
    porcentaje_descuento numeric(5,2) DEFAULT 0,
    activo boolean DEFAULT true NOT NULL,
    CONSTRAINT chk_promocion_descuento CHECK (((porcentaje_descuento >= (0)::numeric) AND (porcentaje_descuento <= (100)::numeric))),
    CONSTRAINT chk_promocion_fechas CHECK (((fecha_hasta IS NULL) OR (fecha_hasta > fecha_desde)))
);


--
-- Name: promocion_beneficio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.promocion_beneficio (
    promocion_id bigint NOT NULL,
    beneficio_id bigint NOT NULL
);


--
-- Name: promocion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.promocion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: promocion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.promocion_id_seq OWNED BY public.promocion.id;


--
-- Name: promocion_tipo_habitacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.promocion_tipo_habitacion (
    promocion_id bigint NOT NULL,
    tipo_habitacion_id bigint NOT NULL,
    precio_noche numeric(12,2),
    CONSTRAINT chk_promo_precio CHECK (((precio_noche IS NULL) OR (precio_noche > (0)::numeric)))
);


--
-- Name: reserva; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reserva (
    id bigint NOT NULL,
    codigo character varying(30) NOT NULL,
    cliente_id bigint,
    agencia_id bigint,
    creado_por_usuario_id bigint NOT NULL,
    estado_reserva_id smallint NOT NULL,
    nombre_titular character varying(200) NOT NULL,
    cantidad_personas integer NOT NULL,
    fecha_entrada date NOT NULL,
    fecha_salida date NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    observacion text,
    CONSTRAINT chk_reserva_fechas CHECK ((fecha_salida > fecha_entrada)),
    CONSTRAINT chk_reserva_personas CHECK ((cantidad_personas > 0))
);


--
-- Name: reserva_evento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reserva_evento (
    id bigint NOT NULL,
    salon_evento_id bigint NOT NULL,
    reserva_id bigint,
    usuario_id bigint,
    nombre_reservante character varying(200) NOT NULL,
    fecha_hora_inicio timestamp without time zone NOT NULL,
    duracion_horas numeric(6,2) NOT NULL,
    estado character varying(20) DEFAULT 'RESERVADO'::character varying NOT NULL,
    observacion text,
    CONSTRAINT chk_evento_duracion CHECK ((duracion_horas > (0)::numeric)),
    CONSTRAINT chk_evento_estado CHECK (((estado)::text = ANY ((ARRAY['RESERVADO'::character varying, 'CANCELADO'::character varying, 'FINALIZADO'::character varying])::text[])))
);


--
-- Name: reserva_evento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reserva_evento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reserva_evento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reserva_evento_id_seq OWNED BY public.reserva_evento.id;


--
-- Name: reserva_habitacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reserva_habitacion (
    id bigint NOT NULL,
    reserva_id bigint NOT NULL,
    habitacion_id bigint NOT NULL,
    promocion_id bigint,
    precio_noche_aplicado numeric(12,2) NOT NULL,
    CONSTRAINT chk_reserva_habitacion_precio CHECK ((precio_noche_aplicado > (0)::numeric))
);


--
-- Name: reserva_habitacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reserva_habitacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reserva_habitacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reserva_habitacion_id_seq OWNED BY public.reserva_habitacion.id;


--
-- Name: reserva_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reserva_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reserva_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reserva_id_seq OWNED BY public.reserva.id;


--
-- Name: salon_evento; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.salon_evento (
    id bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    capacidad integer NOT NULL,
    costo_hora numeric(12,2) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    CONSTRAINT chk_salon_capacidad CHECK ((capacidad > 0)),
    CONSTRAINT chk_salon_costo CHECK ((costo_hora > (0)::numeric))
);


--
-- Name: salon_evento_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.salon_evento_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: salon_evento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.salon_evento_id_seq OWNED BY public.salon_evento.id;


--
-- Name: tarifa_habitacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tarifa_habitacion (
    id bigint NOT NULL,
    tipo_habitacion_id bigint NOT NULL,
    fecha_desde date NOT NULL,
    fecha_hasta date,
    precio_noche numeric(12,2) NOT NULL,
    activa boolean DEFAULT true NOT NULL,
    CONSTRAINT chk_tarifa_fechas CHECK (((fecha_hasta IS NULL) OR (fecha_hasta > fecha_desde))),
    CONSTRAINT chk_tarifa_precio CHECK ((precio_noche > (0)::numeric))
);


--
-- Name: tarifa_habitacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tarifa_habitacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tarifa_habitacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tarifa_habitacion_id_seq OWNED BY public.tarifa_habitacion.id;


--
-- Name: tipo_consumo; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tipo_consumo (
    id smallint NOT NULL,
    nombre character varying(50) NOT NULL
);


--
-- Name: tipo_consumo_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tipo_consumo_id_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tipo_consumo_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tipo_consumo_id_seq OWNED BY public.tipo_consumo.id;


--
-- Name: tipo_habitacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tipo_habitacion (
    id bigint NOT NULL,
    nombre character varying(50) NOT NULL,
    descripcion text,
    capacidad integer NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    CONSTRAINT chk_tipo_habitacion_capacidad CHECK ((capacidad > 0))
);


--
-- Name: tipo_habitacion_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tipo_habitacion_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tipo_habitacion_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tipo_habitacion_id_seq OWNED BY public.tipo_habitacion.id;


--
-- Name: usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuario (
    id bigint NOT NULL,
    nombre character varying(100) NOT NULL,
    apellido character varying(100),
    telefono character varying(30),
    email character varying(150) NOT NULL,
    password character varying(255) NOT NULL,
    rol character varying(20) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    remember_token character varying(100),
    CONSTRAINT chk_usuario_rol CHECK (((rol)::text = ANY ((ARRAY['CLIENTE'::character varying, 'AGENCIA'::character varying, 'RECEPCIONISTA'::character varying, 'ADMINISTRADOR'::character varying])::text[]))),
    CONSTRAINT chk_usuario_telefono CHECK (((telefono IS NULL) OR ((telefono)::text ~ '^\+[1-9][0-9]{6,14}$'::text)))
);


--
-- Name: usuario_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usuario_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usuario_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usuario_id_seq OWNED BY public.usuario.id;


--
-- Name: agencia id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agencia ALTER COLUMN id SET DEFAULT nextval('public.agencia_id_seq'::regclass);


--
-- Name: beneficio id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.beneficio ALTER COLUMN id SET DEFAULT nextval('public.beneficio_id_seq'::regclass);


--
-- Name: bloqueo_habitacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bloqueo_habitacion ALTER COLUMN id SET DEFAULT nextval('public.bloqueo_habitacion_id_seq'::regclass);


--
-- Name: comision_recepcionista id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comision_recepcionista ALTER COLUMN id SET DEFAULT nextval('public.comision_recepcionista_id_seq'::regclass);


--
-- Name: consumo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consumo ALTER COLUMN id SET DEFAULT nextval('public.consumo_id_seq'::regclass);


--
-- Name: estado_habitacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estado_habitacion ALTER COLUMN id SET DEFAULT nextval('public.estado_habitacion_id_seq'::regclass);


--
-- Name: estado_reserva id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estado_reserva ALTER COLUMN id SET DEFAULT nextval('public.estado_reserva_id_seq'::regclass);


--
-- Name: habitacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.habitacion ALTER COLUMN id SET DEFAULT nextval('public.habitacion_id_seq'::regclass);


--
-- Name: metodo_pago id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodo_pago ALTER COLUMN id SET DEFAULT nextval('public.metodo_pago_id_seq'::regclass);


--
-- Name: pago id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago ALTER COLUMN id SET DEFAULT nextval('public.pago_id_seq'::regclass);


--
-- Name: promocion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promocion ALTER COLUMN id SET DEFAULT nextval('public.promocion_id_seq'::regclass);


--
-- Name: reserva id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva ALTER COLUMN id SET DEFAULT nextval('public.reserva_id_seq'::regclass);


--
-- Name: reserva_evento id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_evento ALTER COLUMN id SET DEFAULT nextval('public.reserva_evento_id_seq'::regclass);


--
-- Name: reserva_habitacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_habitacion ALTER COLUMN id SET DEFAULT nextval('public.reserva_habitacion_id_seq'::regclass);


--
-- Name: salon_evento id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salon_evento ALTER COLUMN id SET DEFAULT nextval('public.salon_evento_id_seq'::regclass);


--
-- Name: tarifa_habitacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tarifa_habitacion ALTER COLUMN id SET DEFAULT nextval('public.tarifa_habitacion_id_seq'::regclass);


--
-- Name: tipo_consumo id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_consumo ALTER COLUMN id SET DEFAULT nextval('public.tipo_consumo_id_seq'::regclass);


--
-- Name: tipo_habitacion id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_habitacion ALTER COLUMN id SET DEFAULT nextval('public.tipo_habitacion_id_seq'::regclass);


--
-- Name: usuario id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario ALTER COLUMN id SET DEFAULT nextval('public.usuario_id_seq'::regclass);


--
-- Name: agencia agencia_nit_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agencia
    ADD CONSTRAINT agencia_nit_key UNIQUE (nit);


--
-- Name: agencia agencia_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agencia
    ADD CONSTRAINT agencia_pkey PRIMARY KEY (id);


--
-- Name: agencia agencia_usuario_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agencia
    ADD CONSTRAINT agencia_usuario_id_key UNIQUE (usuario_id);


--
-- Name: beneficio beneficio_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.beneficio
    ADD CONSTRAINT beneficio_nombre_key UNIQUE (nombre);


--
-- Name: beneficio beneficio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.beneficio
    ADD CONSTRAINT beneficio_pkey PRIMARY KEY (id);


--
-- Name: bloqueo_habitacion bloqueo_habitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bloqueo_habitacion
    ADD CONSTRAINT bloqueo_habitacion_pkey PRIMARY KEY (id);


--
-- Name: comision_recepcionista comision_recepcionista_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comision_recepcionista
    ADD CONSTRAINT comision_recepcionista_pkey PRIMARY KEY (id);


--
-- Name: comision_recepcionista comision_recepcionista_reserva_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comision_recepcionista
    ADD CONSTRAINT comision_recepcionista_reserva_id_key UNIQUE (reserva_id);


--
-- Name: consumo consumo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consumo
    ADD CONSTRAINT consumo_pkey PRIMARY KEY (id);


--
-- Name: estado_habitacion estado_habitacion_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estado_habitacion
    ADD CONSTRAINT estado_habitacion_nombre_key UNIQUE (nombre);


--
-- Name: estado_habitacion estado_habitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estado_habitacion
    ADD CONSTRAINT estado_habitacion_pkey PRIMARY KEY (id);


--
-- Name: estado_reserva estado_reserva_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estado_reserva
    ADD CONSTRAINT estado_reserva_nombre_key UNIQUE (nombre);


--
-- Name: estado_reserva estado_reserva_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estado_reserva
    ADD CONSTRAINT estado_reserva_pkey PRIMARY KEY (id);


--
-- Name: foto_habitacion foto_habitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.foto_habitacion
    ADD CONSTRAINT foto_habitacion_pkey PRIMARY KEY (id);


--
-- Name: foto_salon_evento foto_salon_evento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.foto_salon_evento
    ADD CONSTRAINT foto_salon_evento_pkey PRIMARY KEY (id);


--
-- Name: foto_tipo_habitacion foto_tipo_habitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.foto_tipo_habitacion
    ADD CONSTRAINT foto_tipo_habitacion_pkey PRIMARY KEY (id);


--
-- Name: habitacion habitacion_numero_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.habitacion
    ADD CONSTRAINT habitacion_numero_key UNIQUE (numero);


--
-- Name: habitacion habitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.habitacion
    ADD CONSTRAINT habitacion_pkey PRIMARY KEY (id);


--
-- Name: hotel hotel_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.hotel
    ADD CONSTRAINT hotel_pkey PRIMARY KEY (id);


--
-- Name: metodo_pago metodo_pago_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodo_pago
    ADD CONSTRAINT metodo_pago_nombre_key UNIQUE (nombre);


--
-- Name: metodo_pago metodo_pago_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodo_pago
    ADD CONSTRAINT metodo_pago_pkey PRIMARY KEY (id);


--
-- Name: tarifa_habitacion no_solapamiento_tarifas; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tarifa_habitacion
    ADD CONSTRAINT no_solapamiento_tarifas EXCLUDE USING gist (tipo_habitacion_id WITH =, daterange(fecha_desde, COALESCE(fecha_hasta, '9999-12-31'::date), '[)'::text) WITH &&) WHERE ((activa = true));


--
-- Name: pago pago_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_pkey PRIMARY KEY (id);


--
-- Name: promocion_beneficio promocion_beneficio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promocion_beneficio
    ADD CONSTRAINT promocion_beneficio_pkey PRIMARY KEY (promocion_id, beneficio_id);


--
-- Name: promocion promocion_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promocion
    ADD CONSTRAINT promocion_nombre_key UNIQUE (nombre);


--
-- Name: promocion promocion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promocion
    ADD CONSTRAINT promocion_pkey PRIMARY KEY (id);


--
-- Name: promocion_tipo_habitacion promocion_tipo_habitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promocion_tipo_habitacion
    ADD CONSTRAINT promocion_tipo_habitacion_pkey PRIMARY KEY (promocion_id, tipo_habitacion_id);


--
-- Name: reserva reserva_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT reserva_codigo_key UNIQUE (codigo);


--
-- Name: reserva_evento reserva_evento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_evento
    ADD CONSTRAINT reserva_evento_pkey PRIMARY KEY (id);


--
-- Name: reserva_habitacion reserva_habitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_habitacion
    ADD CONSTRAINT reserva_habitacion_pkey PRIMARY KEY (id);


--
-- Name: reserva reserva_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT reserva_pkey PRIMARY KEY (id);


--
-- Name: salon_evento salon_evento_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salon_evento
    ADD CONSTRAINT salon_evento_nombre_key UNIQUE (nombre);


--
-- Name: salon_evento salon_evento_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salon_evento
    ADD CONSTRAINT salon_evento_pkey PRIMARY KEY (id);


--
-- Name: tarifa_habitacion tarifa_habitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tarifa_habitacion
    ADD CONSTRAINT tarifa_habitacion_pkey PRIMARY KEY (id);


--
-- Name: tipo_consumo tipo_consumo_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_consumo
    ADD CONSTRAINT tipo_consumo_nombre_key UNIQUE (nombre);


--
-- Name: tipo_consumo tipo_consumo_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_consumo
    ADD CONSTRAINT tipo_consumo_pkey PRIMARY KEY (id);


--
-- Name: tipo_habitacion tipo_habitacion_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_habitacion
    ADD CONSTRAINT tipo_habitacion_nombre_key UNIQUE (nombre);


--
-- Name: tipo_habitacion tipo_habitacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tipo_habitacion
    ADD CONSTRAINT tipo_habitacion_pkey PRIMARY KEY (id);


--
-- Name: foto_habitacion uq_foto_habitacion_ruta; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.foto_habitacion
    ADD CONSTRAINT uq_foto_habitacion_ruta UNIQUE (ruta);


--
-- Name: foto_salon_evento uq_foto_salon_ruta; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.foto_salon_evento
    ADD CONSTRAINT uq_foto_salon_ruta UNIQUE (ruta);


--
-- Name: foto_tipo_habitacion uq_foto_tipo_ruta; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.foto_tipo_habitacion
    ADD CONSTRAINT uq_foto_tipo_ruta UNIQUE (ruta);


--
-- Name: reserva_habitacion uq_reserva_habitacion; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_habitacion
    ADD CONSTRAINT uq_reserva_habitacion UNIQUE (reserva_id, habitacion_id);


--
-- Name: usuario usuario_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_email_key UNIQUE (email);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id);


--
-- Name: idx_foto_habitacion_orden; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_foto_habitacion_orden ON public.foto_habitacion USING btree (habitacion_id, orden);


--
-- Name: idx_foto_salon_orden; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_foto_salon_orden ON public.foto_salon_evento USING btree (salon_evento_id, orden);


--
-- Name: idx_foto_tipo_orden; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_foto_tipo_orden ON public.foto_tipo_habitacion USING btree (tipo_habitacion_id, orden);


--
-- Name: uq_foto_habitacion_principal; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_foto_habitacion_principal ON public.foto_habitacion USING btree (habitacion_id) WHERE es_principal;


--
-- Name: uq_foto_salon_principal; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_foto_salon_principal ON public.foto_salon_evento USING btree (salon_evento_id) WHERE es_principal;


--
-- Name: uq_foto_tipo_principal; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_foto_tipo_principal ON public.foto_tipo_habitacion USING btree (tipo_habitacion_id) WHERE es_principal;


--
-- Name: agencia fk_agencia_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.agencia
    ADD CONSTRAINT fk_agencia_usuario FOREIGN KEY (usuario_id) REFERENCES public.usuario(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: bloqueo_habitacion fk_bloqueo_habitacion; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.bloqueo_habitacion
    ADD CONSTRAINT fk_bloqueo_habitacion FOREIGN KEY (habitacion_id) REFERENCES public.habitacion(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: comision_recepcionista fk_comision_recepcionista; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comision_recepcionista
    ADD CONSTRAINT fk_comision_recepcionista FOREIGN KEY (recepcionista_id) REFERENCES public.usuario(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: comision_recepcionista fk_comision_reserva; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.comision_recepcionista
    ADD CONSTRAINT fk_comision_reserva FOREIGN KEY (reserva_id) REFERENCES public.reserva(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: consumo fk_consumo_reserva; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consumo
    ADD CONSTRAINT fk_consumo_reserva FOREIGN KEY (reserva_id) REFERENCES public.reserva(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: consumo fk_consumo_tipo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consumo
    ADD CONSTRAINT fk_consumo_tipo FOREIGN KEY (tipo_consumo_id) REFERENCES public.tipo_consumo(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: consumo fk_consumo_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consumo
    ADD CONSTRAINT fk_consumo_usuario FOREIGN KEY (registrado_por_usuario_id) REFERENCES public.usuario(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: reserva_evento fk_evento_reserva; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_evento
    ADD CONSTRAINT fk_evento_reserva FOREIGN KEY (reserva_id) REFERENCES public.reserva(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: reserva_evento fk_evento_salon; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_evento
    ADD CONSTRAINT fk_evento_salon FOREIGN KEY (salon_evento_id) REFERENCES public.salon_evento(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: reserva_evento fk_evento_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_evento
    ADD CONSTRAINT fk_evento_usuario FOREIGN KEY (usuario_id) REFERENCES public.usuario(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: foto_habitacion fk_foto_habitacion_habitacion; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.foto_habitacion
    ADD CONSTRAINT fk_foto_habitacion_habitacion FOREIGN KEY (habitacion_id) REFERENCES public.habitacion(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_salon_evento fk_foto_salon_salon; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.foto_salon_evento
    ADD CONSTRAINT fk_foto_salon_salon FOREIGN KEY (salon_evento_id) REFERENCES public.salon_evento(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: foto_tipo_habitacion fk_foto_tipo_tipo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.foto_tipo_habitacion
    ADD CONSTRAINT fk_foto_tipo_tipo FOREIGN KEY (tipo_habitacion_id) REFERENCES public.tipo_habitacion(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: habitacion fk_habitacion_estado; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.habitacion
    ADD CONSTRAINT fk_habitacion_estado FOREIGN KEY (estado_habitacion_id) REFERENCES public.estado_habitacion(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: habitacion fk_habitacion_tipo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.habitacion
    ADD CONSTRAINT fk_habitacion_tipo FOREIGN KEY (tipo_habitacion_id) REFERENCES public.tipo_habitacion(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: pago fk_pago_metodo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT fk_pago_metodo FOREIGN KEY (metodo_pago_id) REFERENCES public.metodo_pago(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: pago fk_pago_reserva; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT fk_pago_reserva FOREIGN KEY (reserva_id) REFERENCES public.reserva(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: pago fk_pago_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT fk_pago_usuario FOREIGN KEY (registrado_por_usuario_id) REFERENCES public.usuario(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: promocion_tipo_habitacion fk_promo_tipo_habitacion; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promocion_tipo_habitacion
    ADD CONSTRAINT fk_promo_tipo_habitacion FOREIGN KEY (tipo_habitacion_id) REFERENCES public.tipo_habitacion(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: promocion_tipo_habitacion fk_promo_tipo_promo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promocion_tipo_habitacion
    ADD CONSTRAINT fk_promo_tipo_promo FOREIGN KEY (promocion_id) REFERENCES public.promocion(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: promocion_beneficio fk_promocion_beneficio_beneficio; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promocion_beneficio
    ADD CONSTRAINT fk_promocion_beneficio_beneficio FOREIGN KEY (beneficio_id) REFERENCES public.beneficio(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: promocion_beneficio fk_promocion_beneficio_promocion; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.promocion_beneficio
    ADD CONSTRAINT fk_promocion_beneficio_promocion FOREIGN KEY (promocion_id) REFERENCES public.promocion(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: reserva fk_reserva_agencia; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT fk_reserva_agencia FOREIGN KEY (agencia_id) REFERENCES public.agencia(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: reserva fk_reserva_cliente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT fk_reserva_cliente FOREIGN KEY (cliente_id) REFERENCES public.usuario(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: reserva fk_reserva_creador; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT fk_reserva_creador FOREIGN KEY (creado_por_usuario_id) REFERENCES public.usuario(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: reserva fk_reserva_estado; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT fk_reserva_estado FOREIGN KEY (estado_reserva_id) REFERENCES public.estado_reserva(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: reserva_habitacion fk_reserva_habitacion_habitacion; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_habitacion
    ADD CONSTRAINT fk_reserva_habitacion_habitacion FOREIGN KEY (habitacion_id) REFERENCES public.habitacion(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: reserva_habitacion fk_reserva_habitacion_promocion; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_habitacion
    ADD CONSTRAINT fk_reserva_habitacion_promocion FOREIGN KEY (promocion_id) REFERENCES public.promocion(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: reserva_habitacion fk_reserva_habitacion_reserva; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_habitacion
    ADD CONSTRAINT fk_reserva_habitacion_reserva FOREIGN KEY (reserva_id) REFERENCES public.reserva(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: tarifa_habitacion fk_tarifa_tipo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tarifa_habitacion
    ADD CONSTRAINT fk_tarifa_tipo FOREIGN KEY (tipo_habitacion_id) REFERENCES public.tipo_habitacion(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--



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
