--
-- PostgreSQL database dump
--

\restrict Y7Daz7prjwzh7RdV8C16hu08PVbx36N4ZVXirFdAxjVO7YrpqDtfJa4uMyfdK2A

-- Dumped from database version 18.3
-- Dumped by pg_dump version 18.3

-- Started on 2026-03-26 17:03:51

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
-- TOC entry 5 (class 2615 OID 2200)
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

-- *not* creating schema, since initdb creates it


SET default_table_access_method = heap;

--
-- TOC entry 234 (class 1259 OID 16620)
-- Name: ai_forecasts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_forecasts (
    forecast_id integer NOT NULL,
    variant_id integer NOT NULL,
    forecast_month date NOT NULL,
    predicted_demand integer,
    suggested_import integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ai_forecasts_predicted_demand_check CHECK ((predicted_demand >= 0)),
    CONSTRAINT ai_forecasts_suggested_import_check CHECK ((suggested_import >= 0))
);


--
-- TOC entry 233 (class 1259 OID 16619)
-- Name: ai_forecasts_forecast_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ai_forecasts_forecast_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5158 (class 0 OID 0)
-- Dependencies: 233
-- Name: ai_forecasts_forecast_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ai_forecasts_forecast_id_seq OWNED BY public.ai_forecasts.forecast_id;


--
-- TOC entry 222 (class 1259 OID 16407)
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    category_id integer NOT NULL,
    category_name character varying(100) NOT NULL,
    description text,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_deleted boolean DEFAULT false,
    logo character varying(255) DEFAULT NULL::character varying,
    status boolean DEFAULT true
);


--
-- TOC entry 221 (class 1259 OID 16406)
-- Name: categories_category_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_category_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5159 (class 0 OID 0)
-- Dependencies: 221
-- Name: categories_category_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.categories_category_id_seq OWNED BY public.categories.category_id;


--
-- TOC entry 230 (class 1259 OID 16505)
-- Name: inventory; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory (
    inventory_id integer NOT NULL,
    variant_id integer NOT NULL,
    shelf_id integer NOT NULL,
    quantity integer NOT NULL,
    last_updated_by integer,
    last_updated timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT inventory_quantity_check CHECK ((quantity >= 0))
);


--
-- TOC entry 229 (class 1259 OID 16504)
-- Name: inventory_inventory_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_inventory_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5160 (class 0 OID 0)
-- Dependencies: 229
-- Name: inventory_inventory_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.inventory_inventory_id_seq OWNED BY public.inventory.inventory_id;


--
-- TOC entry 232 (class 1259 OID 16535)
-- Name: pending_imports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pending_imports (
    approval_id integer NOT NULL,
    staff_id integer NOT NULL,
    image_url text,
    suggested_qty integer,
    ai_recognized jsonb,
    status character varying(20) DEFAULT 'PENDING'::character varying,
    manager_id integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pending_imports_status_check CHECK (((status)::text = ANY ((ARRAY['PENDING'::character varying, 'APPROVED'::character varying, 'REJECTED'::character varying])::text[]))),
    CONSTRAINT pending_imports_suggested_qty_check CHECK ((suggested_qty >= 0))
);


--
-- TOC entry 231 (class 1259 OID 16534)
-- Name: pending_imports_approval_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pending_imports_approval_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5161 (class 0 OID 0)
-- Dependencies: 231
-- Name: pending_imports_approval_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pending_imports_approval_id_seq OWNED BY public.pending_imports.approval_id;


--
-- TOC entry 226 (class 1259 OID 16461)
-- Name: product_variants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_variants (
    variant_id integer NOT NULL,
    product_id integer NOT NULL,
    size character varying(10) NOT NULL,
    color character varying(50) NOT NULL,
    is_deleted boolean DEFAULT false,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    stock integer DEFAULT 0,
    status boolean DEFAULT true,
    sku character varying(50)
);


--
-- TOC entry 225 (class 1259 OID 16460)
-- Name: product_variants_variant_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_variants_variant_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5162 (class 0 OID 0)
-- Dependencies: 225
-- Name: product_variants_variant_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_variants_variant_id_seq OWNED BY public.product_variants.variant_id;


--
-- TOC entry 224 (class 1259 OID 16431)
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    product_id integer NOT NULL,
    product_name character varying(150) NOT NULL,
    category_id integer NOT NULL,
    is_deleted boolean DEFAULT false,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    product_image character varying(255),
    status boolean DEFAULT true
);


--
-- TOC entry 223 (class 1259 OID 16430)
-- Name: products_product_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_product_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5163 (class 0 OID 0)
-- Dependencies: 223
-- Name: products_product_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_product_id_seq OWNED BY public.products.product_id;


--
-- TOC entry 228 (class 1259 OID 16486)
-- Name: shelves; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.shelves (
    shelf_id integer NOT NULL,
    location_code character varying(50) NOT NULL,
    description text,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- TOC entry 227 (class 1259 OID 16485)
-- Name: shelves_shelf_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.shelves_shelf_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5164 (class 0 OID 0)
-- Dependencies: 227
-- Name: shelves_shelf_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.shelves_shelf_id_seq OWNED BY public.shelves.shelf_id;


--
-- TOC entry 236 (class 1259 OID 16640)
-- Name: system_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_logs (
    log_id integer NOT NULL,
    user_id integer,
    action_type character varying(50) NOT NULL,
    table_affected character varying(50),
    details jsonb,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- TOC entry 235 (class 1259 OID 16639)
-- Name: system_logs_log_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_logs_log_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5165 (class 0 OID 0)
-- Dependencies: 235
-- Name: system_logs_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_logs_log_id_seq OWNED BY public.system_logs.log_id;


--
-- TOC entry 238 (class 1259 OID 16661)
-- Name: transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.transactions (
    transaction_id integer NOT NULL,
    transaction_type character varying(20) NOT NULL,
    variant_id integer NOT NULL,
    quantity integer NOT NULL,
    user_id integer,
    reference_id character varying(50),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT transactions_quantity_check CHECK ((quantity > 0)),
    CONSTRAINT transactions_transaction_type_check CHECK (((transaction_type)::text = ANY ((ARRAY['IMPORT'::character varying, 'EXPORT'::character varying])::text[])))
);


--
-- TOC entry 237 (class 1259 OID 16660)
-- Name: transactions_transaction_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.transactions_transaction_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5166 (class 0 OID 0)
-- Dependencies: 237
-- Name: transactions_transaction_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.transactions_transaction_id_seq OWNED BY public.transactions.transaction_id;


--
-- TOC entry 220 (class 1259 OID 16391)
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    user_id integer NOT NULL,
    username character varying(50) NOT NULL,
    password_hash character varying(255) NOT NULL,
    full_name character varying(100),
    role character varying(20) NOT NULL,
    status boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_deleted boolean DEFAULT false,
    phone_number character varying(20),
    address text,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['MANAGER'::character varying, 'STAFF'::character varying])::text[])))
);


--
-- TOC entry 219 (class 1259 OID 16390)
-- Name: users_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_user_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 5167 (class 0 OID 0)
-- Dependencies: 219
-- Name: users_user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_user_id_seq OWNED BY public.users.user_id;


--
-- TOC entry 4926 (class 2604 OID 16623)
-- Name: ai_forecasts forecast_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_forecasts ALTER COLUMN forecast_id SET DEFAULT nextval('public.ai_forecasts_forecast_id_seq'::regclass);


--
-- TOC entry 4905 (class 2604 OID 16410)
-- Name: categories category_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories ALTER COLUMN category_id SET DEFAULT nextval('public.categories_category_id_seq'::regclass);


--
-- TOC entry 4921 (class 2604 OID 16508)
-- Name: inventory inventory_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory ALTER COLUMN inventory_id SET DEFAULT nextval('public.inventory_inventory_id_seq'::regclass);


--
-- TOC entry 4923 (class 2604 OID 16538)
-- Name: pending_imports approval_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pending_imports ALTER COLUMN approval_id SET DEFAULT nextval('public.pending_imports_approval_id_seq'::regclass);


--
-- TOC entry 4914 (class 2604 OID 16464)
-- Name: product_variants variant_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants ALTER COLUMN variant_id SET DEFAULT nextval('public.product_variants_variant_id_seq'::regclass);


--
-- TOC entry 4910 (class 2604 OID 16434)
-- Name: products product_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products ALTER COLUMN product_id SET DEFAULT nextval('public.products_product_id_seq'::regclass);


--
-- TOC entry 4919 (class 2604 OID 16489)
-- Name: shelves shelf_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shelves ALTER COLUMN shelf_id SET DEFAULT nextval('public.shelves_shelf_id_seq'::regclass);


--
-- TOC entry 4928 (class 2604 OID 16643)
-- Name: system_logs log_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_logs ALTER COLUMN log_id SET DEFAULT nextval('public.system_logs_log_id_seq'::regclass);


--
-- TOC entry 4930 (class 2604 OID 16664)
-- Name: transactions transaction_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions ALTER COLUMN transaction_id SET DEFAULT nextval('public.transactions_transaction_id_seq'::regclass);


--
-- TOC entry 4901 (class 2604 OID 16394)
-- Name: users user_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN user_id SET DEFAULT nextval('public.users_user_id_seq'::regclass);


--
-- TOC entry 5148 (class 0 OID 16620)
-- Dependencies: 234
-- Data for Name: ai_forecasts; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.ai_forecasts (forecast_id, variant_id, forecast_month, predicted_demand, suggested_import, created_at) VALUES (1, 1, '2026-04-01', 60, 50, '2026-03-24 03:06:38.013955');
INSERT INTO public.ai_forecasts (forecast_id, variant_id, forecast_month, predicted_demand, suggested_import, created_at) VALUES (2, 3, '2026-04-01', 50, 45, '2026-03-24 03:06:38.013955');
INSERT INTO public.ai_forecasts (forecast_id, variant_id, forecast_month, predicted_demand, suggested_import, created_at) VALUES (3, 5, '2026-04-01', 45, 40, '2026-03-24 03:06:38.013955');


--
-- TOC entry 5136 (class 0 OID 16407)
-- Dependencies: 222
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (3, 'Jordan', 'Jordan brand shoes', 1, '2026-03-24 03:06:38.013955', false, 'logo_jordan.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (4, 'Vans', 'Vans brand shoes', 1, '2026-03-24 03:06:38.013955', false, 'logo_van.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (2, 'Adidas', 'Adidas brand shoes', 1, '2026-03-24 03:06:38.013955', false, 'logo_adidas.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (1, 'Nike', 'Nike brand shoes', 1, '2026-03-24 03:06:38.013955', false, 'logo_nike.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (11, 'Puma', NULL, 1, '2026-03-26 17:00:14.631238', false, 'puma_1774519214.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (5, 'Converse', 'Converse brand shoes', 1, '2026-03-24 03:06:38.013955', false, 'logo_converse.jpg', true);


--
-- TOC entry 5144 (class 0 OID 16505)
-- Dependencies: 230
-- Data for Name: inventory; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (1, 1, 1, 50, 2, '2026-03-24 03:06:38.013955');
INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (2, 2, 2, 40, 2, '2026-03-24 03:06:38.013955');
INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (3, 3, 1, 35, 3, '2026-03-24 03:06:38.013955');
INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (4, 4, 2, 30, 3, '2026-03-24 03:06:38.013955');
INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (5, 5, 3, 25, 2, '2026-03-24 03:06:38.013955');
INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (6, 6, 4, 20, 2, '2026-03-24 03:06:38.013955');
INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (7, 7, 3, 15, 3, '2026-03-24 03:06:38.013955');
INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (8, 8, 4, 10, 3, '2026-03-24 03:06:38.013955');
INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (9, 9, 1, 18, 2, '2026-03-24 03:06:38.013955');
INSERT INTO public.inventory (inventory_id, variant_id, shelf_id, quantity, last_updated_by, last_updated) VALUES (10, 10, 2, 12, 2, '2026-03-24 03:06:38.013955');


--
-- TOC entry 5146 (class 0 OID 16535)
-- Dependencies: 232
-- Data for Name: pending_imports; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.pending_imports (approval_id, staff_id, image_url, suggested_qty, ai_recognized, status, manager_id, created_at) VALUES (1, 2, '/uploads/nike_dunk.jpg', 20, '{"brand": "Nike", "model": "Dunk", "confidence": 0.95}', 'PENDING', 1, '2026-03-24 03:06:38.013955');
INSERT INTO public.pending_imports (approval_id, staff_id, image_url, suggested_qty, ai_recognized, status, manager_id, created_at) VALUES (2, 3, '/uploads/nike_dunk2.jpg', 15, '{"brand": "Nike", "model": "Dunk", "confidence": 0.95}', 'PENDING', 1, '2026-03-24 03:06:38.013955');


--
-- TOC entry 5140 (class 0 OID 16461)
-- Dependencies: 226
-- Data for Name: product_variants; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (1, 1, '40', 'White', false, '2026-03-24 03:06:38.013955', 50, true, 'NK-AF1-WHT-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (2, 1, '41', 'White', false, '2026-03-24 03:06:38.013955', 75, true, 'NK-AF1-WHT-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (3, 2, '40', 'Black/White', false, '2026-03-24 03:06:38.013955', 50, true, 'AD-SMB-BW-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (4, 2, '41', 'Black/White', false, '2026-03-24 03:06:38.013955', 75, true, 'AD-SMB-BW-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (5, 3, '40', 'Red/Black', false, '2026-03-24 03:06:38.013955', 50, true, 'JR1-HB-RB-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (6, 3, '41', 'Red/Black', false, '2026-03-24 03:06:38.013955', 75, true, 'JR1-HB-RB-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (7, 4, '40', 'Black', false, '2026-03-24 03:06:38.013955', 50, true, 'VN-OS-BLK-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (8, 4, '41', 'Black', false, '2026-03-24 03:06:38.013955', 75, true, 'VN-OS-BLK-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (9, 5, '40', 'Beige', false, '2026-03-24 03:06:38.013955', 50, true, 'CV-C70-BEI-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (10, 5, '41', 'Beige', false, '2026-03-24 03:06:38.013955', 75, true, 'CV-C70-BEI-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (11, 6, '40', 'Phantom White', false, '2026-03-26 11:58:02.015539', 40, true, 'NK-BLZ-PW-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (12, 6, '41', 'Phantom White', false, '2026-03-26 11:58:02.015539', 35, true, 'NK-BLZ-PW-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (13, 7, '40', 'White/Black', false, '2026-03-26 11:58:02.015539', 120, true, 'AD-SS2-WB-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (14, 7, '41', 'White/Black', false, '2026-03-26 11:58:02.015539', 90, true, 'AD-SS2-WB-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (15, 8, '40', 'Mocha/Black', false, '2026-03-26 11:58:02.015539', 15, true, 'JR-TS-MB-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (16, 8, '41', 'Mocha/Black', false, '2026-03-26 11:58:02.015539', 10, true, 'JR-TS-MB-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (17, 9, '39', 'Checkerboard', false, '2026-03-26 11:58:02.015539', 60, true, 'VN-SLIP-CB-39');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (18, 9, '40', 'Checkerboard', false, '2026-03-26 11:58:02.015539', 55, true, 'VN-SLIP-CB-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (19, 10, '37', 'Black/White', false, '2026-03-26 11:58:02.015539', 45, true, 'CV-MOVE-BW-37');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (20, 10, '38', 'Black/White', false, '2026-03-26 11:58:02.015539', 30, true, 'CV-MOVE-BW-38');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (21, 1, '42', 'White', false, '2026-03-26 12:31:53.415178', 30, true, 'NK-AF1-WHT-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (22, 1, '43', 'White', false, '2026-03-26 12:31:53.415178', 25, true, 'NK-AF1-WHT-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (23, 2, '42', 'Black/White', false, '2026-03-26 12:31:53.415178', 45, true, 'AD-SMB-BW-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (24, 2, '43', 'Black/White', false, '2026-03-26 12:31:53.415178', 20, true, 'AD-SMB-BW-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (25, 3, '42', 'Red/Black', false, '2026-03-26 12:31:53.415178', 12, true, 'JR1-HB-RB-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (26, 3, '43', 'Red/Black', false, '2026-03-26 12:31:53.415178', 8, true, 'JR1-HB-RB-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (27, 4, '42', 'Black', false, '2026-03-26 12:31:53.415178', 55, true, 'VN-OS-BLK-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (28, 4, '43', 'Black', false, '2026-03-26 12:31:53.415178', 40, true, 'VN-OS-BLK-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (29, 5, '42', 'Beige', false, '2026-03-26 12:31:53.415178', 35, true, 'CV-C70-BEI-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (30, 5, '43', 'Beige', false, '2026-03-26 12:31:53.415178', 22, true, 'CV-C70-BEI-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (31, 6, '42', 'Phantom White', false, '2026-03-26 12:31:53.415178', 15, true, 'NK-BLZ-PW-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (32, 6, '43', 'Phantom White', false, '2026-03-26 12:31:53.415178', 10, true, 'NK-BLZ-PW-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (33, 7, '42', 'White/Black', false, '2026-03-26 12:31:53.415178', 50, true, 'AD-SS2-WB-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (34, 7, '43', 'White/Black', false, '2026-03-26 12:31:53.415178', 30, true, 'AD-SS2-WB-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (35, 8, '42', 'Mocha/Black', false, '2026-03-26 12:31:53.415178', 5, true, 'JR-TS-MB-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (36, 8, '43', 'Mocha/Black', false, '2026-03-26 12:31:53.415178', 3, true, 'JR-TS-MB-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (37, 9, '41', 'Checkerboard', false, '2026-03-26 12:31:53.415178', 40, true, 'VN-SLIP-CB-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (38, 9, '42', 'Checkerboard', false, '2026-03-26 12:31:53.415178', 20, true, 'VN-SLIP-CB-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (39, 10, '39', 'Black/White', false, '2026-03-26 12:31:53.415178', 25, true, 'CV-MOVE-BW-39');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (40, 10, '40', 'Black/White', false, '2026-03-26 12:31:53.415178', 15, true, 'CV-MOVE-BW-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (41, 14, '40', 'xám trắng', false, '2026-03-26 16:23:38.654499', 5, true, 'NK-SNLW-XAM-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (42, 15, '40', 'Silver', false, '2026-03-26 16:33:36.390363', 7, true, 'NI-NRRSALS-SIL-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (43, 16, '41', 'Be/Kem (Egret)', false, '2026-03-26 16:40:50.950363', 3, true, 'CO-CAAC-BE/-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (44, 14, '41', 'xám trắng', false, '2026-03-26 16:44:41.858911', 12, true, 'NK-SNLW-XAM-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (45, 14, '42', 'xám trắng', false, '2026-03-26 16:44:41.858911', 8, true, 'NK-SNLW-XAM-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (46, 14, '43', 'xám trắng', false, '2026-03-26 16:44:41.858911', 5, true, 'NK-SNLW-XAM-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (47, 15, '41', 'Silver', false, '2026-03-26 16:44:41.858911', 10, true, 'NI-NRRSALS-SIL-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (48, 15, '42', 'Silver', false, '2026-03-26 16:44:41.858911', 15, true, 'NI-NRRSALS-SIL-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (49, 15, '43', 'Silver', false, '2026-03-26 16:44:41.858911', 6, true, 'NI-NRRSALS-SIL-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (50, 16, '39', 'Be/Kem (Egret)', false, '2026-03-26 16:44:41.858911', 5, true, 'CO-CAAC-BE/-39');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (51, 16, '40', 'Be/Kem (Egret)', false, '2026-03-26 16:44:41.858911', 12, true, 'CO-CAAC-BE/-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (52, 16, '42', 'Be/Kem (Egret)', false, '2026-03-26 16:44:41.858911', 7, true, 'CO-CAAC-BE/-42');


--
-- TOC entry 5138 (class 0 OID 16431)
-- Dependencies: 224
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (2, 'Adidas Samba OG', 2, false, 2, '2026-03-24 03:06:38.013955', 'adidassambaog.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (4, 'Vans Old Skool', 4, false, 3, '2026-03-24 03:06:38.013955', 'van.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (3, 'Jordan 1 High', 3, false, 3, '2026-03-24 03:06:38.013955', 'jordan1high.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (7, 'Adidas Superstar II', 2, false, 2, '2026-03-25 21:48:45.474736', 'adidas2.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (8, 'Jordan 1 Retro Low Travis Scott ', 3, false, 3, '2026-03-25 22:47:39.563783', 'jordan2.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (9, 'Vans checkerboard slip-on classic black/off white', 4, false, 3, '2026-03-25 22:51:11.79287', 'vans2.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (6, 'Nike Blazer Phantom Low', 1, false, 3, '2026-03-25 21:39:18.613878', 'nike2.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (1, 'Nike Air Force 1', 1, false, 2, '2026-03-24 03:06:38.013955', 'nikeAF1.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (14, 'Giày Sacai Nike LD Waffle', 1, false, 2, '2026-03-26 16:23:38.652615', 'nike3.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (15, 'Nike ReactX Rejuven8 Spruce Aura Light Silver', 1, false, 2, '2026-03-26 16:33:36.388985', '1774517616_nike4.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (5, 'Converse Chuck 70', 5, false, 2, '2026-03-24 03:06:38.013955', 'converse.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (16, 'Converse Aeon Active CX', 5, false, 2, '2026-03-26 16:40:50.947824', '1774518050_con3.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (10, 'Chuck Taylor All Star Move Platform', 5, false, 2, '2026-03-25 22:53:44.059421', 'con2.jpg', true);


--
-- TOC entry 5142 (class 0 OID 16486)
-- Dependencies: 228
-- Data for Name: shelves; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.shelves (shelf_id, location_code, description, created_by, created_at) VALUES (1, 'A-01', 'Kệ A tầng 01', 1, '2026-03-24 03:06:38.013955');
INSERT INTO public.shelves (shelf_id, location_code, description, created_by, created_at) VALUES (2, 'A-02', 'Kệ A tầng 02', 1, '2026-03-24 03:06:38.013955');
INSERT INTO public.shelves (shelf_id, location_code, description, created_by, created_at) VALUES (3, 'B-01', 'Kệ B tầng 01', 1, '2026-03-24 03:06:38.013955');
INSERT INTO public.shelves (shelf_id, location_code, description, created_by, created_at) VALUES (4, 'B-02', 'Kệ B tầng 02', 1, '2026-03-24 03:06:38.013955');


--
-- TOC entry 5150 (class 0 OID 16640)
-- Dependencies: 236
-- Data for Name: system_logs; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.system_logs (log_id, user_id, action_type, table_affected, details, created_at) VALUES (1, 1, 'LOGIN', 'users', '{"message": "Admin logged in"}', '2026-03-24 03:06:38.013955');
INSERT INTO public.system_logs (log_id, user_id, action_type, table_affected, details, created_at) VALUES (2, 2, 'INSERT', 'products', '{"product": "Nike Air Force 1 via AI Scan"}', '2026-03-24 03:06:38.013955');
INSERT INTO public.system_logs (log_id, user_id, action_type, table_affected, details, created_at) VALUES (3, 1, 'APPROVE', 'pending_imports', '{"status": "Manager approved Nike Dunk lot"}', '2026-03-24 03:06:38.013955');


--
-- TOC entry 5152 (class 0 OID 16661)
-- Dependencies: 238
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.transactions (transaction_id, transaction_type, variant_id, quantity, user_id, reference_id, created_at) VALUES (1, 'IMPORT', 1, 50, 2, 'PO_001', '2026-03-20 08:30:00');
INSERT INTO public.transactions (transaction_id, transaction_type, variant_id, quantity, user_id, reference_id, created_at) VALUES (2, 'IMPORT', 2, 40, 2, 'PO_001', '2026-03-20 09:15:00');
INSERT INTO public.transactions (transaction_id, transaction_type, variant_id, quantity, user_id, reference_id, created_at) VALUES (3, 'IMPORT', 3, 35, 3, 'PO_002', '2026-03-21 10:00:00');
INSERT INTO public.transactions (transaction_id, transaction_type, variant_id, quantity, user_id, reference_id, created_at) VALUES (4, 'EXPORT', 1, 5, 2, 'HD_001', '2026-03-23 14:00:00');
INSERT INTO public.transactions (transaction_id, transaction_type, variant_id, quantity, user_id, reference_id, created_at) VALUES (5, 'EXPORT', 3, 3, 2, 'HD_001', '2026-03-23 14:10:00');
INSERT INTO public.transactions (transaction_id, transaction_type, variant_id, quantity, user_id, reference_id, created_at) VALUES (6, 'EXPORT', 2, 4, 3, 'HD_002', '2026-03-24 09:30:00');
INSERT INTO public.transactions (transaction_id, transaction_type, variant_id, quantity, user_id, reference_id, created_at) VALUES (7, 'EXPORT', 5, 2, 3, 'HD_002', '2026-03-24 10:00:00');


--
-- TOC entry 5134 (class 0 OID 16391)
-- Dependencies: 220
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (3, 'staff2', '$2y$10$f86nxwquQB5QMKrWUfb1pudKTVmUwtZxi2ClDlvxtnbaWNQJw2ene', 'Staff Two', 'STAFF', false, '2026-03-24 03:06:38.013955', false, '0987654321', '789 Trần Hưng Đạo, Quận Sơn Trà, Đà Nẵng');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (10, 'kietpro', '$2y$10$LSkU2aKw6vyCyoeHyA15Pe.kP/JFhR32K8Xa4838MKapz/CMtLNwG', 'Phan Kiet', 'STAFF', true, '2026-03-25 00:13:42.994548', false, '0933445566', '10 Hoàng Diệu, Quận Ba Đình, Hà Nội');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (11, 'kietvip', '$2y$10$oBqkXk35nx9pEHZS.xWxz.kW90YjgXO5pv/BBJBMS5D3cDxOWqvSS', 'Quoc Kiet', 'STAFF', true, '2026-03-25 00:14:45.933997', true, '0944556677', '11 Bạch Đằng, Quận Hồng Bàng, Hải Phòng');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (12, 'kiet', '$2y$10$gxMB28Affm1TMJ.kUIKZ/uQEbj2ap2ldl6UtU6zDsLWPLifgV4oJ6', 'kiet123', 'STAFF', false, '2026-03-25 06:13:23.596002', false, '0955667788', '12 Quang Trung, TP. Đà Lạt, Lâm Đồng');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (13, 'phankiet123', '$2y$10$elCYTBuKYuG5HNnbFhWH8.SF9nNwWdTe9gwsdZUFnoYE7kqypPRpW', 'kietdz', 'STAFF', true, '2026-03-25 07:51:04.657108', false, '0966778899', '13 Nguyễn Trãi, Quận 5, TP.HCM');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (2, 'staff1', '$2y$10$f86nxwquQB5QMKrWUfb1pudKTVmUwtZxi2ClDlvxtnbaWNQJw2ene', 'Staff One', 'STAFF', true, '2026-03-24 03:06:38.013955', false, '0912345678', '456 Nguyễn Huệ, Quận Ninh Kiều, Cần Thơ');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (1, 'admin', '$2y$10$VdzcwyYojuQCtGNWBD1HzOFLdBHknI34vFzXTtPe9yEheiZXytK/a', 'Phan Quốc Kiệt', 'MANAGER', true, '2026-03-24 03:06:38.013955', false, '0901234567', '123 Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM');


--
-- TOC entry 5168 (class 0 OID 0)
-- Dependencies: 233
-- Name: ai_forecasts_forecast_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ai_forecasts_forecast_id_seq', 3, true);


--
-- TOC entry 5169 (class 0 OID 0)
-- Dependencies: 221
-- Name: categories_category_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categories_category_id_seq', 11, true);


--
-- TOC entry 5170 (class 0 OID 0)
-- Dependencies: 229
-- Name: inventory_inventory_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.inventory_inventory_id_seq', 10, true);


--
-- TOC entry 5171 (class 0 OID 0)
-- Dependencies: 231
-- Name: pending_imports_approval_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pending_imports_approval_id_seq', 2, true);


--
-- TOC entry 5172 (class 0 OID 0)
-- Dependencies: 225
-- Name: product_variants_variant_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.product_variants_variant_id_seq', 43, true);


--
-- TOC entry 5173 (class 0 OID 0)
-- Dependencies: 223
-- Name: products_product_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.products_product_id_seq', 16, true);


--
-- TOC entry 5174 (class 0 OID 0)
-- Dependencies: 227
-- Name: shelves_shelf_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.shelves_shelf_id_seq', 4, true);


--
-- TOC entry 5175 (class 0 OID 0)
-- Dependencies: 235
-- Name: system_logs_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.system_logs_log_id_seq', 3, true);


--
-- TOC entry 5176 (class 0 OID 0)
-- Dependencies: 237
-- Name: transactions_transaction_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.transactions_transaction_id_seq', 7, true);


--
-- TOC entry 5177 (class 0 OID 0)
-- Dependencies: 219
-- Name: users_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_user_id_seq', 13, true);


--
-- TOC entry 4965 (class 2606 OID 16631)
-- Name: ai_forecasts ai_forecasts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_forecasts
    ADD CONSTRAINT ai_forecasts_pkey PRIMARY KEY (forecast_id);


--
-- TOC entry 4945 (class 2606 OID 16419)
-- Name: categories categories_category_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_category_name_key UNIQUE (category_name);


--
-- TOC entry 4947 (class 2606 OID 16417)
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (category_id);


--
-- TOC entry 4959 (class 2606 OID 16516)
-- Name: inventory inventory_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT inventory_pkey PRIMARY KEY (inventory_id);


--
-- TOC entry 4963 (class 2606 OID 16548)
-- Name: pending_imports pending_imports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pending_imports
    ADD CONSTRAINT pending_imports_pkey PRIMARY KEY (approval_id);


--
-- TOC entry 4951 (class 2606 OID 16474)
-- Name: product_variants product_variants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_pkey PRIMARY KEY (variant_id);


--
-- TOC entry 4953 (class 2606 OID 16920)
-- Name: product_variants product_variants_sku_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_sku_key UNIQUE (sku);


--
-- TOC entry 4949 (class 2606 OID 16442)
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (product_id);


--
-- TOC entry 4955 (class 2606 OID 16498)
-- Name: shelves shelves_location_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shelves
    ADD CONSTRAINT shelves_location_code_key UNIQUE (location_code);


--
-- TOC entry 4957 (class 2606 OID 16496)
-- Name: shelves shelves_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shelves
    ADD CONSTRAINT shelves_pkey PRIMARY KEY (shelf_id);


--
-- TOC entry 4969 (class 2606 OID 16650)
-- Name: system_logs system_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_logs
    ADD CONSTRAINT system_logs_pkey PRIMARY KEY (log_id);


--
-- TOC entry 4971 (class 2606 OID 16673)
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (transaction_id);


--
-- TOC entry 4967 (class 2606 OID 16633)
-- Name: ai_forecasts unique_variant_month; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_forecasts
    ADD CONSTRAINT unique_variant_month UNIQUE (variant_id, forecast_month);


--
-- TOC entry 4961 (class 2606 OID 16518)
-- Name: inventory unique_variant_shelf; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT unique_variant_shelf UNIQUE (variant_id, shelf_id);


--
-- TOC entry 4941 (class 2606 OID 16403)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- TOC entry 4943 (class 2606 OID 16405)
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- TOC entry 4972 (class 2606 OID 16420)
-- Name: categories fk_categories_created_by; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT fk_categories_created_by FOREIGN KEY (created_by) REFERENCES public.users(user_id);


--
-- TOC entry 4982 (class 2606 OID 16634)
-- Name: ai_forecasts fk_forecasts_variant; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_forecasts
    ADD CONSTRAINT fk_forecasts_variant FOREIGN KEY (variant_id) REFERENCES public.product_variants(variant_id);


--
-- TOC entry 4977 (class 2606 OID 16524)
-- Name: inventory fk_inventory_shelf; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT fk_inventory_shelf FOREIGN KEY (shelf_id) REFERENCES public.shelves(shelf_id);


--
-- TOC entry 4978 (class 2606 OID 16529)
-- Name: inventory fk_inventory_updated_by; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT fk_inventory_updated_by FOREIGN KEY (last_updated_by) REFERENCES public.users(user_id);


--
-- TOC entry 4979 (class 2606 OID 16519)
-- Name: inventory fk_inventory_variant; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT fk_inventory_variant FOREIGN KEY (variant_id) REFERENCES public.product_variants(variant_id);


--
-- TOC entry 4983 (class 2606 OID 16651)
-- Name: system_logs fk_logs_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_logs
    ADD CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES public.users(user_id);


--
-- TOC entry 4980 (class 2606 OID 16554)
-- Name: pending_imports fk_pending_manager; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pending_imports
    ADD CONSTRAINT fk_pending_manager FOREIGN KEY (manager_id) REFERENCES public.users(user_id);


--
-- TOC entry 4981 (class 2606 OID 16549)
-- Name: pending_imports fk_pending_staff; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pending_imports
    ADD CONSTRAINT fk_pending_staff FOREIGN KEY (staff_id) REFERENCES public.users(user_id);


--
-- TOC entry 4973 (class 2606 OID 16445)
-- Name: products fk_products_category; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES public.categories(category_id);


--
-- TOC entry 4974 (class 2606 OID 16450)
-- Name: products fk_products_created_by; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT fk_products_created_by FOREIGN KEY (created_by) REFERENCES public.users(user_id);


--
-- TOC entry 4976 (class 2606 OID 16499)
-- Name: shelves fk_shelves_created_by; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shelves
    ADD CONSTRAINT fk_shelves_created_by FOREIGN KEY (created_by) REFERENCES public.users(user_id);


--
-- TOC entry 4984 (class 2606 OID 16679)
-- Name: transactions fk_transactions_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES public.users(user_id);


--
-- TOC entry 4985 (class 2606 OID 16674)
-- Name: transactions fk_transactions_variant; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT fk_transactions_variant FOREIGN KEY (variant_id) REFERENCES public.product_variants(variant_id);


--
-- TOC entry 4975 (class 2606 OID 16475)
-- Name: product_variants fk_variants_product; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES public.products(product_id);


-- Completed on 2026-03-26 17:03:51

--
-- PostgreSQL database dump complete
--

\unrestrict Y7Daz7prjwzh7RdV8C16hu08PVbx36N4ZVXirFdAxjVO7YrpqDtfJa4uMyfdK2A

