--
-- PostgreSQL database dump
--

\restrict hXVeqSRJXjZ6p2Ze0kTA2S45ODjK944RS4ogEEKhtAL7SgqlLUPgeRed2K1JUfN

-- Dumped from database version 17.9
-- Dumped by pg_dump version 17.9

-- Started on 2026-03-31 17:13:56

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
-- TOC entry 6 (class 2615 OID 16719)
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

-- *not* creating schema, since initdb creates it


--
-- TOC entry 5280 (class 0 OID 0)
-- Dependencies: 6
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA public IS '';


--
-- TOC entry 2 (class 3079 OID 16720)
-- Name: vector; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS vector WITH SCHEMA public;


--
-- TOC entry 5281 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION vector; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION vector IS 'vector data type and ivfflat and hnsw access methods';


--
-- TOC entry 232 (class 1259 OID 17115)
-- Name: ai_forecasts_forecast_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ai_forecasts_forecast_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


SET default_table_access_method = heap;

--
-- TOC entry 233 (class 1259 OID 17116)
-- Name: ai_forecasts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_forecasts (
    forecast_id integer DEFAULT nextval('public.ai_forecasts_forecast_id_seq'::regclass) NOT NULL,
    variant_id integer NOT NULL,
    forecast_month date NOT NULL,
    predicted_demand integer,
    suggested_import integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ai_forecasts_predicted_demand_check CHECK ((predicted_demand >= 0)),
    CONSTRAINT ai_forecasts_suggested_import_check CHECK ((suggested_import >= 0))
);


--
-- TOC entry 220 (class 1259 OID 17059)
-- Name: categories_category_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.categories_category_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 221 (class 1259 OID 17060)
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    category_id integer DEFAULT nextval('public.categories_category_id_seq'::regclass) NOT NULL,
    category_name character varying(100) NOT NULL,
    description text,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_deleted boolean DEFAULT false,
    logo character varying(255) DEFAULT NULL::character varying,
    status boolean DEFAULT true
);


--
-- TOC entry 228 (class 1259 OID 17097)
-- Name: inventory_inventory_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.inventory_inventory_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 229 (class 1259 OID 17098)
-- Name: inventory; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.inventory (
    inventory_id integer DEFAULT nextval('public.inventory_inventory_id_seq'::regclass) NOT NULL,
    variant_id integer NOT NULL,
    shelf_id integer NOT NULL,
    quantity integer NOT NULL,
    last_updated_by integer,
    last_updated timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT inventory_quantity_check CHECK ((quantity >= 0))
);


--
-- TOC entry 230 (class 1259 OID 17104)
-- Name: pending_imports_approval_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pending_imports_approval_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 231 (class 1259 OID 17105)
-- Name: pending_imports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pending_imports (
    approval_id integer DEFAULT nextval('public.pending_imports_approval_id_seq'::regclass) NOT NULL,
    staff_id integer NOT NULL,
    image_url text,
    suggested_qty integer,
    ai_recognized jsonb,
    status character varying(20) DEFAULT 'PENDING'::character varying,
    manager_id integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pending_imports_status_check CHECK (((status)::text = ANY (ARRAY[('PENDING'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text]))),
    CONSTRAINT pending_imports_suggested_qty_check CHECK ((suggested_qty >= 0))
);


--
-- TOC entry 226 (class 1259 OID 17088)
-- Name: product_variants_variant_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.product_variants_variant_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 227 (class 1259 OID 17089)
-- Name: product_variants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.product_variants (
    variant_id integer DEFAULT nextval('public.product_variants_variant_id_seq'::regclass) NOT NULL,
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
-- TOC entry 224 (class 1259 OID 17078)
-- Name: products_product_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.products_product_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 225 (class 1259 OID 17079)
-- Name: products; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.products (
    product_id integer DEFAULT nextval('public.products_product_id_seq'::regclass) NOT NULL,
    product_name character varying(150) NOT NULL,
    category_id integer NOT NULL,
    is_deleted boolean DEFAULT false,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    product_image character varying(255),
    status boolean DEFAULT true,
    image_embedding public.vector(512)
);


--
-- TOC entry 222 (class 1259 OID 17070)
-- Name: shelves_shelf_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.shelves_shelf_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 223 (class 1259 OID 17071)
-- Name: shelves; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.shelves (
    shelf_id integer DEFAULT nextval('public.shelves_shelf_id_seq'::regclass) NOT NULL,
    location_code character varying(50) NOT NULL,
    description text,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- TOC entry 234 (class 1259 OID 17123)
-- Name: system_logs_log_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_logs_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 235 (class 1259 OID 17124)
-- Name: system_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_logs (
    log_id integer DEFAULT nextval('public.system_logs_log_id_seq'::regclass) NOT NULL,
    user_id integer,
    action_type character varying(50) NOT NULL,
    table_affected character varying(50),
    details jsonb,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- TOC entry 236 (class 1259 OID 17131)
-- Name: transactions_transaction_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.transactions_transaction_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 237 (class 1259 OID 17132)
-- Name: transactions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.transactions (
    transaction_id integer DEFAULT nextval('public.transactions_transaction_id_seq'::regclass) NOT NULL,
    transaction_type character varying(20) NOT NULL,
    variant_id integer NOT NULL,
    quantity integer NOT NULL,
    user_id integer,
    reference_id character varying(50),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT transactions_quantity_check CHECK ((quantity > 0)),
    CONSTRAINT transactions_transaction_type_check CHECK (((transaction_type)::text = ANY (ARRAY[('IMPORT'::character varying)::text, ('EXPORT'::character varying)::text])))
);


--
-- TOC entry 218 (class 1259 OID 17048)
-- Name: users_user_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 219 (class 1259 OID 17049)
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    user_id integer DEFAULT nextval('public.users_user_id_seq'::regclass) NOT NULL,
    username character varying(50) NOT NULL,
    password_hash character varying(255) NOT NULL,
    full_name character varying(100),
    role character varying(20) NOT NULL,
    status boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_deleted boolean DEFAULT false,
    phone_number character varying(20),
    address text,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY (ARRAY[('MANAGER'::character varying)::text, ('STAFF'::character varying)::text])))
);


--
-- TOC entry 5270 (class 0 OID 17116)
-- Dependencies: 233
-- Data for Name: ai_forecasts; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 5258 (class 0 OID 17060)
-- Dependencies: 221
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (1, 'Nike', 'Nike brand shoes', 1, '2026-03-31 13:12:46.232382', false, 'logo_nike.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (2, 'Adidas', 'Adidas brand shoes', 1, '2026-03-31 13:12:46.232382', false, 'logo_adidas.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (3, 'Jordan', 'Jordan brand shoes', 1, '2026-03-31 13:12:46.232382', false, 'logo_jordan.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (4, 'Vans', 'Vans brand shoes', 1, '2026-03-31 13:12:46.232382', false, 'logo_van.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (5, 'Converse', 'Converse brand shoes', 1, '2026-03-31 13:12:46.232382', false, 'logo_converse.jpg', true);
INSERT INTO public.categories (category_id, category_name, description, created_by, created_at, is_deleted, logo, status) VALUES (11, 'Puma', NULL, 1, '2026-03-31 13:12:46.232382', false, 'puma_1774519214.jpg', true);


--
-- TOC entry 5266 (class 0 OID 17098)
-- Dependencies: 229
-- Data for Name: inventory; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 5268 (class 0 OID 17105)
-- Dependencies: 231
-- Data for Name: pending_imports; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.pending_imports (approval_id, staff_id, image_url, suggested_qty, ai_recognized, status, manager_id, created_at) VALUES (1, 2, '/uploads/nike_dunk.jpg', 20, '{"brand": "Nike", "model": "Dunk", "confidence": 0.95}', 'PENDING', 1, '2026-03-31 13:12:46.232382');
INSERT INTO public.pending_imports (approval_id, staff_id, image_url, suggested_qty, ai_recognized, status, manager_id, created_at) VALUES (2, 3, '/uploads/nike_dunk2.jpg', 15, '{"brand": "Nike", "model": "Dunk", "confidence": 0.95}', 'PENDING', 1, '2026-03-31 13:12:46.232382');


--
-- TOC entry 5264 (class 0 OID 17089)
-- Dependencies: 227
-- Data for Name: product_variants; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (11, 12, '40', 'Trắng', false, '2026-03-31 16:04:36.31697', 25, true, 'NI-NIK-40');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (12, 13, '42', 'Đen', false, '2026-03-31 16:22:39.116108', 10, true, 'JO-AIR-42');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (13, 13, '41', 'Đen', false, '2026-03-31 16:23:22.849569', 15, true, 'JO-AIR-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (14, 12, '43', 'Trắng', false, '2026-03-31 16:51:52.554405', 40, true, 'NI-NIK-43');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (15, 14, '41', 'Black/white', false, '2026-03-31 16:57:26.959358', 10, true, 'CO-CHU-41');
INSERT INTO public.product_variants (variant_id, product_id, size, color, is_deleted, created_at, stock, status, sku) VALUES (16, 14, '43', 'Black/white', false, '2026-03-31 16:58:25.413736', 10, true, 'CO-CHU-43');


--
-- TOC entry 5262 (class 0 OID 17079)
-- Dependencies: 225
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status, image_embedding) VALUES (12, 'Nike Air Force 1', 1, false, 2, '2026-03-31 16:04:36.31697', '1774947859_69cb8e130c7cd.jpg', true, '[-0.30663544,0.08887126,0.29166284,-0.08957319,-0.39889795,0.29056317,-0.28958696,-0.026566334,0.30917203,0.020995252,-0.20172487,-0.13993666,-0.8995106,-0.22116522,-0.0451948,-0.24053231,0.84116906,0.041031756,0.043477662,-0.18873648,-0.41954154,-0.116871625,0.041044198,-0.4147766,0.061530482,0.18470605,0.31499964,0.03737785,0.2815166,-0.18440789,-0.17273659,-0.67854536,0.19550322,0.29747403,-0.13481912,0.024450883,0.22184645,0.3418899,-0.33313724,1.5364479,-0.2495675,-0.27331364,0.3051238,-0.115370885,0.64043784,0.16326669,0.15861599,-0.253103,0.31228402,-0.16557063,0.34613317,0.378611,0.34009507,-0.11045795,-0.22101071,0.16528061,-0.11387597,0.4091586,-0.4722082,0.42437494,0.5536777,-0.10935028,0.55733407,-0.09697351,-0.046321608,0.0119341565,0.215231,-0.25617093,-0.5269496,0.077928625,-0.5474339,-0.11204634,0.0615096,-0.2242813,-0.09269776,0.2852967,-0.14202623,-0.30743256,-0.013163293,-0.2004099,-0.20273052,0.10738744,0.055368904,-0.19695042,0.13907567,0.52682483,0.45046765,0.04114177,0.2955619,-0.22561166,-0.1901548,0.4458961,-4.7435923,0.12587778,-0.0317826,0.24445164,-0.15185615,0.075476795,-0.7025863,0.30957687,-0.2598154,-0.3382106,0.5361647,-0.03381076,-0.42846513,0.5491815,-0.6193546,-0.2808621,-0.21793856,0.43661895,-0.1223734,0.55773926,0.0026754907,-0.1538344,-0.5370786,0.04982354,-0.07466349,-0.15593323,0.27881098,-0.26010358,0.33344927,0.3677626,0.20487182,0.07901362,0.3155053,-0.56679726,-0.439564,-0.3520398,0.42563853,0.31468868,0.11738425,-0.0050501684,-0.07982816,0.73966813,0.050091334,0.22656977,-0.010977759,0.16453414,0.02830689,0.0046560513,-0.14454567,-0.12380937,-0.39699355,0.95801723,0.18159989,0.16923596,0.006300671,-0.093343504,0.6486325,0.12350967,0.31193238,0.33455458,1.1483134,0.050811425,0.22354932,-0.3701848,0.32425472,0.27770162,-0.06747301,0.31003764,-0.120819144,-0.4756313,0.04900479,0.062476695,0.17193979,0.17929417,0.3908754,-0.46300745,0.13306288,-0.09584938,-0.69080275,-0.46656317,0.04183106,0.101160735,0.02233436,-0.3380323,0.24419692,0.014391565,0.17446446,-0.36126766,0.8242885,0.081839286,0.13141444,0.41556895,-0.005050434,0.26442498,-0.16212967,0.5884816,-0.058764223,0.37401032,-0.34534827,0.025091356,0.20282736,0.23206015,0.85096025,0.36055696,0.10884258,-0.3286393,-0.41453907,0.42400226,-0.09616997,-0.23815766,0.4839264,-0.22129786,-0.2864831,0.1162426,-0.70576775,-0.42353258,-0.08831842,0.2071881,-0.027622197,0.66423804,0.04778348,0.041609645,-0.35785347,0.06433609,0.21314059,0.080957286,0.19835582,0.31157893,-0.30375198,-1.1514884,0.61721355,-1.9367784e-05,0.42372155,-0.2406516,0.37296277,0.16041774,0.07948515,0.027256737,-0.04888351,-0.20488873,0.11495143,0.6102277,0.063215956,0.016885795,-0.40906632,-0.21426228,0.15197146,-0.08155187,0.09712088,-0.26422018,0.49329692,0.035440173,-0.16371657,-0.32677948,-0.3966837,0.25434566,0.034979813,-0.21094792,-0.1543896,0.5118471,0.6389595,-0.6300546,0.15320025,0.17706898,0.088159196,0.3414944,-1.1639326,0.21036743,0.0031259619,0.3993936,-0.3615886,-0.16345815,-0.0068735788,0.13221171,-0.20469704,0.3147503,0.43675476,-0.33371907,-0.42681277,0.05757226,0.020054877,-0.19013268,0.21317114,0.13873942,-0.44281146,-0.51771903,-0.21065868,-0.14054587,0.6101006,0.24657717,-0.19352952,-0.3315666,-0.12210783,-0.33691403,1.7290064,-0.13504618,-0.26396313,0.11463795,-0.04853742,0.12949087,-0.32587823,0.3997402,-0.23684148,0.047296003,-0.013183011,-0.36931533,0.07694762,0.22389568,-0.3752244,0.21920666,-0.050181746,-0.33485132,0.24718119,-0.10718571,-0.42839018,0.32164627,0.27499595,-0.5999048,-0.15186243,0.25452352,0.7382937,0.08083542,0.13318394,-0.22866912,0.4491089,0.17519207,-0.03425295,0.088235825,-0.45922336,1.4977763,-0.106241085,-0.4782758,0.32337686,0.20681025,0.19197403,0.09358944,0.33668795,-0.31806204,-0.10793795,0.1299435,0.013579822,0.08358976,0.08198255,0.17475335,-0.2768778,0.014483084,-0.4026014,0.57021385,-0.44972935,-0.20406596,-0.067546174,0.7175743,0.5407734,-0.28474838,-0.08141313,-0.25549415,-0.102129474,-0.11642353,-0.054721393,0.19534965,-0.42828655,-0.24703635,-0.7004284,0.4809147,0.08767948,0.13205346,0.17937031,-0.0035783877,0.46260688,-0.1695134,-0.178696,-0.68922865,0.15963976,0.14166799,-0.088801615,0.14185688,0.034996614,-0.11045962,-0.00082085305,0.28464773,0.43420935,-0.0500794,-0.13326012,-0.3197949,0.14891616,0.012565899,-0.20712738,0.06037318,0.106721684,0.101691976,-0.41595215,0.0005999962,-0.006174567,-0.18917951,0.10713316,0.21468493,0.16778998,0.22462301,-0.63453776,-0.37322557,-0.14108445,-0.5370589,-0.5862822,0.19897784,0.07093409,-0.00065130927,0.027769994,0.44408977,0.12656336,-0.063584834,0.9616906,-0.95189065,-0.023708915,-0.5418728,-0.22507662,0.5628494,-0.27408376,-0.42571774,0.25041404,-0.10062085,0.6549125,-0.063237675,-0.52309126,0.3876024,-0.3331277,0.21029606,-0.26238874,0.004442993,0.35799536,0.22291912,0.6082999,0.81682134,-0.14623386,0.08107864,0.5181741,-0.11933821,-1.9983904,-0.17154868,-0.394534,-0.18249369,1.2715353,-0.075819016,-0.60879606,0.017123036,-0.5908288,0.5392831,0.92369753,0.24384159,-0.03789321,-0.2562589,0.15924406,0.13812639,0.026719905,-0.48852438,-0.23179097,-0.006198137,-0.19143704,-0.11970117,-0.59355474,0.52283305,0.2963585,0.24381576,-0.41669187,0.05854187,-0.24576083,-0.08690144,-0.075599164,-0.36659977,0.08637009,0.3211502,0.042970385,0.14613144,0.40415895,-0.070249796,0.2911114,0.063479014,-0.06562245,-0.14727402,-0.13778558,0.07774856,0.04142934,-0.5383445,0.5617908,-0.53024423,-0.077425934,0.1249454,-0.28575143,0.24150474,0.38314936,-0.38149384,0.4469106,-0.37050366,-0.12610993,0.18656951,0.493098,-0.07096352,-0.33473673,-0.69502616,0.33921242,-0.5957482,0.30616048,-0.16271602,-0.48132658,-0.50439286,0.27935773,-0.020025728,0.037826963,0.12279563,-0.31737235,0.095659226,-0.17509939,0.12876275,-0.21155447,-0.41960087,-0.5674651,0.2502203,0.12501295,0.101590425,0.2821365,0.47678792]');
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status, image_embedding) VALUES (13, 'Air Jordan 1 Retro Low OG SP Travis Scott Black Phantom', 3, false, 2, '2026-03-31 16:22:39.116108', '1774948928_69cb92407c373.jpg', true, '[-0.09347603,-0.18479557,0.33320782,0.1610509,-0.0043567484,-0.045334857,-0.28305888,0.19223227,0.13272145,0.05444493,0.10575629,0.045020096,-0.634873,-0.6306591,-0.14273898,0.0874227,0.64237845,-0.29179287,0.15955994,0.04225802,-0.27236146,-0.18029337,0.2801594,-0.026069488,0.41330385,0.19747783,-0.034461975,0.23714288,0.22969311,-0.29995096,0.08990726,-0.5951114,-0.1128975,0.2781337,-0.13510293,0.087836415,0.41187927,0.7196028,-0.3621984,1.3822265,-0.047101196,-0.1243071,0.2819265,-0.25012985,0.62563205,-0.8481757,0.10489959,-0.47428608,0.008519918,-0.3222629,0.119141765,0.3610567,0.23493534,0.012941269,-0.28677052,0.06238566,0.1453605,0.539297,-0.09699768,0.11620681,0.072958015,0.33555004,0.28237644,0.15150596,-0.08350052,-0.22513404,0.33870465,-0.22271127,-0.31103086,-0.2513573,-0.26661122,-0.43181697,-0.16431701,0.01298726,-0.15950838,-0.09636242,-0.029059682,-0.57464665,0.14763774,-0.037369747,-0.22604075,-0.13394475,-0.40331733,0.12595078,0.24984443,0.35199046,0.6221757,-0.05288544,0.4536756,-0.016450858,-0.14109811,0.4265209,-5.053361,0.585782,0.08509767,0.14937496,-0.23847656,-0.25513023,-0.5413573,0.6174574,-0.23766595,-0.5307714,0.2787752,-0.19213726,-0.24052916,0.64515024,-0.98585576,-0.14529261,-0.29654005,0.4443783,0.05512803,0.37377033,0.075365946,0.041981958,-0.2467803,-0.41445374,-0.13454893,-0.15539713,0.035002902,-0.28842747,0.097544216,0.05308399,-0.2783027,0.021136189,0.26428095,-0.3744144,-0.5287936,-0.10710201,0.17786497,-0.015996726,0.12385588,0.38801634,-0.27309856,0.72422576,-0.04948496,-0.09414301,0.021190133,0.3217178,-0.05836424,-0.17974333,0.0061546136,-0.21931046,-0.22672386,0.36790597,-0.11511196,0.25846574,0.059768006,0.039032087,0.50230724,0.36320758,-0.23102885,0.4126688,1.2177806,0.20058487,-0.05744794,0.109016836,0.29771748,0.22244874,0.2524627,-0.13895085,-0.44590876,-0.3643327,0.32498667,0.27012506,0.24369141,-0.13793628,0.061291907,-0.15509917,-0.01376473,-0.329539,-0.27683738,-0.5903788,0.1681176,0.07355215,-0.096589655,-0.71929985,0.33689666,0.06520963,-0.3025522,-0.38941342,0.88909084,-0.041248836,0.45799315,-0.10591552,0.015111279,0.22247203,0.08534843,0.42318824,0.053062495,-0.13803819,0.03325273,-0.090074554,0.20231776,0.60623497,0.8406706,0.23100826,-0.16774057,0.18456769,-0.24822211,0.48124754,-0.41339096,0.07518507,0.57235223,0.1027137,-0.5692932,-0.4508637,-0.59730244,-0.23162721,-0.15813744,0.3951825,-0.27829,0.067189515,0.22120523,-0.10099568,-0.71195906,0.25304803,-0.14925443,0.16703558,-0.023059689,-0.121508785,-0.3291292,-0.8362024,0.6974051,0.035484493,0.15845597,-0.24904662,0.15096216,-0.3549238,0.02907731,0.33628997,-0.57771266,-0.31991053,-0.32535386,0.5738366,0.17402288,0.2624217,-0.1140952,-0.011792232,0.05302146,-0.11948793,-0.05525419,-0.044338122,0.41890368,-0.016415525,0.09183957,-0.43246973,-0.097549416,0.06896706,-0.4021451,-0.18481515,0.059403963,-0.16174649,0.24003455,-0.41576025,0.089278534,0.3513057,0.40471372,-0.034360357,-1.0473014,-0.038979605,0.14182106,0.51868486,-0.20542708,-0.090893246,-0.18633965,0.39608163,0.10425687,0.10898021,-0.111672506,-0.3083793,-0.15059617,0.18657,0.50365984,-0.68379235,0.16477601,0.37602627,-0.40598327,-0.37661538,-0.20421611,-0.3918758,0.33564836,0.19605969,-0.6138989,-0.38336706,-0.035394184,-0.014751874,2.2126029,-0.16022418,0.013975648,-0.26098317,0.5006708,0.018561672,-0.2681761,0.5157458,-0.17678879,-0.059689473,0.042041212,-0.2611743,0.36170724,0.07534477,-0.5966182,0.1535416,0.2725231,0.017363071,0.5993654,-0.13610582,-0.2894641,0.24945371,0.27897394,-0.31386113,-0.2464946,0.127834,0.722136,0.20763129,0.024894673,0.095092565,0.13866806,0.07533218,-0.025481177,0.026338257,-0.07623025,1.5254557,-0.24493389,-0.5963005,0.4932461,0.3043117,0.0829073,0.2352024,-0.124308065,-0.20308049,-0.17120022,0.21621689,0.007719133,0.2824134,0.04290136,0.045359723,-0.43957496,-0.19519243,-0.28830013,0.18803042,-0.3650716,-0.3183802,0.09143135,0.30668232,0.71111435,-0.27010256,-0.28619182,-0.082795724,-0.32377723,-0.36955693,0.12763153,0.052555904,-0.4302405,-0.045250833,-0.016346224,0.31435096,-0.32549953,-0.007974808,0.1522212,-0.20426168,0.84853196,-0.375134,-0.27590227,-0.43870077,0.5903689,-0.0851721,-0.38036796,0.2791548,0.39084834,0.42161384,0.38591376,0.22927721,0.39221913,-0.027248563,-0.041841105,-0.1535071,0.34865877,0.023602102,0.13699198,0.08267628,-0.06027454,0.07621513,-0.29789045,-0.29541144,-0.2973832,0.23880999,0.24713118,0.2552826,-0.13495868,0.69294864,-0.65021014,-0.3353244,-0.19008905,-0.25959057,-0.7152915,0.36486405,0.05107725,-0.10101204,0.3417351,0.47912338,0.23146461,-0.0348554,0.8228533,-0.7059528,-0.20123704,0.34401006,-0.008355329,0.5542266,0.4414202,-0.24403012,0.2588667,0.063767515,0.28402627,-0.13759409,-0.38326558,0.4368412,0.3521497,0.32906362,-0.19115318,-0.103565075,0.48515424,0.83117586,0.28055143,0.75927377,-0.06524849,-0.18028,-0.12330268,0.09160146,-1.8749563,-0.2709461,-0.007868707,-0.030410357,1.0360947,-0.22170407,-0.19145434,-0.20438442,-0.29090774,0.3542444,0.39473188,0.22563289,0.09672864,0.3307383,0.24221176,0.04246922,0.3977516,-0.34355456,-0.09874076,0.15771973,-0.018315142,-0.09840031,-0.61473876,0.5449635,0.11170055,-0.1226312,-0.6693467,-0.096129276,-0.681993,0.00040689483,0.12417341,0.06810231,0.20755951,0.45224047,0.024429658,0.27141726,0.10399666,-0.16162142,0.26468676,0.19574611,-0.4060826,-0.16175139,-0.6919047,0.45201537,0.48939058,-0.46236795,0.45512757,-0.31355357,0.049528006,0.28899482,-0.21285705,0.19838658,0.26525342,-0.19255425,0.4260778,-0.09709193,-0.14367503,0.27994058,0.14746884,-0.21960153,-0.52633095,-0.7405252,0.27010775,-0.36326218,0.09350654,-0.16277838,-0.626741,-0.4150684,0.102686405,-0.3235398,0.1300831,-0.19165175,-0.0006727874,0.29299673,-0.2926738,0.08212944,0.030518934,-0.27129015,-0.7745424,-0.022443466,0.2049848,0.4852425,0.4052574,0.21239947]');
INSERT INTO public.products (product_id, product_name, category_id, is_deleted, created_by, created_at, product_image, status, image_embedding) VALUES (14, 'Chuck Taylor All Star Move Platform', 5, false, 2, '2026-03-31 16:57:26.959358', '1774950973_69cb9a3d10b3b.jpg', true, '[0.007630512,-0.2720652,0.8237691,0.1820648,-0.4001396,0.2905839,0.123253524,0.25148138,-0.10822865,0.062295333,-0.28789157,-0.15273583,-0.053586617,0.045911074,-0.51463974,-0.046629317,0.52573574,-0.43329528,0.42105785,0.13017446,-0.15195727,-0.07411664,0.17992242,-0.07977603,0.48881036,0.49267286,-0.24425434,0.33241776,0.14946316,-0.16836832,-0.16734278,-0.5089572,-0.023634046,0.41512713,0.046736903,-0.050353896,0.29632473,0.29960883,-0.1533243,1.29032,-0.2645807,-0.51053977,0.38558787,0.31316888,0.25195947,0.10814653,0.10418533,-0.2824405,0.32740864,0.45570374,-0.15615566,-0.07121517,0.3154019,-0.079235576,-0.27876738,-0.06511634,-0.0029437877,0.37157446,-0.2855625,-0.11215746,0.37467784,0.14824589,0.05601738,-0.07110934,-0.12219768,-0.1358492,-0.0087219905,-0.34288475,0.18487757,0.06789247,0.13824823,-0.31787127,-0.41549712,-0.01904419,0.016781986,-0.24972777,0.23923376,-0.67199934,-0.21033195,-0.17111906,-0.38786852,-0.37013945,-0.19877374,0.009654924,0.14438504,0.77636033,-0.12787443,-0.29987374,0.018134693,-0.49942634,0.11007073,0.24571735,-5.2954416,0.26503745,0.086408764,-0.16291495,-0.11713571,-0.068257846,-0.5820873,0.5085013,0.044351526,-0.45165247,0.35057938,-0.09217156,-0.36832148,0.375078,-0.12666117,-0.05617488,-0.22206953,0.2362647,0.04581827,0.36651164,-0.02256823,-0.17456241,-0.15784623,-0.21934833,-0.19532776,-0.26409453,-0.12645283,-0.5910788,-0.07681846,0.2504028,-0.23982276,-0.09378195,0.18652149,-0.16370484,-0.10048724,-0.064993605,0.12720057,-0.42254463,-0.03759547,0.0064019905,-0.32963946,0.7888115,0.5984081,0.3705105,0.34829038,0.8454553,-0.2876143,0.07983531,0.037380144,-0.22481328,-0.015733246,0.4629358,-0.30774307,0.36447263,-0.19346769,-0.0582185,0.4173003,0.091462925,0.14212775,0.47794443,1.0404772,-0.032244503,0.00056567136,-0.17266002,0.17680183,-0.050795935,0.08527329,0.22412822,-0.36510754,-0.12901793,-0.24575496,0.40992534,0.1481473,0.03463835,0.7503207,-0.5333792,-0.35919014,-0.29548618,-0.41440505,-0.10013071,0.121196486,0.0333428,-0.041388683,-0.23922148,0.5053485,0.014654001,0.25035203,-0.42324653,0.68203616,-0.20213634,0.49449623,0.25232172,-0.035625678,-0.09800549,-0.011384359,0.41795853,-0.39028168,0.45265576,-0.039182592,-0.08884931,-0.0359281,0.48733556,0.70961016,-0.35164142,0.09366094,0.05604556,0.10148389,0.5455487,-0.18990469,-0.5516898,0.9942967,0.0065453853,-0.036789857,-0.031448424,-0.73059374,-0.38298684,0.043710258,-0.018994464,-0.2900735,0.10375492,0.27670845,-0.072779745,-0.072441116,-0.03867206,0.089994304,0.54548603,-0.09622981,0.28057528,-0.19461623,-0.7700792,0.4513228,0.05341106,0.711584,-0.109644696,0.026631601,0.022516705,0.21854006,-0.00026510656,-0.4943889,-0.14646742,-0.03192509,0.21840657,0.5326706,-0.053023927,0.012033676,-0.022113003,0.15798047,0.009627052,0.36343947,0.38571224,0.1684851,-0.04672084,0.1439241,-0.06445221,0.24503878,-0.090037085,-0.0403871,0.41711283,0.010115321,0.29942852,0.3065277,-0.4697474,-0.09404212,0.19312952,0.2533224,0.059587657,-1.2364498,-0.09776123,0.013024541,0.43619642,0.054729477,-0.3260643,-0.029734936,-0.3405676,-0.07570173,0.119058184,0.0638857,0.18502662,-0.69244665,-0.148069,0.6167186,-0.66760117,0.34880662,0.13544962,-0.24985215,-0.26973498,-0.34379417,0.0777909,0.006692104,0.1224045,-0.7663213,-0.20532979,-0.03426214,-0.024388503,1.5372913,-0.15040623,0.028471248,-0.26676118,0.09673086,0.045666263,-0.29273632,0.040087067,-0.19294176,-0.19054072,-0.15402812,-0.2562018,-0.14065664,0.3090742,0.019850653,0.12188847,-0.11099674,0.3936203,0.42856705,-0.036355462,-0.15787445,0.1524876,-0.12439488,-0.40784064,-0.0642417,0.42449954,0.78756094,0.02145879,0.14213575,-0.11426853,0.13958807,0.31272823,-0.0029758103,0.64746034,-0.15830731,1.7414646,-0.39440444,-0.47850817,0.77513695,0.5715898,-0.22370309,0.38602605,0.29987413,-0.089184076,-0.069868356,-0.04150929,0.017723031,-0.10009204,-0.20081219,-0.04245334,-0.20795095,-0.055428166,-0.25345957,0.036773082,-0.5447915,-0.19611283,0.24994034,0.36519903,0.23683023,-0.078859895,-0.39284483,-0.05537196,0.17930539,-0.13729581,-0.1725548,-0.24728754,-0.045957245,-0.31071717,-0.23138727,0.81213576,0.21509416,0.49631855,0.27357918,-0.35092488,0.55447865,-0.47601494,0.15365772,-0.82409835,0.23589809,0.06872999,0.096963815,0.53370255,0.083685376,0.09269839,-0.053808197,0.19570558,0.51872176,-0.2713932,-0.017938275,-0.18012534,0.21509537,0.003633202,0.03965068,0.31079477,-0.02240709,-0.032135252,-0.29859793,-0.753189,0.05662001,-0.15293398,-0.013538779,-0.20634657,-0.2734,0.12802897,-0.50138015,-0.25423145,-0.003987622,-0.05523185,-0.3839124,0.012375571,-0.01718162,-0.31125504,0.63378274,0.045762442,-0.1449592,0.3762996,0.747982,-0.7737785,-0.29420087,0.102924615,0.002089752,0.5553365,-0.0012330227,0.092805125,0.1855398,-0.0011705365,0.4210205,-0.40777272,-0.5307082,0.30380797,0.060161036,0.05658564,-0.25597253,-0.19081338,0.6609577,0.6176582,0.56311685,0.5923072,0.0051842732,-0.02598266,-0.2641944,-0.03738186,-1.8280729,-0.06667286,-0.18455826,0.09365081,1.1155796,-0.10912921,-0.7949955,0.14307925,-0.47460705,0.60510725,0.2963306,0.17406046,0.05132577,0.2866716,0.5358328,0.5657635,0.42209315,-0.35022497,0.17384762,-0.27121556,0.11359938,-0.47566926,-0.25631687,0.8588905,0.32566538,0.09869296,-0.61126053,-0.32246387,-0.77595246,0.10691033,-0.12630875,-0.30304396,-0.19377498,-0.012971319,-0.16173115,0.532353,0.12888214,-0.16836968,0.3839371,-0.33250242,-0.33525237,-0.06790885,-0.48657656,0.121375546,-0.24591567,-0.6750032,0.7951526,-0.49813154,-0.1712292,0.384228,-0.30347103,0.35228652,-0.06280492,-0.4791275,0.5991521,-0.036815863,-0.025577236,0.115788,0.009306293,-0.3636249,-0.089423284,-0.47801593,0.19352084,-0.6630098,0.0136255305,-0.45336175,0.089004725,-0.24889313,-0.13093944,0.047603168,-0.013179397,0.059463978,0.1872162,0.4056422,0.12610133,0.14329594,-0.30436295,0.42886057,-0.62121785,0.44790804,-0.041758258,0.06595267,-0.039729297,0.54075193]');


--
-- TOC entry 5260 (class 0 OID 17071)
-- Dependencies: 223
-- Data for Name: shelves; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.shelves (shelf_id, location_code, description, created_by, created_at) VALUES (1, 'A-01', 'Kệ A tầng 01', 1, '2026-03-31 13:12:46.232382');
INSERT INTO public.shelves (shelf_id, location_code, description, created_by, created_at) VALUES (2, 'A-02', 'Kệ A tầng 02', 1, '2026-03-31 13:12:46.232382');
INSERT INTO public.shelves (shelf_id, location_code, description, created_by, created_at) VALUES (3, 'B-01', 'Kệ B tầng 01', 1, '2026-03-31 13:12:46.232382');
INSERT INTO public.shelves (shelf_id, location_code, description, created_by, created_at) VALUES (4, 'B-02', 'Kệ B tầng 02', 1, '2026-03-31 13:12:46.232382');


--
-- TOC entry 5272 (class 0 OID 17124)
-- Dependencies: 235
-- Data for Name: system_logs; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.system_logs (log_id, user_id, action_type, table_affected, details, created_at) VALUES (1, 1, 'LOGIN', 'users', '{"message": "Admin logged in"}', '2026-03-31 13:12:46.232382');
INSERT INTO public.system_logs (log_id, user_id, action_type, table_affected, details, created_at) VALUES (2, 2, 'INSERT', 'products', '{"product": "Nike Air Force 1 via AI Scan"}', '2026-03-31 13:12:46.232382');
INSERT INTO public.system_logs (log_id, user_id, action_type, table_affected, details, created_at) VALUES (3, 1, 'APPROVE', 'pending_imports', '{"status": "Manager approved Nike Dunk lot"}', '2026-03-31 13:12:46.232382');


--
-- TOC entry 5274 (class 0 OID 17132)
-- Dependencies: 237
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: -
--



--
-- TOC entry 5256 (class 0 OID 17049)
-- Dependencies: 219
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (1, 'admin', '$2y$10$VdzcwyYojuQCtGNWBD1HzOFLdBHknI34vFzXTtPe9yEheiZXytK/a', 'Phan Quốc Kiệt', 'MANAGER', true, '2026-03-31 13:12:46.232382', false, '0901234567', '123 Lê Lợi, Phường Bến Thành, Quận 1, TP.HCM');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (3, 'staff2', '$2y$10$f86nxwquQB5QMKrWUfb1pudKTVmUwtZxi2ClDlvxtnbaWNQJw2ene', 'Staff Two', 'STAFF', false, '2026-03-31 13:12:46.232382', false, '0987654321', '789 Trần Hưng Đạo, Quận Sơn Trà, Đà Nẵng');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (10, 'kietpro', '$2y$10$LSkU2aKw6vyCyoeHyA15Pe.kP/JFhR32K8Xa4838MKapz/CMtLNwG', 'Phan Kiet', 'STAFF', true, '2026-03-31 13:12:46.232382', false, '0933445566', '10 Hoàng Diệu, Quận Ba Đình, Hà Nội');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (11, 'kietvip', '$2y$10$oBqkXk35nx9pEHZS.xWxz.kW90YjgXO5pv/BBJBMS5D3cDxOWqvSS', 'Quoc Kiet', 'STAFF', true, '2026-03-31 13:12:46.232382', true, '0944556677', '11 Bạch Đằng, Quận Hồng Bàng, Hải Phòng');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (12, 'kiet', '$2y$10$gxMB28Affm1TMJ.kUIKZ/uQEbj2ap2ldl6UtU6zDsLWPLifgV4oJ6', 'kiet123', 'STAFF', false, '2026-03-31 13:12:46.232382', false, '0955667788', '12 Quang Trung, TP. Đà Lạt, Lâm Đồng');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (13, 'phankiet123', '$2y$10$elCYTBuKYuG5HNnbFhWH8.SF9nNwWdTe9gwsdZUFnoYE7kqypPRpW', 'kietdz', 'STAFF', true, '2026-03-31 13:12:46.232382', false, '0966778899', '13 Nguyễn Trãi, Quận 5, TP.HCM');
INSERT INTO public.users (user_id, username, password_hash, full_name, role, status, created_at, is_deleted, phone_number, address) VALUES (2, 'staff1', '$2y$10$CcWlT5FucIbXJFdbnDzHo.CVS5v1dEpeT3xDgDLgti7g9ztpA6Iry', 'Staff One', 'STAFF', true, '2026-03-31 13:12:46.232382', false, '0901234500', '68 Nguyễn Huệ, Quận Ninh Kiều, Cần Thơ');


--
-- TOC entry 5282 (class 0 OID 0)
-- Dependencies: 232
-- Name: ai_forecasts_forecast_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ai_forecasts_forecast_id_seq', 1, false);


--
-- TOC entry 5283 (class 0 OID 0)
-- Dependencies: 220
-- Name: categories_category_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.categories_category_id_seq', 11, true);


--
-- TOC entry 5284 (class 0 OID 0)
-- Dependencies: 228
-- Name: inventory_inventory_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.inventory_inventory_id_seq', 1, false);


--
-- TOC entry 5285 (class 0 OID 0)
-- Dependencies: 230
-- Name: pending_imports_approval_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pending_imports_approval_id_seq', 2, true);


--
-- TOC entry 5286 (class 0 OID 0)
-- Dependencies: 226
-- Name: product_variants_variant_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.product_variants_variant_id_seq', 16, true);


--
-- TOC entry 5287 (class 0 OID 0)
-- Dependencies: 224
-- Name: products_product_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.products_product_id_seq', 14, true);


--
-- TOC entry 5288 (class 0 OID 0)
-- Dependencies: 222
-- Name: shelves_shelf_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.shelves_shelf_id_seq', 4, true);


--
-- TOC entry 5289 (class 0 OID 0)
-- Dependencies: 234
-- Name: system_logs_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.system_logs_log_id_seq', 3, true);


--
-- TOC entry 5290 (class 0 OID 0)
-- Dependencies: 236
-- Name: transactions_transaction_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.transactions_transaction_id_seq', 1, false);


--
-- TOC entry 5291 (class 0 OID 0)
-- Dependencies: 218
-- Name: users_user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_user_id_seq', 13, true);


--
-- TOC entry 5089 (class 2606 OID 17164)
-- Name: ai_forecasts ai_forecasts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_forecasts
    ADD CONSTRAINT ai_forecasts_pkey PRIMARY KEY (forecast_id);


--
-- TOC entry 5069 (class 2606 OID 17146)
-- Name: categories categories_category_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_category_name_key UNIQUE (category_name);


--
-- TOC entry 5071 (class 2606 OID 17144)
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (category_id);


--
-- TOC entry 5083 (class 2606 OID 17158)
-- Name: inventory inventory_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT inventory_pkey PRIMARY KEY (inventory_id);


--
-- TOC entry 5087 (class 2606 OID 17162)
-- Name: pending_imports pending_imports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pending_imports
    ADD CONSTRAINT pending_imports_pkey PRIMARY KEY (approval_id);


--
-- TOC entry 5079 (class 2606 OID 17154)
-- Name: product_variants product_variants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_pkey PRIMARY KEY (variant_id);


--
-- TOC entry 5081 (class 2606 OID 17156)
-- Name: product_variants product_variants_sku_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT product_variants_sku_key UNIQUE (sku);


--
-- TOC entry 5077 (class 2606 OID 17152)
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (product_id);


--
-- TOC entry 5073 (class 2606 OID 17150)
-- Name: shelves shelves_location_code_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shelves
    ADD CONSTRAINT shelves_location_code_key UNIQUE (location_code);


--
-- TOC entry 5075 (class 2606 OID 17148)
-- Name: shelves shelves_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shelves
    ADD CONSTRAINT shelves_pkey PRIMARY KEY (shelf_id);


--
-- TOC entry 5093 (class 2606 OID 17168)
-- Name: system_logs system_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_logs
    ADD CONSTRAINT system_logs_pkey PRIMARY KEY (log_id);


--
-- TOC entry 5095 (class 2606 OID 17170)
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (transaction_id);


--
-- TOC entry 5091 (class 2606 OID 17166)
-- Name: ai_forecasts unique_variant_month; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_forecasts
    ADD CONSTRAINT unique_variant_month UNIQUE (variant_id, forecast_month);


--
-- TOC entry 5085 (class 2606 OID 17160)
-- Name: inventory unique_variant_shelf; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT unique_variant_shelf UNIQUE (variant_id, shelf_id);


--
-- TOC entry 5065 (class 2606 OID 17140)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- TOC entry 5067 (class 2606 OID 17142)
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- TOC entry 5096 (class 2606 OID 17171)
-- Name: categories fk_categories_created_by; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT fk_categories_created_by FOREIGN KEY (created_by) REFERENCES public.users(user_id);


--
-- TOC entry 5106 (class 2606 OID 17221)
-- Name: ai_forecasts fk_forecasts_variant; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_forecasts
    ADD CONSTRAINT fk_forecasts_variant FOREIGN KEY (variant_id) REFERENCES public.product_variants(variant_id);


--
-- TOC entry 5101 (class 2606 OID 17196)
-- Name: inventory fk_inventory_shelf; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT fk_inventory_shelf FOREIGN KEY (shelf_id) REFERENCES public.shelves(shelf_id);


--
-- TOC entry 5102 (class 2606 OID 17201)
-- Name: inventory fk_inventory_updated_by; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT fk_inventory_updated_by FOREIGN KEY (last_updated_by) REFERENCES public.users(user_id);


--
-- TOC entry 5103 (class 2606 OID 17206)
-- Name: inventory fk_inventory_variant; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT fk_inventory_variant FOREIGN KEY (variant_id) REFERENCES public.product_variants(variant_id);


--
-- TOC entry 5107 (class 2606 OID 17226)
-- Name: system_logs fk_logs_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_logs
    ADD CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES public.users(user_id);


--
-- TOC entry 5104 (class 2606 OID 17211)
-- Name: pending_imports fk_pending_manager; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pending_imports
    ADD CONSTRAINT fk_pending_manager FOREIGN KEY (manager_id) REFERENCES public.users(user_id);


--
-- TOC entry 5105 (class 2606 OID 17216)
-- Name: pending_imports fk_pending_staff; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pending_imports
    ADD CONSTRAINT fk_pending_staff FOREIGN KEY (staff_id) REFERENCES public.users(user_id);


--
-- TOC entry 5098 (class 2606 OID 17181)
-- Name: products fk_products_category; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES public.categories(category_id);


--
-- TOC entry 5099 (class 2606 OID 17186)
-- Name: products fk_products_created_by; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT fk_products_created_by FOREIGN KEY (created_by) REFERENCES public.users(user_id);


--
-- TOC entry 5097 (class 2606 OID 17176)
-- Name: shelves fk_shelves_created_by; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.shelves
    ADD CONSTRAINT fk_shelves_created_by FOREIGN KEY (created_by) REFERENCES public.users(user_id);


--
-- TOC entry 5108 (class 2606 OID 17231)
-- Name: transactions fk_transactions_user; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES public.users(user_id);


--
-- TOC entry 5109 (class 2606 OID 17236)
-- Name: transactions fk_transactions_variant; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT fk_transactions_variant FOREIGN KEY (variant_id) REFERENCES public.product_variants(variant_id);


--
-- TOC entry 5100 (class 2606 OID 17191)
-- Name: product_variants fk_variants_product; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.product_variants
    ADD CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES public.products(product_id) ON DELETE CASCADE;


-- Completed on 2026-03-31 17:13:56

--
-- PostgreSQL database dump complete
--

\unrestrict hXVeqSRJXjZ6p2Ze0kTA2S45ODjK944RS4ogEEKhtAL7SgqlLUPgeRed2K1JUfN

