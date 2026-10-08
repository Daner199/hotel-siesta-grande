--
-- PostgreSQL database dump
--

\restrict P2A7is3klRUEWFd8dKXCc6NKIGVQrM2ixZX2WFgc5NJMvlhUtpPWbzXFi05OnSL

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
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

\unrestrict P2A7is3klRUEWFd8dKXCc6NKIGVQrM2ixZX2WFgc5NJMvlhUtpPWbzXFi05OnSL

