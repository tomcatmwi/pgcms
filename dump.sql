--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: general; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA general;


ALTER SCHEMA general OWNER TO postgres;

--
-- Name: SCHEMA general; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA general IS 'General data';


--
-- Name: shop; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA shop;


ALTER SCHEMA shop OWNER TO postgres;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = general, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: articles; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE articles (
    id integer NOT NULL,
    token character varying,
    status numeric(1,0),
    groupid numeric DEFAULT 0,
    info character varying,
    created numeric DEFAULT 0
);


ALTER TABLE general.articles OWNER TO postgres;

--
-- Name: COLUMN articles.status; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN articles.status IS '0: normal, 1: used by some routine, 2: blocked';


--
-- Name: articles_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE articles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.articles_id_seq OWNER TO postgres;

--
-- Name: articles_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE articles_id_seq OWNED BY articles.id;


--
-- Name: articles_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('articles_id_seq', 31, true);


--
-- Name: articles_lang; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE articles_lang (
    id integer NOT NULL,
    article_id numeric DEFAULT 0,
    language numeric DEFAULT 0,
    text text,
    user_id numeric DEFAULT 0,
    created numeric DEFAULT 0,
    intro text,
    title character varying
);


ALTER TABLE general.articles_lang OWNER TO postgres;

--
-- Name: articles_lang_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE articles_lang_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.articles_lang_id_seq OWNER TO postgres;

--
-- Name: articles_lang_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE articles_lang_id_seq OWNED BY articles_lang.id;


--
-- Name: articles_lang_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('articles_lang_id_seq', 53, true);


--
-- Name: emails; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE emails (
    id integer NOT NULL,
    subject character varying(255),
    systemmsg boolean DEFAULT false
);


ALTER TABLE general.emails OWNER TO postgres;

--
-- Name: TABLE emails; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON TABLE emails IS 'E-mails';


--
-- Name: COLUMN emails.id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN emails.id IS 'ID';


--
-- Name: COLUMN emails.subject; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN emails.subject IS 'Subject';


--
-- Name: COLUMN emails.systemmsg; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN emails.systemmsg IS 'Rendszerüzenet';


--
-- Name: emails_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE emails_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.emails_id_seq OWNER TO postgres;

--
-- Name: emails_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE emails_id_seq OWNED BY emails.id;


--
-- Name: emails_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('emails_id_seq', 1, true);


--
-- Name: emails_lang; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE emails_lang (
    id integer NOT NULL,
    lang numeric(6,0),
    link_id numeric(6,0),
    subject character varying(255),
    body text
);


ALTER TABLE general.emails_lang OWNER TO postgres;

--
-- Name: TABLE emails_lang; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON TABLE emails_lang IS 'E-mail localizations';


--
-- Name: COLUMN emails_lang.id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN emails_lang.id IS 'ID';


--
-- Name: COLUMN emails_lang.lang; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN emails_lang.lang IS 'Language ID';


--
-- Name: COLUMN emails_lang.link_id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN emails_lang.link_id IS 'Link ID (general.emails)';


--
-- Name: COLUMN emails_lang.subject; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN emails_lang.subject IS 'E-mail subject';


--
-- Name: COLUMN emails_lang.body; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN emails_lang.body IS 'E-mail body';


--
-- Name: emails_lang_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE emails_lang_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.emails_lang_id_seq OWNER TO postgres;

--
-- Name: emails_lang_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE emails_lang_id_seq OWNED BY emails_lang.id;


--
-- Name: emails_lang_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('emails_lang_id_seq', 2, true);


--
-- Name: filestorage; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE filestorage (
    id integer NOT NULL,
    filename character varying,
    accessed numeric DEFAULT 0,
    created numeric,
    user_id numeric DEFAULT 0,
    protected numeric DEFAULT 0,
    mime character varying DEFAULT 0,
    info text
);


ALTER TABLE general.filestorage OWNER TO postgres;

--
-- Name: COLUMN filestorage.filename; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN filestorage.filename IS 'Original filename';


--
-- Name: COLUMN filestorage.accessed; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN filestorage.accessed IS 'Access counter';


--
-- Name: COLUMN filestorage.created; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN filestorage.created IS 'Creation timestamp';


--
-- Name: COLUMN filestorage.user_id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN filestorage.user_id IS 'Uploader';


--
-- Name: COLUMN filestorage.protected; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN filestorage.protected IS '0 - no protection, 1 - registered users, 2 - admins only';


--
-- Name: COLUMN filestorage.mime; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN filestorage.mime IS 'MIME file type';


--
-- Name: COLUMN filestorage.info; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN filestorage.info IS 'Description';


--
-- Name: filestorage_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE filestorage_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.filestorage_id_seq OWNER TO postgres;

--
-- Name: filestorage_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE filestorage_id_seq OWNED BY filestorage.id;


--
-- Name: filestorage_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('filestorage_id_seq', 17, true);


--
-- Name: gallery; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE gallery (
    id integer NOT NULL,
    user_id numeric(6,0),
    created numeric(32,0),
    info character varying
);


ALTER TABLE general.gallery OWNER TO postgres;

--
-- Name: TABLE gallery; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON TABLE gallery IS 'Image gallery';


--
-- Name: COLUMN gallery.id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN gallery.id IS 'ID';


--
-- Name: COLUMN gallery.user_id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN gallery.user_id IS 'Uploader user ID';


--
-- Name: COLUMN gallery.created; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN gallery.created IS 'Creation timestamp';


--
-- Name: gallery_lang; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE gallery_lang (
    id integer NOT NULL,
    imageid numeric DEFAULT 0,
    caption character varying,
    language numeric
);


ALTER TABLE general.gallery_lang OWNER TO postgres;

--
-- Name: gallery_lang_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE gallery_lang_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.gallery_lang_id_seq OWNER TO postgres;

--
-- Name: gallery_lang_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE gallery_lang_id_seq OWNED BY gallery_lang.id;


--
-- Name: gallery_lang_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('gallery_lang_id_seq', 58, true);


--
-- Name: images_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE images_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.images_id_seq OWNER TO postgres;

--
-- Name: images_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE images_id_seq OWNED BY gallery.id;


--
-- Name: images_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('images_id_seq', 196, true);


--
-- Name: massmailer_addresslist; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE massmailer_addresslist (
    addresslist text,
    userid numeric,
    title character varying,
    public boolean DEFAULT true,
    id integer NOT NULL
);


ALTER TABLE general.massmailer_addresslist OWNER TO postgres;

--
-- Name: massmailer_addresslist_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE massmailer_addresslist_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.massmailer_addresslist_id_seq OWNER TO postgres;

--
-- Name: massmailer_addresslist_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE massmailer_addresslist_id_seq OWNED BY massmailer_addresslist.id;


--
-- Name: massmailer_addresslist_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('massmailer_addresslist_id_seq', 9, true);


--
-- Name: messagefolders; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE messagefolders (
    id integer NOT NULL,
    title character varying,
    created numeric
);


ALTER TABLE general.messagefolders OWNER TO postgres;

--
-- Name: messagefolders_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE messagefolders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.messagefolders_id_seq OWNER TO postgres;

--
-- Name: messagefolders_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE messagefolders_id_seq OWNED BY messagefolders.id;


--
-- Name: messagefolders_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('messagefolders_id_seq', 8, true);


--
-- Name: messages; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE messages (
    id integer NOT NULL,
    email character varying,
    name character varying,
    date numeric,
    folderid numeric DEFAULT 0,
    text text,
    language numeric,
    unread boolean DEFAULT true,
    ip character varying,
    replied boolean DEFAULT false,
    phone character varying,
    company character varying
);


ALTER TABLE general.messages OWNER TO postgres;

--
-- Name: messages_autoreply; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE messages_autoreply (
    id integer NOT NULL,
    language numeric,
    subject character varying,
    body text,
    info character varying
);


ALTER TABLE general.messages_autoreply OWNER TO postgres;

--
-- Name: messages_autoreply_lang_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE messages_autoreply_lang_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.messages_autoreply_lang_id_seq OWNER TO postgres;

--
-- Name: messages_autoreply_lang_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE messages_autoreply_lang_id_seq OWNED BY messages_autoreply.id;


--
-- Name: messages_autoreply_lang_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('messages_autoreply_lang_id_seq', 23, true);


--
-- Name: messages_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.messages_id_seq OWNER TO postgres;

--
-- Name: messages_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE messages_id_seq OWNED BY messages.id;


--
-- Name: messages_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('messages_id_seq', 28, true);


--
-- Name: text_groups; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE text_groups (
    id integer NOT NULL,
    token character varying(255),
    info character varying(255)
);


ALTER TABLE general.text_groups OWNER TO postgres;

--
-- Name: TABLE text_groups; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON TABLE text_groups IS 'Text groups';


--
-- Name: COLUMN text_groups.id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN text_groups.id IS 'ID';


--
-- Name: COLUMN text_groups.token; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN text_groups.token IS 'Group token';


--
-- Name: COLUMN text_groups.info; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN text_groups.info IS 'Information about this group';


--
-- Name: text_groups_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE text_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.text_groups_id_seq OWNER TO postgres;

--
-- Name: text_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE text_groups_id_seq OWNED BY text_groups.id;


--
-- Name: text_groups_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('text_groups_id_seq', 63, true);


--
-- Name: texts; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE texts (
    id integer NOT NULL,
    token character varying(255),
    groupid numeric(6,0)
);


ALTER TABLE general.texts OWNER TO postgres;

--
-- Name: TABLE texts; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON TABLE texts IS 'Texts';


--
-- Name: COLUMN texts.id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN texts.id IS 'ID';


--
-- Name: COLUMN texts.token; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN texts.token IS 'Token';


--
-- Name: COLUMN texts.groupid; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN texts.groupid IS 'Group ID';


--
-- Name: texts_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE texts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.texts_id_seq OWNER TO postgres;

--
-- Name: texts_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE texts_id_seq OWNED BY texts.id;


--
-- Name: texts_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('texts_id_seq', 227, true);


--
-- Name: texts_lang; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE texts_lang (
    id integer NOT NULL,
    text_id numeric(6,0),
    language numeric(6,0),
    user_id numeric(6,0),
    text text
);


ALTER TABLE general.texts_lang OWNER TO postgres;

--
-- Name: TABLE texts_lang; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON TABLE texts_lang IS 'Text translations';


--
-- Name: COLUMN texts_lang.id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN texts_lang.id IS 'ID';


--
-- Name: COLUMN texts_lang.text_id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN texts_lang.text_id IS 'Text ID';


--
-- Name: COLUMN texts_lang.language; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN texts_lang.language IS 'Language ID';


--
-- Name: COLUMN texts_lang.user_id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN texts_lang.user_id IS 'Translator ID';


--
-- Name: COLUMN texts_lang.text; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN texts_lang.text IS 'Text (translated)';


--
-- Name: texts_lang_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE texts_lang_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.texts_lang_id_seq OWNER TO postgres;

--
-- Name: texts_lang_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE texts_lang_id_seq OWNED BY texts_lang.id;


--
-- Name: texts_lang_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('texts_lang_id_seq', 584, true);


--
-- Name: users; Type: TABLE; Schema: general; Owner: postgres; Tablespace: 
--

CREATE TABLE users (
    id integer NOT NULL,
    username character(255),
    password character(255),
    name character varying(255),
    city character varying(255),
    zip character varying(255),
    address character varying(255),
    state character varying(255),
    language numeric(6,0),
    email character varying(255),
    resetcode character varying(255),
    active boolean DEFAULT false,
    admin boolean DEFAULT false,
    newsletter boolean DEFAULT false,
    country character varying(6) DEFAULT 'HU'::character varying,
    phone1 character varying(3),
    phone2 character varying(3),
    phone3 character varying(9),
    nickname character varying(64),
    company character varying,
    company_country character varying(6),
    company_state character varying,
    company_zip character varying,
    company_city character varying,
    company_address character varying
);


ALTER TABLE general.users OWNER TO postgres;

--
-- Name: TABLE users; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON TABLE users IS 'User data';


--
-- Name: COLUMN users.id; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.id IS 'ID';


--
-- Name: COLUMN users.username; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.username IS 'Username';


--
-- Name: COLUMN users.password; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.password IS 'Password';


--
-- Name: COLUMN users.name; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.name IS 'Name';


--
-- Name: COLUMN users.city; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.city IS 'City';


--
-- Name: COLUMN users.zip; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.zip IS 'ZIP code';


--
-- Name: COLUMN users.address; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.address IS 'Address';


--
-- Name: COLUMN users.state; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.state IS 'State or province';


--
-- Name: COLUMN users.language; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.language IS 'Preferred language';


--
-- Name: COLUMN users.email; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.email IS 'E-mail address';


--
-- Name: COLUMN users.resetcode; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.resetcode IS 'Password reset code';


--
-- Name: COLUMN users.active; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.active IS 'User active';


--
-- Name: COLUMN users.admin; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.admin IS 'Administrator';


--
-- Name: COLUMN users.newsletter; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.newsletter IS 'Newsletter signup';


--
-- Name: COLUMN users.country; Type: COMMENT; Schema: general; Owner: postgres
--

COMMENT ON COLUMN users.country IS 'Country code';


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: general; Owner: postgres
--

CREATE SEQUENCE users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE general.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: general; Owner: postgres
--

ALTER SEQUENCE users_id_seq OWNED BY users.id;


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: general; Owner: postgres
--

SELECT pg_catalog.setval('users_id_seq', 138, true);


SET search_path = shop, pg_catalog;

--
-- Name: categories; Type: TABLE; Schema: shop; Owner: postgres; Tablespace: 
--

CREATE TABLE categories (
    id integer NOT NULL,
    available boolean DEFAULT true,
    parent numeric DEFAULT 0,
    password character varying(255)
);


ALTER TABLE shop.categories OWNER TO postgres;

--
-- Name: TABLE categories; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON TABLE categories IS 'Item categories';


--
-- Name: COLUMN categories.available; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN categories.available IS 'Category visible';


--
-- Name: COLUMN categories.parent; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN categories.parent IS 'Parent category';


--
-- Name: COLUMN categories.password; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN categories.password IS 'Password for protected category';


--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: shop; Owner: postgres
--

CREATE SEQUENCE categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE shop.categories_id_seq OWNER TO postgres;

--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: shop; Owner: postgres
--

ALTER SEQUENCE categories_id_seq OWNED BY categories.id;


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: shop; Owner: postgres
--

SELECT pg_catalog.setval('categories_id_seq', 1, false);


--
-- Name: categories_lang; Type: TABLE; Schema: shop; Owner: postgres; Tablespace: 
--

CREATE TABLE categories_lang (
    id integer NOT NULL,
    name character varying,
    description text,
    language numeric,
    category_id numeric
);


ALTER TABLE shop.categories_lang OWNER TO postgres;

--
-- Name: COLUMN categories_lang.name; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN categories_lang.name IS 'Name of category';


--
-- Name: COLUMN categories_lang.description; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN categories_lang.description IS 'Description of category';


--
-- Name: COLUMN categories_lang.language; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN categories_lang.language IS 'Language';


--
-- Name: COLUMN categories_lang.category_id; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN categories_lang.category_id IS 'Category';


--
-- Name: categories_lang_id_seq; Type: SEQUENCE; Schema: shop; Owner: postgres
--

CREATE SEQUENCE categories_lang_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE shop.categories_lang_id_seq OWNER TO postgres;

--
-- Name: categories_lang_id_seq; Type: SEQUENCE OWNED BY; Schema: shop; Owner: postgres
--

ALTER SEQUENCE categories_lang_id_seq OWNED BY categories_lang.id;


--
-- Name: categories_lang_id_seq; Type: SEQUENCE SET; Schema: shop; Owner: postgres
--

SELECT pg_catalog.setval('categories_lang_id_seq', 1, false);


--
-- Name: items; Type: TABLE; Schema: shop; Owner: postgres; Tablespace: 
--

CREATE TABLE items (
    id integer NOT NULL,
    available boolean DEFAULT false,
    always boolean DEFAULT false,
    category numeric DEFAULT 0,
    priority numeric DEFAULT 0,
    specialoffer numeric DEFAULT 0,
    created numeric DEFAULT 0
);


ALTER TABLE shop.items OWNER TO postgres;

--
-- Name: COLUMN items.id; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items.id IS 'ID';


--
-- Name: COLUMN items.available; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items.available IS 'Is it available';


--
-- Name: COLUMN items.always; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items.always IS 'Is it always available';


--
-- Name: COLUMN items.category; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items.category IS 'Category';


--
-- Name: COLUMN items.priority; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items.priority IS 'Priority index for sorting';


--
-- Name: COLUMN items.specialoffer; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items.specialoffer IS 'Special offer ends (0 for no special)';


--
-- Name: COLUMN items.created; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items.created IS 'Creation date';


--
-- Name: items_id_seq; Type: SEQUENCE; Schema: shop; Owner: postgres
--

CREATE SEQUENCE items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE shop.items_id_seq OWNER TO postgres;

--
-- Name: items_id_seq; Type: SEQUENCE OWNED BY; Schema: shop; Owner: postgres
--

ALTER SEQUENCE items_id_seq OWNED BY items.id;


--
-- Name: items_id_seq; Type: SEQUENCE SET; Schema: shop; Owner: postgres
--

SELECT pg_catalog.setval('items_id_seq', 1, false);


--
-- Name: items_lang; Type: TABLE; Schema: shop; Owner: postgres; Tablespace: 
--

CREATE TABLE items_lang (
    id integer NOT NULL,
    name character varying,
    description text,
    language numeric DEFAULT 0,
    specialmsg text,
    item_id numeric DEFAULT 0
);


ALTER TABLE shop.items_lang OWNER TO postgres;

--
-- Name: COLUMN items_lang.name; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items_lang.name IS 'Name of item';


--
-- Name: COLUMN items_lang.description; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items_lang.description IS 'Description of item';


--
-- Name: COLUMN items_lang.language; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items_lang.language IS 'Language';


--
-- Name: COLUMN items_lang.specialmsg; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items_lang.specialmsg IS 'Special notice (if needed)';


--
-- Name: COLUMN items_lang.item_id; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items_lang.item_id IS 'Item ID';


--
-- Name: items_lang_id_seq; Type: SEQUENCE; Schema: shop; Owner: postgres
--

CREATE SEQUENCE items_lang_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE shop.items_lang_id_seq OWNER TO postgres;

--
-- Name: items_lang_id_seq; Type: SEQUENCE OWNED BY; Schema: shop; Owner: postgres
--

ALTER SEQUENCE items_lang_id_seq OWNED BY items_lang.id;


--
-- Name: items_lang_id_seq; Type: SEQUENCE SET; Schema: shop; Owner: postgres
--

SELECT pg_catalog.setval('items_lang_id_seq', 1, false);


--
-- Name: items_units; Type: TABLE; Schema: shop; Owner: postgres; Tablespace: 
--

CREATE TABLE items_units (
    id integer NOT NULL,
    item_id numeric DEFAULT 0,
    unit_id numeric DEFAULT 0,
    price numeric DEFAULT 0,
    available boolean DEFAULT true
);


ALTER TABLE shop.items_units OWNER TO postgres;

--
-- Name: COLUMN items_units.item_id; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items_units.item_id IS 'Item ID';


--
-- Name: COLUMN items_units.unit_id; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items_units.unit_id IS 'Unit ID';


--
-- Name: COLUMN items_units.price; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items_units.price IS 'Price per unit in default currency';


--
-- Name: COLUMN items_units.available; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN items_units.available IS 'Is this unit available';


--
-- Name: items_units_id_seq; Type: SEQUENCE; Schema: shop; Owner: postgres
--

CREATE SEQUENCE items_units_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE shop.items_units_id_seq OWNER TO postgres;

--
-- Name: items_units_id_seq; Type: SEQUENCE OWNED BY; Schema: shop; Owner: postgres
--

ALTER SEQUENCE items_units_id_seq OWNED BY items_units.id;


--
-- Name: items_units_id_seq; Type: SEQUENCE SET; Schema: shop; Owner: postgres
--

SELECT pg_catalog.setval('items_units_id_seq', 1, false);


--
-- Name: units; Type: TABLE; Schema: shop; Owner: postgres; Tablespace: 
--

CREATE TABLE units (
    id integer NOT NULL,
    name character varying(255)
);


ALTER TABLE shop.units OWNER TO postgres;

--
-- Name: COLUMN units.name; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN units.name IS 'Unit name (ie. "pcs")';


--
-- Name: units_id_seq; Type: SEQUENCE; Schema: shop; Owner: postgres
--

CREATE SEQUENCE units_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE shop.units_id_seq OWNER TO postgres;

--
-- Name: units_id_seq; Type: SEQUENCE OWNED BY; Schema: shop; Owner: postgres
--

ALTER SEQUENCE units_id_seq OWNED BY units.id;


--
-- Name: units_id_seq; Type: SEQUENCE SET; Schema: shop; Owner: postgres
--

SELECT pg_catalog.setval('units_id_seq', 1, false);


--
-- Name: units_lang; Type: TABLE; Schema: shop; Owner: postgres; Tablespace: 
--

CREATE TABLE units_lang (
    id integer NOT NULL,
    unit_id numeric DEFAULT 0,
    name character varying,
    language numeric
);


ALTER TABLE shop.units_lang OWNER TO postgres;

--
-- Name: COLUMN units_lang.unit_id; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN units_lang.unit_id IS 'Unit ID';


--
-- Name: COLUMN units_lang.name; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN units_lang.name IS 'Unit name';


--
-- Name: COLUMN units_lang.language; Type: COMMENT; Schema: shop; Owner: postgres
--

COMMENT ON COLUMN units_lang.language IS 'Language';


--
-- Name: units_lang_id_seq; Type: SEQUENCE; Schema: shop; Owner: postgres
--

CREATE SEQUENCE units_lang_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE shop.units_lang_id_seq OWNER TO postgres;

--
-- Name: units_lang_id_seq; Type: SEQUENCE OWNED BY; Schema: shop; Owner: postgres
--

ALTER SEQUENCE units_lang_id_seq OWNED BY units_lang.id;


--
-- Name: units_lang_id_seq; Type: SEQUENCE SET; Schema: shop; Owner: postgres
--

SELECT pg_catalog.setval('units_lang_id_seq', 1, false);


SET search_path = general, pg_catalog;

--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY articles ALTER COLUMN id SET DEFAULT nextval('articles_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY articles_lang ALTER COLUMN id SET DEFAULT nextval('articles_lang_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY emails ALTER COLUMN id SET DEFAULT nextval('emails_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY emails_lang ALTER COLUMN id SET DEFAULT nextval('emails_lang_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY filestorage ALTER COLUMN id SET DEFAULT nextval('filestorage_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY gallery ALTER COLUMN id SET DEFAULT nextval('images_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY gallery_lang ALTER COLUMN id SET DEFAULT nextval('gallery_lang_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY massmailer_addresslist ALTER COLUMN id SET DEFAULT nextval('massmailer_addresslist_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY messagefolders ALTER COLUMN id SET DEFAULT nextval('messagefolders_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY messages ALTER COLUMN id SET DEFAULT nextval('messages_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY messages_autoreply ALTER COLUMN id SET DEFAULT nextval('messages_autoreply_lang_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY text_groups ALTER COLUMN id SET DEFAULT nextval('text_groups_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY texts ALTER COLUMN id SET DEFAULT nextval('texts_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY texts_lang ALTER COLUMN id SET DEFAULT nextval('texts_lang_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: general; Owner: postgres
--

ALTER TABLE ONLY users ALTER COLUMN id SET DEFAULT nextval('users_id_seq'::regclass);


SET search_path = shop, pg_catalog;

--
-- Name: id; Type: DEFAULT; Schema: shop; Owner: postgres
--

ALTER TABLE ONLY categories ALTER COLUMN id SET DEFAULT nextval('categories_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: shop; Owner: postgres
--

ALTER TABLE ONLY categories_lang ALTER COLUMN id SET DEFAULT nextval('categories_lang_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: shop; Owner: postgres
--

ALTER TABLE ONLY items ALTER COLUMN id SET DEFAULT nextval('items_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: shop; Owner: postgres
--

ALTER TABLE ONLY items_lang ALTER COLUMN id SET DEFAULT nextval('items_lang_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: shop; Owner: postgres
--

ALTER TABLE ONLY items_units ALTER COLUMN id SET DEFAULT nextval('items_units_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: shop; Owner: postgres
--

ALTER TABLE ONLY units ALTER COLUMN id SET DEFAULT nextval('units_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: shop; Owner: postgres
--

ALTER TABLE ONLY units_lang ALTER COLUMN id SET DEFAULT nextval('units_lang_id_seq'::regclass);


SET search_path = general, pg_catalog;

--
-- Data for Name: articles; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY articles (id, token, status, groupid, info, created) FROM stdin;
30	test_article	0	59	This is the test article for pgCMS	1451315280000
\.


--
-- Data for Name: articles_lang; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY articles_lang (id, article_id, language, text, user_id, created, intro, title) FROM stdin;
49	30	1	<h1>Lorem ipsum dolor sit amet, consectetur adipiscing elit. (English)</h1>\n      <div>\n       <b>\n       <img style="vertical-align: top; margin-right: 1em; margin-bottom: 1em; float: left;" src="data:image/png;base64,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">Mauris feugiat ipsum quis massa rhoncus, vel vestibulum mi rutrum.</b> Praesent eu orci non ligula vehicula gravida. Phasellus tincidunt, justo eget congue fermentum, purus nisi ultricies mi, in commodo ante lectus eget sapien. Fusce ac elementum urna. Proin justo felis, tempus eu bibendum id, ullamcorper sit amet metus. Nulla tempor libero at nisi tincidunt placerat. Phasellus at odio eget lorem rhoncus fermentum eget vel odio. Quisque orci est, iaculis lobortis mi vitae, vestibulum eleifend risus. Proin condimentum molestie leo quis accumsan. Aliquam quis pretium nunc, commodo egestas sem. Nam sagittis sem et mi placerat, ac facilisis lectus laoreet. Praesent eget dui velit. Cras varius dui nulla. Sed interdum nibh et arcu placerat, eget laoreet arcu hendrerit.</div>	1	1451315280000	<br>	Test article - you can delete it
53	30	2	<h1>Lorem ipsum dolor sit amet, consectetur adipiscing elit. (Magyar)</h1>\n      <div>\n       <b>\n       <img style="vertical-align: top; margin-right: 1em; margin-bottom: 1em; float: left;" src="data:image/png;base64,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">Mauris feugiat ipsum quis massa rhoncus, vel vestibulum mi rutrum.</b> Praesent eu orci non ligula vehicula gravida. Phasellus tincidunt, justo eget congue fermentum, purus nisi ultricies mi, in commodo ante lectus eget sapien. Fusce ac elementum urna. Proin justo felis, tempus eu bibendum id, ullamcorper sit amet metus. Nulla tempor libero at nisi tincidunt placerat. Phasellus at odio eget lorem rhoncus fermentum eget vel odio. Quisque orci est, iaculis lobortis mi vitae, vestibulum eleifend risus. Proin condimentum molestie leo quis accumsan. Aliquam quis pretium nunc, commodo egestas sem. Nam sagittis sem et mi placerat, ac facilisis lectus laoreet. Praesent eget dui velit. Cras varius dui nulla. Sed interdum nibh et arcu placerat, eget laoreet arcu hendrerit.</div>	1	1453035856	<br>	Test article - you can delete it
\.


--
-- Data for Name: emails; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY emails (id, subject, systemmsg) FROM stdin;
\.


--
-- Data for Name: emails_lang; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY emails_lang (id, lang, link_id, subject, body) FROM stdin;
\.


--
-- Data for Name: filestorage; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY filestorage (id, filename, accessed, created, user_id, protected, mime, info) FROM stdin;
\.


--
-- Data for Name: gallery; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY gallery (id, user_id, created, info) FROM stdin;
\.


--
-- Data for Name: gallery_lang; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY gallery_lang (id, imageid, caption, language) FROM stdin;
\.


--
-- Data for Name: massmailer_addresslist; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY massmailer_addresslist (addresslist, userid, title, public, id) FROM stdin;
\.


--
-- Data for Name: messagefolders; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY messagefolders (id, title, created) FROM stdin;
\.


--
-- Data for Name: messages; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY messages (id, email, name, date, folderid, text, language, unread, ip, replied, phone, company) FROM stdin;
\.


--
-- Data for Name: messages_autoreply; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY messages_autoreply (id, language, subject, body, info) FROM stdin;
\.


--
-- Data for Name: text_groups; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY text_groups (id, token, info) FROM stdin;
59	test	Autogenerated by pgCMS
\.


--
-- Data for Name: texts; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY texts (id, token, groupid) FROM stdin;
225	test	59
\.


--
-- Data for Name: texts_lang; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY texts_lang (id, text_id, language, user_id, text) FROM stdin;
579	225	2	1	Ego quos amo osculandi vestros labia majora.
584	225	1	1	This appears to be working
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: general; Owner: postgres
--

COPY users (id, username, password, name, city, zip, address, state, language, email, resetcode, active, admin, newsletter, country, phone1, phone2, phone3, nickname, company, company_country, company_state, company_zip, company_city, company_address) FROM stdin;
1	admin                                                                                                                                                                                                                                                          	21232f297a57a5a743894a0e4a801fc3                                                                                                                                                                                                                               	Administrator	Adminville	0000	Root str. 32.	Adminstate	2	admin@pgcms.com		t	t	f	HU		20	12345678	Administrator	Pixeldog	HU		0000	Adminville	Root str. 64.
\.


SET search_path = shop, pg_catalog;

--
-- Data for Name: categories; Type: TABLE DATA; Schema: shop; Owner: postgres
--

COPY categories (id, available, parent, password) FROM stdin;
\.


--
-- Data for Name: categories_lang; Type: TABLE DATA; Schema: shop; Owner: postgres
--

COPY categories_lang (id, name, description, language, category_id) FROM stdin;
\.


--
-- Data for Name: items; Type: TABLE DATA; Schema: shop; Owner: postgres
--

COPY items (id, available, always, category, priority, specialoffer, created) FROM stdin;
\.


--
-- Data for Name: items_lang; Type: TABLE DATA; Schema: shop; Owner: postgres
--

COPY items_lang (id, name, description, language, specialmsg, item_id) FROM stdin;
\.


--
-- Data for Name: items_units; Type: TABLE DATA; Schema: shop; Owner: postgres
--

COPY items_units (id, item_id, unit_id, price, available) FROM stdin;
\.


--
-- Data for Name: units; Type: TABLE DATA; Schema: shop; Owner: postgres
--

COPY units (id, name) FROM stdin;
\.


--
-- Data for Name: units_lang; Type: TABLE DATA; Schema: shop; Owner: postgres
--

COPY units_lang (id, unit_id, name, language) FROM stdin;
\.


SET search_path = general, pg_catalog;

--
-- Name: articles_lang_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY articles_lang
    ADD CONSTRAINT articles_lang_pkey PRIMARY KEY (id);


--
-- Name: articles_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY articles
    ADD CONSTRAINT articles_pkey PRIMARY KEY (id);


--
-- Name: emails_lang_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY emails_lang
    ADD CONSTRAINT emails_lang_pkey PRIMARY KEY (id);


--
-- Name: emails_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY emails
    ADD CONSTRAINT emails_pkey PRIMARY KEY (id);


--
-- Name: images_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY gallery
    ADD CONSTRAINT images_pkey PRIMARY KEY (id);


--
-- Name: messages_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id);


--
-- Name: text_groups_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY text_groups
    ADD CONSTRAINT text_groups_pkey PRIMARY KEY (id);


--
-- Name: texts_lang_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY texts_lang
    ADD CONSTRAINT texts_lang_pkey PRIMARY KEY (id);


--
-- Name: texts_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY texts
    ADD CONSTRAINT texts_pkey PRIMARY KEY (id);


--
-- Name: users_pkey; Type: CONSTRAINT; Schema: general; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


SET search_path = shop, pg_catalog;

--
-- Name: items_pkey; Type: CONSTRAINT; Schema: shop; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY items
    ADD CONSTRAINT items_pkey PRIMARY KEY (id);


--
-- PostgreSQL database dump complete
--

