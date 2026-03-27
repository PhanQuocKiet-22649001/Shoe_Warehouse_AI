--
-- PostgreSQL database dump
--

\restrict gwudFpYBX1Z9BhmzR7P1Dv37SmUrXtkdh0c4s1JNjuCPaQuzcMaj2kESfOgfbCG

-- Dumped from database version 18.3
-- Dumped by pg_dump version 18.3

-- Started on 2026-03-27 17:14:53

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
-- TOC entry 5160 (class 0 OID 0)
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
-- TOC entry 5161 (class 0 OID 0)
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
-- TOC entry 5162 (class 0 OID 0)
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
-- TOC entry 5163 (class 0 OID 0)
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
-- TOC entry 5164 (class 0 OID 0)
-- Dependencies: 225
-- Name: product_variants_variant_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.product_variants_variant_id_seq OWNED BY public.product_variants.variant_id;


--
-- TOC entry 240 (class 1259 OID 16923)
-- Name: product_variants_variant_id_seq1; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.product_variants ALTER COLUMN variant_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.product_variants_variant_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


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
-- TOC entry 5165 (class 0 OID 0)
-- Dependencies: 223
-- Name: products_product_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.products_product_id_seq OWNED BY public.products.product_id;


--
-- TOC entry 239 (class 1259 OID 16922)
-- Name: products_product_id_seq1; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.products ALTER COLUMN product_id ADD GENERATED BY DEFAULT AS IDENTITY (
    SEQUENCE NAME public.products_product_id_seq1
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


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
-- TOC entry 5166 (class 0 OID 0)
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
-- TOC entry 5167 (class 0 OID 0)
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
-- TOC entry 5168 (class 0 OID 0)
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
-- TOC entry 5169 (class 0 OID 0)
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
-- TOC entry 4907 (class 2604 OID 16410)
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
-- TOC entry 4903 (class 2604 OID 16394)
-- Name: users user_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN user_id SET DEFAULT nextval('public.users_user_id_seq'::regclass);


--
-- TOC entry 5148 (class 0 OID 16620)
-- Dependencies: 234
-- Data for Name: ai_forecasts; Type: TABLE DATA; Schema: public; Owner: -
--



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

INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (1, 1, '40', 'White', false, '2026-03-24 03:06:38', 50, true, 'NK-AF1-WHT-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (2, 1, '41', 'White', false, '2026-03-24 03:06:38', 75, true, 'NK-AF1-WHT-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (3, 2, '40', 'Black/White', false, '2026-03-24 03:06:38', 50, true, 'AD-SMB-BW-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (4, 2, '41', 'Black/White', false, '2026-03-24 03:06:38', 75, true, 'AD-SMB-BW-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (5, 3, '40', 'Red/Black', false, '2026-03-24 03:06:38', 50, true, 'JR1-HB-RB-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (6, 4, '40', 'Black', false, '2026-03-24 03:06:38', 50, true, 'VN-OS-BLK-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (7, 5, '40', 'Beige', false, '2026-03-24 03:06:38', 50, true, 'CV-C70-BEI-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (8, 6, '40', 'Phantom White', false, '2026-03-26 11:58:02', 40, true, 'NK-BLZ-PW-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (9, 7, '40', 'White/Black', false, '2026-03-26 11:58:02', 120, true, 'AD-SS2-WB-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (10, 8, '40', 'Mocha/Black', false, '2026-03-26 11:58:02', 15, true, 'JR-TS-MB-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (11, 9, '39', 'Checkerboard', false, '2026-03-26 11:58:02', 60, true, 'VN-SLIP-CB-39');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (12, 10, '37', 'Black/White', false, '2026-03-26 11:58:02', 45, true, 'CV-MOVE-BW-37');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (13, 11, '40', 'Grey White', false, '2026-03-26 16:23:38', 5, true, 'NK-SAC-GW-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (14, 12, '40', 'Silver', false, '2026-03-26 16:33:36', 7, true, 'NI-REAX-SIL-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (15, 13, '41', 'Egret', false, '2026-03-26 16:40:50', 3, true, 'CO-AEON-EGR-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (18, 16, '0', 'Black', false, '2026-03-27 16:24:48.875483', 0, true, 'NI-NIK-');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (19, 14, '0', 'Black', false, '2026-03-27 16:27:22.101047', 0, true, 'NI-AIR-');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (20, 17, '0', 'Black', false, '2026-03-27 16:28:15.424559', 0, true, 'NI-JOR-');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (16, 14, '40', 'Black', false, '2026-03-27 16:05:52.782438', 180, true, 'NI-AIR-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (21, 18, '45', 'Đỏ', false, '2026-03-27 16:52:42.355351', 25, true, 'PU-PUM-45');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (22, 18, '41', 'Đỏ', false, '2026-03-27 16:53:43.876465', 1, true, 'PU-PUM-41');


--
-- TOC entry 5138 (class 0 OID 16431)
-- Dependencies: 224
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (1, 'Nike Air Force 1', 1, false, 2, '2026-03-24 03:06:38', 'nikeAF1.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (2, 'Adidas Samba OG', 2, false, 2, '2026-03-24 03:06:38', 'adidassambaog.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (3, 'Jordan 1 High', 3, false, 3, '2026-03-24 03:06:38', 'jordan1high.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (4, 'Vans Old Skool', 4, false, 3, '2026-03-24 03:06:38', 'van.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (5, 'Converse Chuck 70', 5, false, 2, '2026-03-24 03:06:38', 'converse.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (6, 'Nike Blazer Phantom Low', 1, false, 3, '2026-03-25 21:39:18', 'nike2.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (7, 'Adidas Superstar II', 2, false, 2, '2026-03-25 21:48:45', 'adidas2.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (8, 'Jordan 1 Retro Low Travis Scott ', 3, false, 3, '2026-03-25 22:47:39', 'jordan2.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (9, 'Vans checkerboard slip-on classic black/off white', 4, false, 3, '2026-03-25 22:51:11', 'vans2.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (10, 'Chuck Taylor All Star Move Platform', 5, false, 2, '2026-03-25 22:53:44', 'con2.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (11, 'Giày Sacai Nike LD Waffle', 1, false, 2, '2026-03-26 16:23:38', 'nike3.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (12, 'Nike ReactX Rejuven8 Spruce Aura Light Silver', 1, false, 2, '2026-03-26 16:33:36', '1774517616_nike4.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (13, 'Converse Aeon Active CX', 5, false, 2, '2026-03-26 16:40:50', '1774518050_con3.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (14, 'Air Jordan 1 Low OG SP Travis Scott "Black Phantom"', 1, false, 2, '2026-03-27 16:05:52.780484', '1774602334_69c6485ee3edf.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (15, 'Air Jordan 1 Low OG SP Travis Scott Black Phantom', 1, false, 2, '2026-03-27 16:06:40.125137', '1774602380_69c6488cc8d5c.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (16, 'Nike Travis Scott x Air Jordan 1 Low OG Black Phantom', 1, false, 2, '2026-03-27 16:24:48.873901', '1774603474_69c64cd29c20a.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (17, 'Jordan Air Jordan 1 Low OG SP Travis Scott Black Phantom', 1, false, 2, '2026-03-27 16:28:15.423059', '1774603681_69c64da14af30.jpg', true);
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status) VALUES (18, 'Puma Speedcat', 11, false, 2, '2026-03-27 16:52:42.353462', '1774605150_69c6535e31c4b.jpg', true);


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
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (1, 'admin', '$2y$10$VdzcwyYojuQCtGNWBD1HzOFLdBHknI34vFzXTtPe9yEheiZXytK/a', 'Phan Quốc Kiệt', 'MANAGER', true, '2026-03-24 03:06:38.013955', false, '0901234567', '123 Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (2, 'staff1', '$2y$10$CcWlT5FucIbXJFdbnDzHo.CVS5v1dEpeT3xDgDLgti7g9ztpA6Iry', 'Staff One', 'STAFF', true, '2026-03-24 03:06:38.013955', false, '0912345675', '685 Nguyễn Huệ, Quận Ninh Kiều, Cần Thơ');


--
-- TOC entry 5170 (class 0 OID 0)
-- Dependencies: 233
-- Name: ai_forecasts_forecast_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ai_forecasts_forecast_id_seq', 1, false);


--
-- TOC entry 5171 (class 0 OID 0)
-- Dependencies: 221
-- Name: categories_category_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categories_category_id_seq', 11, true);


--
-- TOC entry 5172 (class 0 OID 0)
-- Dependencies: 229
-- Name: inventory_inventory_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.inventory_inventory_id_seq', 1, false);


--
-- TOC entry 5173 (class 0 OID 0)
-- Dependencies: 231
-- Name: pending_imports_approval_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pending_imports_approval_id_seq', 2, true);


--
-- TOC entry 5174 (class 0 OID 0)
-- Dependencies: 225
-- Name: product_variants_variant_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.product_variants_variant_id_seq', 1, false);


--
-- TOC entry 5175 (class 0 OID 0)
-- Dependencies: 240
-- Name: product_variants_variant_id_seq1; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.product_variants_variant_id_seq1', 22, true);


--
-- TOC entry 5176 (class 0 OID 0)
-- Dependencies: 223
-- Name: products_product_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.products_product_id_seq', 13, true);


--
-- TOC entry 5177 (class 0 OID 0)
-- Dependencies: 239
-- Name: products_product_id_seq1; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.products_product_id_seq1', 18, true);


--
-- TOC entry 5178 (class 0 OID 0)
-- Dependencies: 227
-- Name: shelves_shelf_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.shelves_shelf_id_seq', 4, true);


--
-- TOC entry 5179 (class 0 OID 0)
-- Dependencies: 235
-- Name: system_logs_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.system_logs_log_id_seq', 3, true);


--
-- TOC entry 5180 (class 0 OID 0)
-- Dependencies: 237
-- Name: transactions_transaction_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.transactions_transaction_id_seq', 1, false);


--
-- TOC entry 5181 (class 0 OID 0)
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
-- TOC entry 4975 (class 2606 OID 17012)
-- Name: product_variants fk_variants_product; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES public.products(product_id) ON DELETE CASCADE;


-- Completed on 2026-03-27 17:14:53

--
-- PostgreSQL database dump complete
--

\unrestrict gwudFpYBX1Z9BhmzR7P1Dv37SmUrXtkdh0c4s1JNjuCPaQuzcMaj2kESfOgfbCG

