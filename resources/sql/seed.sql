-----------------------------------------
-- Drop old schmema
-----------------------------------------
DROP TABLE IF EXISTS "content" CASCADE;
DROP TABLE IF EXISTS post CASCADE;
DROP TABLE IF EXISTS "comment" CASCADE;
DROP TABLE IF EXISTS reply CASCADE;
DROP TABLE IF EXISTS country CASCADE;
DROP TABLE IF EXISTS "location" CASCADE;
DROP TABLE IF EXISTS "user" CASCADE;
DROP TABLE IF EXISTS "admin" CASCADE;
DROP TABLE IF EXISTS ban CASCADE;
DROP TABLE IF EXISTS "notification" CASCADE;
DROP TABLE IF EXISTS badge CASCADE;
DROP TABLE IF EXISTS report CASCADE;
DROP TABLE IF EXISTS user_report CASCADE;
DROP TABLE IF EXISTS content_report CASCADE;
DROP TABLE IF EXISTS tag CASCADE;
DROP TABLE IF EXISTS tag_report CASCADE;
DROP TABLE IF EXISTS reason CASCADE;
DROP TABLE IF EXISTS rating CASCADE;
DROP TABLE IF EXISTS user_subscription CASCADE;
DROP TABLE IF EXISTS has_badge CASCADE;
DROP TABLE IF EXISTS report_reason CASCADE;
DROP TABLE IF EXISTS tag_subscription CASCADE;
DROP TABLE IF EXISTS saved_post CASCADE;
DROP TABLE IF EXISTS post_tag CASCADE;
DROP TABLE IF EXISTS post_version CASCADE;
DROP TABLE IF EXISTS comment_version CASCADE;
DROP TABLE IF EXISTS reply_version CASCADE;

DROP TYPE IF EXISTS post_type;
DROP TYPE IF EXISTS badge_name;
DROP TYPE IF EXISTS report_reason_type;

-----------------------------------------
-- Types
-----------------------------------------

CREATE TYPE post_type AS ENUM ('News', 'Opinion');
CREATE TYPE badge_name AS ENUM ('Champion', 'Mr WorldWide', 'Popular', 'Tag Master', 'Targeted', 'Verified', 'Violet');
CREATE TYPE report_reason_type AS ENUM ('Abusive Language', 'Fake News', 'Hate Speech', 'Advertisement', 'Clickbait', 'Other');

-----------------------------------------
-- Tables
-----------------------------------------

DROP SEQUENCE IF EXISTS country_id_sequence;
CREATE SEQUENCE country_id_sequence
    start 1
    increment 1;

CREATE TABLE country (
    id INTEGER NOT NULL DEFAULT nextval('country_id_sequence') PRIMARY KEY,
    "name" TEXT NOT NULL CONSTRAINT country_name_uk UNIQUE
);

ALTER SEQUENCE country_id_sequence
OWNED BY country.id;

-------------

DROP SEQUENCE IF EXISTS location_id_sequence;
CREATE SEQUENCE location_id_sequence
    start 1
    increment 1;

CREATE TABLE "location" (
    id INTEGER NOT NULL DEFAULT nextval('location_id_sequence') PRIMARY KEY,
    "name" TEXT NOT NULL,
    country_id INTEGER NOT NULL REFERENCES country (id) ON UPDATE CASCADE ON DELETE CASCADE
);

ALTER SEQUENCE location_id_sequence
OWNED BY "location".id;

-------------

DROP SEQUENCE IF EXISTS user_id_sequence;
CREATE SEQUENCE user_id_sequence
    start 1
    increment 1;

CREATE TABLE "user" (
    id INTEGER NOT NULL DEFAULT nextval('user_id_sequence') PRIMARY KEY,
    email TEXT NOT NULL CONSTRAINT user_email_uk UNIQUE,
    name TEXT NOT NULL,
    password TEXT NOT NULL,
    bio text,
    birthday DATE NOT NULL,
    banned BOOLEAN NOT NULL DEFAULT FALSE,
    verified BOOLEAN NOT NULL DEFAULT FALSE,
    location_id INTEGER REFERENCES "location" (id) ON UPDATE CASCADE ON DELETE SET NULL,
    photo text,
    remember_token VARCHAR, -- Laravel
    search tsvector,
    CONSTRAINT of_age_user_ck CHECK (date_part('year', age(now(), birthday)) > 13)
);

ALTER SEQUENCE user_id_sequence
OWNED BY "user".id;

-------------

DROP SEQUENCE IF EXISTS content_id_sequence;
CREATE SEQUENCE content_id_sequence
    start 1
    increment 1;

CREATE TABLE "content" (
    id INTEGER NOT NULL DEFAULT nextval('content_id_sequence') PRIMARY KEY,
    body text,
    most_recent BOOLEAN NOT NULL DEFAULT TRUE,
    likes_difference INTEGER NOT NULL DEFAULT 0,
    author_id INTEGER REFERENCES "user"(id) ON UPDATE CASCADE ON DELETE SET NULL
);

ALTER SEQUENCE content_id_sequence
OWNED BY "content".id;

-------------

CREATE TABLE post (
    content_id INTEGER PRIMARY KEY REFERENCES "content" (id) ON UPDATE CASCADE ON DELETE CASCADE,
    title TEXT NOT NULL,
    publication_date TIMESTAMP WITH TIME zone DEFAULT now() NOT NULL,
    modification_date TIMESTAMP WITH TIME zone DEFAULT NULL,
    visible BOOLEAN NOT NULL DEFAULT TRUE,
    type post_type NOT NULL,
    photo TEXT NOT NULL,
    search tsvector,
    CONSTRAINT mod_after_pub_post_ck CHECK (publication_date <= modification_date)
);

-------------

CREATE TABLE "comment" (
    content_id INTEGER PRIMARY KEY REFERENCES "content" (id) ON UPDATE CASCADE ON DELETE CASCADE,
    publication_date TIMESTAMP WITH TIME zone DEFAULT now() NOT NULL,
    modification_date TIMESTAMP WITH TIME zone DEFAULT NULL,
    post_id INTEGER NOT NULL REFERENCES post(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT pub_date_comment_ck CHECK (publication_date <= now()),
    CONSTRAINT mod_after_pub_comment_ck CHECK (publication_date < modification_date)
);

-------------

CREATE TABLE reply (
    content_id INTEGER PRIMARY KEY REFERENCES "content" (id) ON UPDATE CASCADE ON DELETE CASCADE,
    publication_date TIMESTAMP WITH TIME zone DEFAULT now() NOT NULL,
    modification_date TIMESTAMP WITH TIME zone DEFAULT NULL,
    comment_id INTEGER NOT NULL REFERENCES comment(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT pub_date_reply_ck CHECK (publication_date <= now()),
    CONSTRAINT mod_after_pub_reply_ck CHECK (publication_date < modification_date)
);

-------------

CREATE TABLE "admin" (
    user_id INTEGER PRIMARY KEY REFERENCES "user" (id) ON UPDATE CASCADE ON DELETE CASCADE,
    posts_deleted INTEGER NOT NULL DEFAULT 0,
    comments_deleted INTEGER NOT NULL DEFAULT 0,
    reports_solved INTEGER NOT NULL DEFAULT 0,
    users_banned INTEGER NOT NULL DEFAULT 0,
    CONSTRAINT posts_deleted_non_negative_ck CHECK (posts_deleted >= 0),
    CONSTRAINT comments_deleted_non_negative_ck CHECK (comments_deleted >= 0),
    CONSTRAINT reports_solved_non_negative_ck CHECK (reports_solved >= 0),
    CONSTRAINT user_banned_non_negative_ck CHECK (users_banned >= 0)
);


-------------

DROP SEQUENCE IF EXISTS ban_id_sequence;
CREATE SEQUENCE ban_id_sequence
    start 1
    increment 1;

CREATE TABLE ban (
    id INTEGER NOT NULL DEFAULT nextval('ban_id_sequence') PRIMARY KEY,
    ban_start DATE DEFAULT now() NOT NULL,
    ban_end DATE,
    user_id INTEGER NOT NULL REFERENCES "user" (id) ON UPDATE CASCADE ON DELETE CASCADE,
    admin_id INTEGER REFERENCES "admin" (user_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT ban_start_before_today_ck CHECK (ban_start <= now()),
    CONSTRAINT ban_interval_valid_ck  CHECK (ban_end > ban_start)
);

ALTER SEQUENCE ban_id_sequence
OWNED BY ban.id;

-------------

DROP SEQUENCE IF EXISTS notification_id_sequence;
CREATE SEQUENCE notification_id_sequence
    start 1
    increment 1;

CREATE TABLE "notification" (
    id INTEGER NOT NULL DEFAULT nextval('notification_id_sequence') PRIMARY KEY,
    "text" TEXT NOT NULL,
    icon TEXT NOT NULL,
    domain text,
    "date" TIMESTAMP WITH TIME zone DEFAULT now() NOT NULL,
    user_id INTEGER NOT NULL REFERENCES "user" (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT notif_date_before_today_ck CHECK ("date" <= now())
);

ALTER SEQUENCE notification_id_sequence
OWNED BY "notification".id;

-------------

DROP SEQUENCE IF EXISTS badge_id_sequence;
CREATE SEQUENCE badge_id_sequence
    start 1
    increment 1;

CREATE TABLE badge (
    id INTEGER NOT NULL DEFAULT nextval('badge_id_sequence') PRIMARY KEY,
    icon TEXT NOT NULL CONSTRAINT badge_icon_uk UNIQUE,
    "name" badge_name NOT NULL CONSTRAINT badge_name_uk UNIQUE,
    description TEXT NOT NULL CONSTRAINT badge_description_uk UNIQUE
);

ALTER SEQUENCE badge_id_sequence
OWNED BY badge.id;

-------------

DROP SEQUENCE IF EXISTS report_id_sequence;
CREATE SEQUENCE report_id_sequence
    start 1
    increment 1;

CREATE TABLE report (
    id INTEGER NOT NULL DEFAULT nextval('report_id_sequence') PRIMARY KEY,
    explanation TEXT NOT NULL,
    closed BOOLEAN NOT NULL DEFAULT FALSE,
    reporter_id INTEGER REFERENCES "user" (id) ON UPDATE CASCADE ON DELETE SET NULL,
    solver_id INTEGER DEFAULT NULL REFERENCES "admin" (user_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT reporter_diff_solver_ck CHECK ((reporter_id != solver_id) or (reporter_id is NULL and solver_id is NULL))
);

ALTER SEQUENCE report_id_sequence
OWNED BY report.id;

-------------

CREATE TABLE user_report (
    report_id INTEGER PRIMARY KEY REFERENCES report (id) ON UPDATE CASCADE ON DELETE CASCADE,
    user_id INTEGER REFERENCES "user" (id) ON UPDATE CASCADE ON DELETE CASCADE
);

-------------

CREATE TABLE content_report (
    report_id INTEGER PRIMARY KEY REFERENCES report (id) ON UPDATE CASCADE ON DELETE CASCADE,
    content_id INTEGER REFERENCES content (id) ON UPDATE CASCADE ON DELETE CASCADE
);


-------------

DROP SEQUENCE IF EXISTS tag_id_sequence;
CREATE SEQUENCE tag_id_sequence
    start 1
    increment 1;

CREATE TABLE tag (
    id INTEGER NOT NULL DEFAULT nextval('tag_id_sequence') PRIMARY KEY,
    "name" TEXT NOT NULL CONSTRAINT tag_name_uk UNIQUE,
    photo text,
    color text NOT NULL,
    search tsvector
);

ALTER SEQUENCE tag_id_sequence
OWNED BY tag.id;

-------------

CREATE TABLE tag_report (
    report_id INTEGER PRIMARY KEY REFERENCES report (id) ON UPDATE CASCADE ON DELETE CASCADE,
    tag_id INTEGER REFERENCES tag (id) ON UPDATE CASCADE ON DELETE SET NULL
);

-------------

DROP SEQUENCE IF EXISTS reason_id_sequence;
CREATE SEQUENCE reason_id_sequence
    start 1
    increment 1;

CREATE TABLE reason (
    id INTEGER NOT NULL DEFAULT nextval('reason_id_sequence') PRIMARY KEY,
    "name" report_reason_type NOT NULL CONSTRAINT reason_name_uk UNIQUE
);

ALTER SEQUENCE reason_id_sequence
OWNED BY reason.id;

-------------

CREATE TABLE rating (
    content_id INTEGER NOT NULL REFERENCES content(id) ON UPDATE CASCADE ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES "user"(id) ON UPDATE CASCADE ON DELETE CASCADE,
    "like" BOOLEAN NOT NULL,
    PRIMARY KEY(content_id,user_id)
);

-------------

CREATE TABLE user_subscription(
    subscribing_user_id INTEGER NOT NULL REFERENCES "user"(id) ON UPDATE CASCADE ON DELETE CASCADE,
    subscribed_user_id INTEGER NOT NULL REFERENCES "user"(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT sub_users_diff CHECK (subscribed_user_id != subscribing_user_id),
    PRIMARY KEY (subscribed_user_id ,subscribing_user_id)
);


-------------

CREATE TABLE has_badge(
    user_id INTEGER NOT NULL REFERENCES "user"(id) ON UPDATE CASCADE ON DELETE CASCADE,
    badge_id INTEGER NOT NULL REFERENCES badge(id) ON UPDATE CASCADE ON DELETE CASCADE,
    PRIMARY KEY (user_id, badge_id)
);

-------------

CREATE TABLE report_reason (
    report_id INTEGER NOT NULL REFERENCES report(id) ON UPDATE CASCADE ON DELETE CASCADE,
    reason_id INTEGER NOT NULL REFERENCES reason(id) ON UPDATE CASCADE ON DELETE CASCADE,
    PRIMARY KEY (report_id, reason_id)
);

-------------

CREATE TABLE tag_subscription(
    user_id INTEGER NOT NULL REFERENCES "user"(id) ON UPDATE CASCADE ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES tag(id) ON UPDATE CASCADE ON DELETE CASCADE,
    PRIMARY KEY (user_id, tag_id)
);

 -------------

CREATE TABLE saved_post (
    user_id INTEGER NOT NULL REFERENCES "user"(id) ON UPDATE CASCADE ON DELETE CASCADE,
    post_id INTEGER NOT NULL REFERENCES post(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    PRIMARY KEY (user_id, post_id)
);

-------------

CREATE TABLE post_tag (
    post_id INTEGER NOT NULL REFERENCES post(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES tag(id) ON UPDATE CASCADE ON DELETE CASCADE,
    PRIMARY KEY (post_id,tag_id)
);

-------------

CREATE TABLE post_version (
    past_version_id INTEGER PRIMARY KEY REFERENCES post(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    cur_version_id INTEGER NOT NULL REFERENCES post(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT post_version_diff CHECK (cur_version_id != past_version_id)
);

-------------

CREATE TABLE comment_version (
    past_version_id INTEGER PRIMARY KEY REFERENCES comment(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    cur_version_id INTEGER NOT NULL REFERENCES comment(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT comment_version_diff CHECK (cur_version_id != past_version_id)
);

-------------

CREATE TABLE reply_version (
    past_version_id INTEGER PRIMARY KEY REFERENCES reply(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    cur_version_id INTEGER NOT NULL REFERENCES reply(content_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT reply_version_diff CHECK (cur_version_id != past_version_id)
);

CREATE INDEX content_author ON "content" USING hash (author_id);

CREATE INDEX post_versions ON post_version USING hash (cur_version_id);

CREATE INDEX comment_versions ON comment_version USING hash (cur_version_id);

CREATE INDEX reply_versions ON reply_version USING hash (cur_version_id);

CREATE INDEX post_dates ON post USING btree (publication_date);

CREATE INDEX post_search_idx ON post USING GIST (search);

CREATE INDEX user_search_idx ON "user" USING GIST (search);

CREATE INDEX tag_search_idx ON tag USING GIN (search);

---------------------------------
---------------------------------
---- First round of triggers ----
---------------------------------
---------------------------------


--- FULL TEXT SEARCH

CREATE OR REPLACE FUNCTION user_search_update() RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        NEW.search = to_tsvector('simple', NEW.name);
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF NEW.name <> OLD.name THEN
            NEW.search = to_tsvector('simple', NEW.name);
        END IF;
    END IF;
    RETURN NEW;
END
$$ LANGUAGE 'plpgsql';

DROP TRIGGER IF EXISTS user_search_update ON "user";
CREATE TRIGGER user_search_update
    BEFORE INSERT OR UPDATE ON "user"
    FOR EACH ROW
    EXECUTE PROCEDURE user_search_update();

--

CREATE OR REPLACE FUNCTION tag_search_update() RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        NEW.search = to_tsvector('simple', NEW.name);
    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF NEW.name <> OLD.name THEN
            NEW.search = to_tsvector('simple', NEW.name);
        END IF;
    END IF;
    RETURN NEW;
END
$$ LANGUAGE 'plpgsql';

DROP TRIGGER IF EXISTS tag_search_update ON tag;
CREATE TRIGGER tag_search_update
    BEFORE INSERT OR UPDATE ON tag
    FOR EACH ROW
    EXECUTE PROCEDURE tag_search_update();
--

CREATE OR REPLACE FUNCTION post_title_search_update() RETURNS TRIGGER AS $$
DECLARE

    search_value tsvector;


BEGIN

    IF TG_OP = 'INSERT' THEN

         SELECT (setweight(to_tsvector('simple', NEW.title), 'A') || ' ' || setweight(to_tsvector('simple', c.body), 'B') || ' ' || setweight(to_tsvector('simple', u.name), 'D')) as rank  INTO search_value

         FROM "content" c inner join "user" u on c.author_id = u.id
         WHERE c.id = NEW.content_id;

         NEW.search = search_value;

    END IF;
    IF TG_OP = 'UPDATE' THEN
        IF NEW.title <> OLD.title THEN

         SELECT (setweight(to_tsvector('simple', NEW.title), 'A') || ' ' || setweight(to_tsvector('simple', c.body), 'B') || ' ' || setweight(to_tsvector('simple', u.name), 'D')) as rank  INTO search_value

         FROM "content" c inner join "user" u on c.author_id = u.id
         WHERE c.id = NEW.content_id;

         NEW.search = search_value;
        END IF;
    END IF;
    RETURN NEW;
END
$$ LANGUAGE 'plpgsql';

DROP TRIGGER IF EXISTS post_title_search_update ON post;
CREATE TRIGGER post_title_search_update
    BEFORE INSERT OR UPDATE ON post
    FOR EACH ROW
    EXECUTE PROCEDURE post_title_search_update();
--

CREATE OR REPLACE FUNCTION post_body_search_update() RETURNS TRIGGER AS $$
DECLARE

    search_value tsvector;

BEGIN

        IF NEW.body <> OLD.body THEN

            IF EXISTS (SELECT (setweight(to_tsvector('simple', p.title), 'A') || ' ' || setweight(to_tsvector('simple', c.body), 'B') || ' ' || setweight(to_tsvector('simple', u.name), 'D')) as rank

            	FROM "content" c inner join post p on c.id = p.content_id inner join "user" u on c.author_id = u.id
            	WHERE NEW.id = p.content_id) THEN

				SELECT (setweight(to_tsvector('simple', p.title), 'A') || ' ' || setweight(to_tsvector('simple', c.body), 'B') || ' ' || setweight(to_tsvector('simple', u.name), 'D')) as rank  INTO search_value

            	FROM "content" c inner join post p on c.id = p.content_id inner join "user" u on c.author_id = u.id
            	WHERE NEW.id = p.content_id;

                UPDATE post SET search = search_value WHERE post.content_id = NEW.id;

            END IF;

        END IF;

    RETURN NEW;
END
$$ LANGUAGE 'plpgsql';

DROP TRIGGER IF EXISTS post_body_search_update ON "content";
CREATE TRIGGER post_body_search_update
    BEFORE UPDATE ON "content"
    FOR EACH ROW
    EXECUTE PROCEDURE post_body_search_update();
--

CREATE OR REPLACE FUNCTION post_author_name_search_update() RETURNS TRIGGER AS $$
DECLARE

    search_value tsvector;

BEGIN

        IF NEW.name <> OLD.name THEN

            IF EXISTS (SELECT (setweight(to_tsvector('simple', p.title), 'A') || ' ' || setweight(to_tsvector('simple', c.body), 'B') || ' ' || setweight(to_tsvector('simple', u.name), 'D')) as rank

            	FROM "content" c inner join post p on c.id = p.content_id inner join "user" u on c.author_id = u.id
            	WHERE NEW.id = p.content_id) THEN

				SELECT (setweight(to_tsvector('simple', p.title), 'A') || ' ' || setweight(to_tsvector('simple', c.body), 'B') || ' ' || setweight(to_tsvector('simple', u.name), 'D')) as rank  INTO search_value

            	FROM "content" c inner join post p on c.id = p.content_id inner join "user" u on c.author_id = u.id
            	WHERE NEW.id = p.content_id;

                UPDATE post SET search = search_value WHERE post.content_id in (SELECT id from "content" c WHERE c.author_id = NEW.id );
            END IF;

        END IF;

    RETURN NEW;
END
$$ LANGUAGE 'plpgsql';

DROP TRIGGER IF EXISTS post_author_name_search_update ON "user";
CREATE TRIGGER post_author_name_search_update
    BEFORE UPDATE ON "user"
    FOR EACH ROW
    EXECUTE PROCEDURE post_author_name_search_update();
--

---------------------------------
---------------------------------
----------- Populate ------------
---------------------------------
---------------------------------

-- country -- (20)
INSERT INTO country (name) VALUES ('Portugal');
INSERT INTO country (name) VALUES ('Spain');
INSERT INTO country (name) VALUES ('France');
INSERT INTO country (name) VALUES ('USA');
INSERT INTO country (name) VALUES ('Italy');
INSERT INTO country (name) VALUES ('Greece');
INSERT INTO country (name) VALUES ('Finland');
INSERT INTO country (name) VALUES ('Tunisia');
INSERT INTO country (name) VALUES ('Mauritania');
INSERT INTO country (name) VALUES ('Nigeria');
INSERT INTO country (name) VALUES ('Canada');
INSERT INTO country (name) VALUES ('Niger');
INSERT INTO country (name) VALUES ('Russia');
INSERT INTO country (name) VALUES ('China');
INSERT INTO country (name) VALUES ('Japan');
INSERT INTO country (name) VALUES ('South Korea');
INSERT INTO country (name) VALUES ('India');
INSERT INTO country (name) VALUES ('Mayotte');
INSERT INTO country (name) VALUES ('Greenland');
INSERT INTO country (name) VALUES ('Brazil');


-- location -- (49)
INSERT INTO "location" (name,country_id) VALUES ('Aveiro',1);
INSERT INTO "location" (name,country_id) VALUES ('Lisbon',1);
INSERT INTO "location" (name,country_id) VALUES ('Porto',1);
INSERT INTO "location" (name,country_id) VALUES ('Madrid',2);
INSERT INTO "location" (name,country_id) VALUES ('Barcelona',2);
INSERT INTO "location" (name,country_id) VALUES ('Sevilha',2);
INSERT INTO "location" (name,country_id) VALUES ('Paris',3);
INSERT INTO "location" (name,country_id) VALUES ('Lyon',3);
INSERT INTO "location" (name,country_id) VALUES ('Marseille',3);
INSERT INTO "location" (name,country_id) VALUES ('New York',4);
INSERT INTO "location" (name,country_id) VALUES ('Los Angeles',4);
INSERT INTO "location" (name,country_id) VALUES ('Chicago',4);
INSERT INTO "location" (name,country_id) VALUES ('Rome',5);
INSERT INTO "location" (name,country_id) VALUES ('Milan',5);
INSERT INTO "location" (name,country_id) VALUES ('Naples',5);
INSERT INTO "location" (name,country_id) VALUES ('Athens',6);
INSERT INTO "location" (name,country_id) VALUES ('Thessaloniki',6);
INSERT INTO "location" (name,country_id) VALUES ('Patras',6);
INSERT INTO "location" (name,country_id) VALUES ('Helsinki',7);
INSERT INTO "location" (name,country_id) VALUES ('Espoo',7);
INSERT INTO "location" (name,country_id) VALUES ('Tampere',7);
INSERT INTO "location" (name,country_id) VALUES ('Tunis',8);
INSERT INTO "location" (name,country_id) VALUES ('Nouakchott',9);
INSERT INTO "location" (name,country_id) VALUES ('Lagos',10);
INSERT INTO "location" (name,country_id) VALUES ('Ottawa',11);
INSERT INTO "location" (name,country_id) VALUES ('Edmonton',11);
INSERT INTO "location" (name,country_id) VALUES ('Regina',11);
INSERT INTO "location" (name,country_id) VALUES ('Niamey',12);
INSERT INTO "location" (name,country_id) VALUES ('Maradi',12);
INSERT INTO "location" (name,country_id) VALUES ('Moscow',13);
INSERT INTO "location" (name,country_id) VALUES ('Saint Petersburg',13);
INSERT INTO "location" (name,country_id) VALUES ('Kazan',13);
INSERT INTO "location" (name,country_id) VALUES ('Shanghai',14);
INSERT INTO "location" (name,country_id) VALUES ('Beijing',14);
INSERT INTO "location" (name,country_id) VALUES ('Tianjin',14);
INSERT INTO "location" (name,country_id) VALUES ('Yokohama',15);
INSERT INTO "location" (name,country_id) VALUES ('Osaka',15);
INSERT INTO "location" (name,country_id) VALUES ('Tokyo',15);
INSERT INTO "location" (name,country_id) VALUES ('Seoul',16);
INSERT INTO "location" (name,country_id) VALUES ('Busan',16);
INSERT INTO "location" (name,country_id) VALUES ('Mumbai',17);
INSERT INTO "location" (name,country_id) VALUES ('Delhi',17);
INSERT INTO "location" (name,country_id) VALUES ('Bangalore',17);
INSERT INTO "location" (name,country_id) VALUES ('Sada',18);
INSERT INTO "location" (name,country_id) VALUES ('Nuuk',19);
INSERT INTO "location" (name,country_id) VALUES ('Sisimiut',19);
INSERT INTO "location" (name,country_id) VALUES ('São Paulo',20);
INSERT INTO "location" (name,country_id) VALUES ('Rio de Janeiro',20);
INSERT INTO "location" (name,country_id) VALUES ('Brasília',20);

-- user -- (15)
-- 1 to 5 -> normal
-- 6 to 7 -> verified
-- 8 to 10 -> admin
-- 11 to 15 -> banned
INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,location_id,photo) VALUES ('eduvidas@uporto.edu','Edu Vidas','$2y$12$PfXFiHTCOWKzeRfk0XtrDOcm7EecLGG8BYaw7nMfsi.JZ/Vae9JLG','My name is Edu Vidas and recently I joined the air forces and am now a pilot','2000-10-14','false','false',3,'89c4ade231a0522da19b3bcec43a06e01baffbbdb50c628f907299a212753c46.png');
INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,location_id,photo) VALUES ('anthony.fantano@music.com','Melon Fantano','fantaninho','The Internet busiest music nerd and its time for another review','1976-09-06','false','false',11,'e479991c7ff1f4b5f0afa29a2a9fb76c5081e5e975f13673a29d2c800fb1c97e.png');
INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,location_id,photo) VALUES ('jpegmafia@hotmail.com','JPEGMAFIA','yoooooo123','All your heros are cornballs. Dont at me homie','1978-07-20','false','false',12,'d62b420c12284d202280f1f68a023d0880b4baaae9a216e4369f77b9b1ae48bf.png');
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id)           VALUES ('hayo.mi.yazaki@faucibus.gov','Hayao Miyazaki','notghiblistu#','1963-12-17','false','false',37);
INSERT INTO "user" (email,name,password,birthday,banned,verified)                       VALUES ('last.normal@pretium.net','John Smith','johnsmith123','1958-08-18','false','false');

INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,location_id,photo) VALUES ('squarecunha@gmail.com','Cunha Cunha','$2y$12$PfXFiHTCOWKzeRfk0XtrDOcm7EecLGG8BYaw7nMfsi.JZ/Vae9JLG','Hi, my name is Cunha Cunha, also know as Perry the Platypus or Avatar Aang','1999-04-01','false','true',1,'ee6430845ce63b3519ac09a40de7e56955113f31b97b9836e0f6a04631479258.png');
INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,location_id,photo) VALUES ('rui.camacho@yandex.com','Camacho PLOG','$2y$12$PfXFiHTCOWKzeRfk0XtrDOcm7EecLGG8BYaw7nMfsi.JZ/Vae9JLG','Esqueçam as notas de PLOG. Subscrevam e leio algo que vos faça cultos','1933-05-15','false','true',4,'554877df1c6be183a2921079b84352b7184e925ea3c09b2cd1b089d79fdcce0c.png');

INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,location_id,photo) VALUES ('metadias@gmail.com','Meta Dias','$2y$12$PfXFiHTCOWKzeRfk0XtrDOcm7EecLGG8BYaw7nMfsi.JZ/Vae9JLG','This is kinda meta, but imagine imagining about your imagination','1999-12-24','false','false',2,'594d5804cc64d56061e5945befe14d2678ebbfcedf1c9a2b90ee4bf2881bfe1b.png');
INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,location_id)       VALUES ('cavaco@rep.gov.pt','Cavaco Silva','cavaca','Im old for this but I prefer reading posts than being ca vaca','1927-09-03','false','false',23);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id)           VALUES ('falcao.cunha@ridiculus.org','Falcao Cunha','falcaoecunha','1963-02-13','false','false', 41);

INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,location_id,photo) VALUES ('mcgun@outlook.com','MC Gun','$2y$12$PfXFiHTCOWKzeRfk0XtrDOcm7EecLGG8BYaw7nMfsi.JZ/Vae9JLG','The baddest MC in town. Dont f with me. Stay home Stay Safe','1999-07-19','true','false',1,'c874a548a180c44276f7729549ee8084c8f4dfd920fc5ab4a6adea7e8c09ae1a.png');
INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,photo)             VALUES ('socrates@prison.pt','Socrates Preso','preso44','Prisioneiro numero 44. Ex Primeio Ministro de Portugal. É a vida','1957-09-06','true','false','031fb3cfa122c1d78c1e080760f377e5d14a8492a2efbdfe18930a7fab6f79e1.png');
INSERT INTO "user" (email,name,password,bio,birthday,banned,verified,location_id)       VALUES ('zackfox@hendrerit.gov','Zack Fox','jesusizda1','Ima dip my balls into some thousand island dressin, Coz I got depression','1987-08-30','true','false',30);
INSERT INTO "user" (email,name,password,birthday,banned,verified)                       VALUES ('racist.kkk@ridiculus.org','David Duke','da-vid-du','1953-10-16','true','false');
INSERT INTO "user" (email,name,password,birthday,banned,verified)                       VALUES ('prozi.cuppon@advert.com','Rui Prozis','unas10desconto','1974-03-23','true','false');

-- user-num -- (86)
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('reagan@netus.edu','Macon Curtis','Drw[~#q8z/x{P','1968-10-11','false','false',20);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('zenaida@Maecenas.net','Vaughan Jones',']g4oGQ?_Vjp4&KZ=','1949-01-28','false','false',26);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('cassandra@amet.gov','Emi Montoya','@N&m>kT)zKSs(Lt','1963-04-27','false','false',41);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('brynne@aliquam.org','Jeanette Lewis','T}*Aoko1{yR>','1966-06-06','false','false',40);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('chantale@dolor.com','Adria Everett','d+p;&:ZQ','1969-11-05','false','false',32);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('garth@scelerisque.gov','Aaron Miller','PdCDF&sbs','1988-03-25','false','false',17);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('lara@lorem.org','Tasha Mayo','KK4{-ZtOe6r~Ih','1998-10-05','false','false',2);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('yvonne@Mauris.us','Keelie England','[yDdhM3(b-F@=','1976-02-03','false','false',43);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('drake@et.edu','Alexander Valentine','{bA[FfebT75/','1969-03-14','false','false',37);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('teagan@netus.com','Porter Henry','[{2}ixNe9','2004-08-14','false','false',21);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('connor@vestibulum.us','Sage Gill','A*$iLqbMUxgx?y$','1996-02-07','false','false',11);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('alfreda@pretium.gov','Darryl Boyd','OZFLhC6]4QWowU_','1935-01-28','false','false',15);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('hayley@erat.net','Velma Nicholson','t14`X1~?`*b-tbU','2006-03-12','false','false',32);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('moses@pretium.net','Emma Brady','pG2_BuN9@0V/G`Cf','1958-07-14','false','false',19);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('holly@condimentum.gov','Barrett Harper','?<vV*8gVXW~G-lq','1998-08-02','false','false',12);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('tyrone@sagittis.edu','Veronica Fulton','b){u59-0f+^il','1929-05-11','false','false',35);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('kasimir@massa.edu','Byron Swanson','K=Oxp/EnnJRu%)O','1982-03-06','false','false',49);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('kieran@eu.gov','Venus Bright',';I1ZV(!YIu6G>Z((','1937-02-01','false','false',13);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('hoyt@nec.us','Price Rogers','t2z%HNbQNt;@J','1945-08-10','false','false',29);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('boris@Nullam.us','Noelle Harper','<HQpP)z@sD=N-','1989-11-08','false','false',8);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('quinn@elementum.org','Amaya Terrell','#s$/:`&s<','1955-03-15','false','false',3);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('colt@Curabitur.net','Joshua Snider','k1+#<d4f;F+4LS','1942-09-24','false','false',7);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('berk@Fusce.org','Carissa Charles','PicCdK=YLn@%{[+x','1976-04-15','false','false',4);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('adele@scelerisque.com','Dacey Martin','O*~~9G#d!)mcon;G','1984-06-15','false','false',17);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('camden@sagittis.us','Emery Mathews','$YxwW[tJU','1983-07-29','false','false',47);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('graiden@a.edu','Vladimir Vasquez',':2J4.D*ReeE','1981-10-08','false','false',41);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('cleo@sollicitudin.org','Aileen Leonard','<lBg0_Jm$','1982-07-18','false','false',33);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('guy@accumsan.us','Raja Thompson','!i-/1CIh','1992-09-28','false','false',2);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('martin@felis.us','Moses Guzman','{GbjbAkA@eHm$?)','1988-11-18','false','false',31);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('naomi@posuere.edu','Slade Lawrence','SC~NJzRMT6]7','1939-10-23','false','false',44);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('jin@facilisis.gov','Rhea Herrera','b8b]nBsvN?>eu','1939-05-03','false','false',18);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('tatum@sem.com','Yetta Knapp','0pod<Y)V8q3T','1953-10-01','false','false',11);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('jerry@sem.edu','Heather Farley','GtpG(DqQb!:`#','1998-09-14','false','false',48);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('demetrius@Donec.us','Hakeem Copeland',' #Nr#uw_x','1960-11-24','false','false',43);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('indigo@pharetra.org','Jonah Castaneda',':)Y +/9S9bF0CaiU','1949-01-25','false','false',17);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('deacon@ridiculus.gov','Joshua Owens','^X`Hs)e{~O%T)_','1962-12-16','false','false',5);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('madeson@aliquam.gov','Brittany Townsend','>>$:`yYFJ;JA^','1994-01-30','false','false',47);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('nita@tincidunt.net','Stephanie Bonner','qbpf^tOA_kWI','1961-11-24','false','false',31);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('britanni@fames.us','Veronica Crane','kY{lAWu)Z5mCJ','1968-10-13','false','false',3);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('naida@mollis.org','Lynn Mcconnell','ANA#WLZ#L','1930-07-27','false','false',33);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('levi@imperdiet.org','Solomon Gregory','-7DX!e5*Kc2h','1955-04-11','false','false',45);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('irene@rhoncus.us','Charles Fulton',';y8d03v{A&P','1989-01-04','false','false',46);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('sylvia@quam.com','Ivan Steele','Bg9:PL3?','1981-09-07','false','false',45);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('heidi@montes.org','Teagan Flowers','k;4SsOy`->o8\\{','1950-02-23','false','false',30);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('zahir@laoreet.us','Joelle Simmons',' G<jS;Xx','1946-09-11','false','false',37);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('savannah@non.us','Beau Russell','NskNsF`hLN;','1955-10-13','false','false',33);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('yoko@blandit.us','Dawn Singleton','F[j^9m}&Msx','1965-05-08','false','false',15);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('rooney@dis.gov','April Franco','9`$R*s4]48Jn','2002-12-22','false','false',45);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('sawyer@justo.com','Tasha Patton','qU?Bm$[G3f%@q9','2000-04-09','false','false',38);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('celeste@commodo.org','Adam Beach','vvH*X*-H]QmBW&~7','1957-12-16','false','false',21);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('caryn@nibh.net','Darryl Armstrong','eCm\dF/5','2005-06-26','false','false',22);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('yoshio@felis.edu','Christen Jensen','{2Xig@AY','1929-09-30','false','false',6);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('kelly@dictum.us','Carlos Simon','+SiBD d?amZeBs@V','1942-01-29','false','false',43);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('abdul@odio.com','Carl Mejia','uZYl5I27Uk5@','2000-05-24','false','false',43);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('brennan@ipsum.gov','Lyle Parks','{Ud3q R0jA)','1928-08-30','false','false',7);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('leo@aptent.gov','Mikayla Schultz','mPTyAaN$j~WQPz@','1987-10-15','false','false',1);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('dante@odio.us','Mia Bender','+%b9])w@~','1988-02-13','false','false',19);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('armando@risus.com','Evelyn Lara','(W3N;z{o','1982-02-12','false','false',15);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('ori@lobortis.gov','Eric Ferguson','*O{Qw^Dcq:kp.-k','1980-10-18','false','false',28);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('marcia@pellentesque.net','Harlan Melton','`DWkvcp&Rs}L','2005-10-14','false','false',26);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('aladdin@nascetur.net','Xaviera Underwood','IEtUniv~','1983-06-08','false','false',26);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('ryder@Donec.gov','Oscar Nieves','?gG=m!A&-;@IM','1989-01-30','false','false',12);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('tad@arcu.net','Azalia Lang','_DLLH3dT3@q','1984-04-26','false','false',14);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('elijah@quis.gov','Audra White','Z\~%cqdIXF4Lx8-','1943-08-11','false','false',36);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('herrod@urna.net','Nita Gross',' kHlgfNW$8F;_Tg','1939-11-02','false','false',42);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('wang@euismod.us','Melvin Ewing','D.R:hy);gv','1957-03-12','false','false',27);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('gretchen@consectetuer.gov','James Dillard','jw*/93~@$-.QXE','1931-09-06','false','false',18);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('kitra@montes.org','Diana Jarvis','Yk5-ooJwq\J+h1','1960-12-16','false','false',24);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('pearl@aptent.gov','Ethan Yates','i4Erotr.#Ki&','1966-12-08','false','false',38);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('wynne@ligula.us','Sydnee Harris','nw k3APPw<8kNF_','1958-11-20','false','false',49);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('carolyn@dapibus.gov','Indira Harris','B?v+i{^dPcUtP*uw','1953-06-20','false','false',27);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('aladdin@fringilla.us','Tallulah Beach','7)SXY;57 Xd!:;R\','1995-10-30','false','false',12);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('brenden@auctor.us','Justine Fitzgerald','n}[>`zf1bZ.Ok','2001-01-16','false','false',45);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('naida@tellus.org','Robert Shannon','~~9B1;!#=(W9','2003-11-16','false','false',12);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('jameson@luctus.com','TaShya Cook','&d)NQC\^','1941-10-04','false','false',49);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('nyssa@nostra.edu','Marshall Duncan','0i[)C-7j','1934-02-22','false','false',18);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('chaney@scelerisque.org','Caleb Taylor','/5MU!#A?]HpJX[-=','1952-09-19','false','false',44);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('aileen@pretium.gov','David Reeves','%*#QK= kq!7U@aP{','1964-07-05','false','false',17);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('dora@sagittis.org','Tatyana Bowers',' kl+4mKiqg{s=C','1939-04-23','false','false',32);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('erich@et.gov','Jaden Mueller','+ZHXMv&0c%','1958-12-10','false','false',33);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('maite@nibh.net','Halee Kim','s@!DiVqv9~ZJ','1930-09-14','false','false',6);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('eve@Integer.edu','Arden Dawson','kQXX(4?$Zj','1958-01-01','false','false',38);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('gregory@arcu.us','Warren Barnes','` =n[rGn(p+!(','1999-05-13','false','false',22);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('yuli@Nullam.net','Magee Stanley','~=Ge*X7Yp','1981-02-25','false','false',41);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('neville@nonummy.edu','Dominic Whitehead','EFBygejI%','2004-05-05','false','false',49);
INSERT INTO "user" (email,name,password,birthday,banned,verified,location_id) VALUES ('plus.one@nonummy.edu','Manny Plus','plus1fds%','1966-09-02','false','false',7);


-- admin --
INSERT INTO "admin" (user_id,posts_deleted,comments_deleted,reports_solved,users_banned) VALUES (8,  32, 12, 2, 11);
INSERT INTO "admin" (user_id,posts_deleted,comments_deleted,reports_solved,users_banned) VALUES (9,  2, 23, 3, 9);
INSERT INTO "admin" (user_id,posts_deleted,comments_deleted,reports_solved,users_banned) VALUES (10, 63, 21, 1, 7);


-- ban (31) --
-- old fantano --
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2017-03-10','2018-05-28',2,8);
-- active bans (5) --
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2019-02-20','2021-05-28',11,8);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2019-04-07',NULL,12,8);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2017-07-21','2019-12-17',13,9);
INSERT INTO ban (ban_start,user_id,admin_id)         VALUES ('2018-03-26',14,9);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2020-01-29','2023-02-20',15,10);
-- old bans default users (20) --
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-07-12','2018-10-30',23,8);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2011-07-20','2017-07-12',75,10);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2013-12-15','2017-11-19',87,8);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2011-08-04','2016-11-19',56,9);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-12-28','2017-12-05',27,10);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-01-23','2019-02-15',41,9);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-10-30','2019-09-06',85,8);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-07-22','2018-09-03',68,9);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2011-04-07','2017-12-19',74,8);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2013-07-09','2019-07-23',68,10);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-07-28','2014-06-03',51,8);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-10-08','2017-01-01',75,9);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2013-01-20','2017-01-11',75,10);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2014-01-13','2017-11-02',23,8);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-12-12','2017-10-24',91,8);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-03-22','2018-12-07',78,10);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2013-10-19','2018-09-14',59,9);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-08-05','2017-09-21',20,9);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2013-07-05','2018-11-19',19,9);
INSERT INTO ban (ban_start,ban_end,user_id,admin_id) VALUES ('2012-11-05','2018-05-03',80,8);


-- badge (7) --
INSERT INTO badge (icon,name,description) VALUES ('fas fa-check-circle','Verified','User has successfully collected all the other rewards');
INSERT INTO badge (icon,name,description) VALUES ('fas fa-trophy','Champion','Post 5+ articles with more than 100+ likes');
INSERT INTO badge (icon,name,description) VALUES ('fas fa-star','Popular','Have 100+ subscribers');
INSERT INTO badge (icon,name,description) VALUES ('fas fa-certificate','Violet','Violet symbolizes harmony and community. You commented 10+ posts');
INSERT INTO badge (icon,name,description) VALUES ('fas fa-globe-europe','Mr WorldWide','Have subscribers of 10+ different countries');
INSERT INTO badge (icon,name,description) VALUES ('fas fa-bullseye','Targeted','Have a post with 10+ comments');
INSERT INTO badge (icon,name,description) VALUES ('fab fa-slack-hash','Tag Master','Used at least 10 different tags');


-- has_badge -- (2 + 14)
INSERT INTO has_badge (user_id,badge_id) VALUES (1,4);
INSERT INTO has_badge (user_id,badge_id) VALUES (1,5);

INSERT INTO has_badge (user_id,badge_id) VALUES (6,1);
INSERT INTO has_badge (user_id,badge_id) VALUES (6,2);
INSERT INTO has_badge (user_id,badge_id) VALUES (6,3);
INSERT INTO has_badge (user_id,badge_id) VALUES (6,4);
INSERT INTO has_badge (user_id,badge_id) VALUES (6,5);
INSERT INTO has_badge (user_id,badge_id) VALUES (6,6);
INSERT INTO has_badge (user_id,badge_id) VALUES (6,7);
INSERT INTO has_badge (user_id,badge_id) VALUES (7,1);
INSERT INTO has_badge (user_id,badge_id) VALUES (7,2);
INSERT INTO has_badge (user_id,badge_id) VALUES (7,3);
INSERT INTO has_badge (user_id,badge_id) VALUES (7,4);
INSERT INTO has_badge (user_id,badge_id) VALUES (7,5);
INSERT INTO has_badge (user_id,badge_id) VALUES (7,6);
INSERT INTO has_badge (user_id,badge_id) VALUES (7,7);


-- tag (10 + 1) --
INSERT INTO tag (name, photo, color) VALUES ('health', 'health.jpg', '#67aeca');
INSERT INTO tag (name, photo, color) VALUES ('lifestyle', 'lifestyle.jpg', '#007761');
INSERT INTO tag (name, photo, color) VALUES ('design', 'design.jpg', '#e52a6f');
INSERT INTO tag (name, photo, color) VALUES ('business', 'business.jpg', '#06648c');
INSERT INTO tag (name, photo, color) VALUES ('sports', 'sports.jpg', '#675682');
INSERT INTO tag (name, photo, color) VALUES ('politics', 'politics.jpg', '#06648c');
INSERT INTO tag (name, photo, color) VALUES ('music', 'music.jpg', '#67aeca');
INSERT INTO tag (name, photo, color) VALUES ('tech', 'tech.jpg', '#675682');
INSERT INTO tag (name, photo, color) VALUES ('science', 'science.jpg', '#007761');
INSERT INTO tag (name, photo, color) VALUES ('education', 'education.jpg', '#e52a6f');
INSERT INTO tag (name, color) VALUES ('racism', '#626d79');


-- content --

-- posts -- 1 to 27

-- health + science
INSERT INTO content (body,likes_difference,author_id) VALUES ('New corona outbreak hits the US Pacific Northwest!! An outbreak of the highly contagious coronavirus has reached the US Pacific Northwest, according to US health officials. The outbreak began in the San Diego area of California, and has since spread to other parts of the state. The US Centers for Disease Control and Prevention (CDC ) said that more than 400 confirmed cases had been confirmed in the region. According to Reuters, the most severe case is a woman who is in her 80s who recently contracted the virus while travelling to China . The virus has so far only killed two people , who died  from the respiratory infection. There is no known cure for the virus, but the symptoms are typically similar to those of a flu: fever, cough, fatigue, headache, and sore throat. The virus is spread through direct contact with body fluids , such as saliva , sweat , or mucus , of someone who has been infected. There have been no reports of people catching the virus from  eating infected food, so there is no  risk of  getting the illness while eating out at restaurants or other public places. The most severe cases have resulted in death , but there have been no cases of young children infected with the virus. However, health officials are concerned that if people with compromised immune systems ( such as immunocompromised people or people with chronic lung disease, like asthma or COPD ) are exposed to  the virus, they may be more susceptible to complications such as severe pneumonia or death. More than 50 countries around the world have now reported more cases of corona. There is no vaccine or treatment  for the disease. However, a new treatment developed by scientists at the Pasteur Institute in Paris , has been  found to offer the best hope of preventing it: inactivation . Researchers have isolated a protein found in bats that can be used as an anti- corona vaccine.',
                                                                100,6);
-- politics
INSERT INTO content (body,likes_difference,author_id) VALUES ('
The UK left the EU on 31 January 2020 and has now entered an 11-month transition period.
During this period the UK effectively remains in the EU s customs union and single market and continues to obey EU rules.
However, it is no longer part of the political institutions. So, for example, there are no longer any British MEPs in the European Parliament.
Future trade deal
The first priority will be to negotiate a trade deal with the EU. The UK wants as much access as possible for its goods and services to the EU.
But the government has made clear that the UK must leave the customs union and single market and end the overall jurisdiction of the European Court of Justice.
Time is short. The EU could take weeks to agree a formal negotiating mandate - all the remaining 27 member states and the European Parliament have to be in agreement. That means formal talks might only begin in March.
The government has ruled out any form of extension to the transition period.
If no trade deal has been agreed and ratified by the end of the year, then the UK faces the prospect of tariffs on exports to the EU.
The prime minister has argued that as the UK is completely aligned to EU rules, the negotiation should be straightforward. But critics have pointed out that the UK wishes to have the freedom to diverge from EU rules so it can do deals with other countries - and that will make negotiations more difficult.
It is not just a trade deal that needs to be sorted out. The UK must agree how it is going to co-operate with the EU on security and law enforcement. The UK is set to leave the European Arrest Warrant scheme and will have to agree a replacement. It must also agree deals in a number of other areas where co-operation is needed.
',
                                                                100,6);
-- sports
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Torrinheira s headbutts are one off the most dangerous techniques, and his head is very large.
He is quite a dangerous fighter when drunk, as it appears his brain is functioning as a primitive thought system. (It seems like it does not even work properly if he is in control of his body.) He is also very fast, having killed many people in one night . (This may be due to his alcohol induced rage, and/or his ability to keep his balance when headbutting  someone.)
Usually he is quite a nice  guy, but after getting inebriated he is prone to aggressive behavior, and will attack people he considers his enemy.
His last fight was against a guy called David, who he easily beat because of the height  difference between them. He was almost losing when he decided to do his go to move, which dropped his opponent immediately.
The first time he killed  someone was around 2 years ago when he took out his best friend because he said "Hey, you are the only friend who is not going to make it!" The best part is that he was right. He failed in  every other area. ( His brain was  not developed enough, and his head was all swollen due to the headbut ting.)
The best time to fight with him is when he is in calm, relaxed state, and will not attack you.
',
                                                                100,6);
-- education
INSERT INTO content (body,likes_difference,author_id) VALUES ('
PLOG grades out now.
After a long wait finally Prof. Rui Camacho let the students see their grades.
His excuse was a little strange:
"O que temos que ter sempre em mente é que a execução dos pontos do programa aponta para a melhoria das condições financeiras e administrativas exigidas. Nunca é demais lembrar o peso e o significado destes problemas, uma vez que o fenômeno da Internet não pode mais se dissociar das regras de conduta normativas. Caros amigos, o julgamento imparcial das eventualidades exige a precisão e a definição das formas de ação. A prática cotidiana prova que a necessidade de renovação processual nos obriga à análise do sistema de participação geral. Neste sentido, o desafiador cenário globalizado ainda não demonstrou convincentemente que vai participar na mudança dos modos de operação convencionais.
No entanto, não podemos esquecer que o aumento do diálogo entre os diferentes setores produtivos facilita a criação dos relacionamentos verticais entre as hierarquias. Desta maneira, a contínua expansão de nossa atividade acarreta um processo de reformulação e modernização do sistema de formação de quadros que corresponde às necessidades. Percebemos, cada vez mais, que a expansão dos mercados mundiais obstaculiza a apreciação da importância do processo de comunicação como um todo. Acima de tudo, é fundamental ressaltar que a consulta aos diversos militantes auxilia a preparação e a composição dos índices pretendidos. Assim mesmo, o início da atividade geral de formação de atitudes maximiza as possibilidades por conta da gestão inovadora da qual fazemos parte."
Yeah trully gibberish. It took him 1 year and this is what he has to say...
Students are preparing a protest to make him get sacked.
',
                                                                100,6);
-- lifestyle
INSERT INTO content (body,likes_difference,author_id) VALUES ('
My friends thought I was crazy when I hired Gizelle as our new babysitter.* “She’s too hot,” they said. “She will bring wolves to your door,” another said. The “wolves” being other dads who would become enamored with her hypnotic beauty.
My friends were not being hyperbolic. Leonardo would have wept over Gizelle’s perfect hip-to-waist ratio and porcelain skin. Her hair, the color of softly churned butter, fell in long plaits to her hips. At five feet nine inches, she towered over my kids with legs and arms that seemed to multiply like Shiva’s limbs.
Ever try on one of those flowy, bohemian dresses and think — I look like a circus tent. Who the hell would this look good on? The answer is Gizelle.
The first week she was with us, I put her perfectly sculpted, baby giraffe thighs to work. I made her accompany me when making heavy purchases. Not to help lift things. Oh no. While in the past, I had to lug the potted rubber tree plant to the car on my own, I suddenly had three able-bodied gentlemen rolling up their sleeves with a “Can I help you with that ma’am?” (Men oddly always called me “ma’am” in Gizelle’s presence.)
If we went grocery shopping together, random male employees would stop to ask us if we had found everything we were looking for. Then with beads of perspiration forming at their temples and the whiff of pheromones gone awry, I would have to crush their hopes with, “No, I think we can find the peaches without your help.”
She was like some mystical Snow White, making the male forest creatures bow before her. I would watch her long, golden hair swoosh around her Botticelli hips and wonder — what would it be like to be that beautiful?
Then the second thought would always follow…why was Gizelle so damn unhappy all the time?
',
                                                                100,6);
-- music + tech
INSERT INTO content (body,likes_difference,author_id) VALUES ('While not normally known for his musical talent, Elon Musk is releasing a debut album  on February 17. He is already got a record deal with Sony Music and has worked with many other big artists including Justin Bieber and Jennifer Lopez. His upcoming record is set to be called "El on Musk – The Story of Everything,"   and it will include his take on the life and death of his late friend. "In the end, it is my mission to tell the story of what it is like to love someone who is gone ,"  says the Tesla CEO . "As Ive said before: you can never have enough of anyone . ".  Musk s music video for his new song has already gone viral and has received over 8 million views on YouTube. A version of the song featuring Beyonce is also in the works. This is an interesting and unique way of  celebrating the passing of a good friend. For many of us, his passion, drive and ingenuity was what attracted us to Elon Musk. His ideas for the future of space travel, transportation and electric cars are incredible and have given us more reasons to believe in humanity. If you are  looking for some inspiration on how to say goodbye to someone you love, make sure to check out the video.',
                                                                100,7);
-- tech + health
INSERT INTO content (body,likes_difference,author_id) VALUES ('US government officials are using cellphone location data from the mobile ad industry —not data from the carriers themselves— to track Americans’ movements during the coronavirus outbreak, the Wall Street Journal reports. The Centers for Disease Control and Prevention, along with state and local governments have received cell phone data about people in areas of “geographic interest,” the WSJ reports. The goal is to create a government portal with geolocation information from some 500 cities across the country, to help ascertain how well people are complying with stay-at-home orders, according to the WSJ. One example of how the anonymized data was reportedly used: Researchers discovered large numbers of people were gathering in a New York City park, and notified local authorities. The use of even anonymized data raises myriad privacy concerns, with privacy advocates urging limits on how such data can be used and prevent its use for other purposes, the WSJ reported. Other countries have used cell phone data to track citizens’ movements during the pandemic; mobile carriers in the European Union have reportedly shared some data with health authorities in Italy, Germany, and Austria. although details about specific patients were not included. Israel authorized the use of cellphone location data to track the virus, with data to be used in a “focused, time-limited and limited activity,” according to The New York Times. China’s tracking system sends information to law enforcement officials, while Taiwan’s “electronic fence” alerts authorities when a quarantined person moves too far away from their home. And South Korea used cell phone location data to create a public map of coronavirus patients, to track where people may have been exposed. Cell phone carriers in the US told the WSJ that have not been asked by the government to provide location data. But the Washington Post reported on March 17th that the federal government was in “active talks” with Facebook, Google, and other tech companies, to figure out how to use location data from phones. ',
                                                                100,7);
-- lifestyle
INSERT INTO content (body,likes_difference,author_id) VALUES ('
“There was blood everywhere. But wait, let me backtrack.”
“This piercing voice was screaming so loudly that I could hear her from the second floor. She lives on the fourth. I’m a nurse, so when I hear somebody screeching, “Heeeeelp! Heeeeelp!” I spring into action. I jolted out of my apartment and checked all the rooms on my floor. Then I sprinted up the stairs to the third floor. The screaming wasn’t coming from there either.
I raced up to the fourth floor, and I heard it loud and clear. “Help! Help me right now!” I crept into apartment 4F and there was Marcy. Laying on the floor. Completely undressed. In a pool of blood. There was blood everywhere.”
I caught up with my friend Lara not too long ago, and she showed up fully exasperated. She arrived at the gym (we were doing a workout together) already sweating, and even before giving me a hug, she held her pointer finger up and then keeled over and placed her hands on her knees. “Jordan, you’ll never believe what just happened to me.” She proceeded to describe the scene above.
Fortunately, this story has a happy ending. Marcy fell off of her bed and cut her knee. Yes, there was a lot of blood, but Lara cleaned her up, got her to the hospital, and the doctors stitched her and had her home within the next 24 hours. But that’s not everything. Lara mentioned how Marcy was so appreciative of her help that she invited her over for wine and pizza later that week.
The lessons shared below are from Lara’s conversation the night her and Marcy had wine and pizza together. A night that began at 7 pm and did not end until 3 in the morning.
After standard pleasantries, deciding which bottle they were going to break out first, and from which pizza place they were going to order, Marcy got straight to the point.
“Lara, I am 99-years old. I have no family, no friends, and I do not love my life.”
“When I saw you come into my apartment the other day and you helped me for no apparent reason, I took that as a sign. You saw me at my worst. Naked. Cold. Alone. Afraid. I think when somebody sees us in our purest, most vulnerable state, there is an instant connection.”
“I am not going to make many more connections during my time left here, but I think I can make at least one. Let’s take this night to connect. Let’s take this time to allow me to share with you my life and all that I have learned. I don’t know you, but I do know me. And I know it’s cliché, but I don’t want you to make the same mistakes I did. I want to share my mistakes, my regrets. So, tonight, we drink, we eat, and we talk about life.”
Lara simply smiled and filled their first glass with a Lambrusco, a sparkling red wine.
',
                                                                100,7);
-- design
INSERT INTO content (body,likes_difference,author_id) VALUES ('
In August, 1971, a Stanford psychology professor designed a simulated jail environment in the basement of Jordan Hall, where 18 college students would role-play prisoners and guards during 2 weeks.
The hypothesis: witness how people would abuse power when given the proper circumstances.
It was called the Stanford Prison Experiment.
The experiment became widely accepted by the psychology community. It has been cited in hundreds of papers. Films and documentaries used it as inspiration. No one ever questioned the validity of the experiment. No one but Ben Blum.
It was recently discovered that the Stanford Prison Experiment was a deceit. Philip Zimbardo, the psychologist professor behind it, paid both guards and prisoners to act in order to exacerbate the premise of the experiment.
All of a sudden, a lot of decisions by psychologists were made based on a false assumption.
5 years ago, when I was embarking on the journey of becoming a UX practitioner, I was impressed by the willingness of the community to share knowledge, as well as the endless sea of available resources to consult: case studies describing full-blown UX processes, templates and freebies to experiment with, and lessons learned by talented designers while practicing design.
But all these available resources also came with a high price: information overload.
',
                                                                100,7);
-- politics + education
INSERT INTO content (body,likes_difference,author_id) VALUES ('
In May of 2019, I was accepted to the Eli Whitney student program at Yale University. At 52, I am the oldest freshman in the class of 2023. Before I was accepted, I didn’t really know what to expect. I had seen the infamous YouTube video of students screaming at a faculty member. I had seen the news stories regarding the admissions scandal and that Yale was included in that unfortunate business. I had also heard the students at Yale referred to as “snowflakes” in various social media dumpsters and occasionally I’d seen references to Ivy League students as snowflakes in a few news sources.
I should give a bit of background information. I was an unimpressive and difficult student in public schools. I joined the military at 17 and spent close to 26 years in the US Navy. I was assigned for 22 of those years to Naval Special Warfare Commands. I went through SEAL training twice, quit the first time and barely made it the second time. I did multiple deployments and was wounded in combat in 2009 on a mission to rescue an American hostage.
Every single day I went to work with much better humans than myself. I was brought to a higher level of existence because the standards were high and one needed to earn their slot, their membership in the unit. This wasn’t a one-time deal. Every time you showed up for work, you needed to prove your worth.
The vetting process is difficult and the percentage of those who try out for special operations units and make it through the screening is very low.
In an odd parallel, I feel, in spite of my short time here, the same about Yale.
After receiving my acceptance email and returning to consciousness, I decided to move to Connecticut and do my best in this new environment. Many people have asked me why I want to attend college at 52, and why at an Ivy League institution like Yale? I could have easily stayed in Virginia and attended a community college close to my home. Well, based on my upbringing in the military, I associated a difficult vetting process with quality and opportunity. I was correct in that guess. More importantly, I simply want to be a better human being. I feel like getting a world-class education at an amazing institution like Yale will help me reach that goal. Are there other places to get a great education? Of course, but I chose Yale.
',
                                                                100,7);
-- design
INSERT INTO content (body,likes_difference,author_id) VALUES ('
I’ve been using Twitter since 2012, I have seen the product evolve and I can say that I have grown with the product and the company. In my earlier years, Twitter was merely a product to me but it has become way more than that now — it has become part of me and my daily routine. Now, there are a million reasons you can give for that — the nice interface, cooler features, my lack of friends and a life — but I think a major reason why the product has stuck is because I have been able to build an emotional connection with it. I personally think my connection with the product can be partly attributed to the simple and intuitive microinteractions on the platform. Interactions like; favouriting a post, changing the favourite interaction from the star icon to the heart icon, the pull to refresh interaction, and all the other invisible interactions are what keeps the platform interesting, and that’s because of how much they all mimic real world interactions and conversations.
Products and interactions like this, (alongside my amazing colleague, Torian) inspired me to spend time understanding the intersection between microinteractions and emotional design, and how we can leverage microinteractions in creating experiences that stand as an extension of the physical world. Microinteractions are seemingly small alluring moments built into an experience that has a huge impact on the overall experience of users. Dan Saffer, who in my opinion is the father of modern microinteractions theory, defined microinteractions as contained product moments that revolve around a single use case and have one main task. A good example of a microinteraction would be; liking a post on Facebook or Instagram, or favouriting a post on Twitter, or even picking a password and setting an alarm.
Microinteractions in my opinion is a cool way for us to replicate the physical model of body language (gestures, facial expressions, haptics and physical movements) in our digital systems. According to the 7% rule, 93% of a conversation is nonverbal — 55% being body language and the other 38% is the tone of voice (which represents writing style and the information structure). I believe the same holds for product design, so 93% of the “conversation” is not based on components and copy, it’s based on the interaction design, with the focus being on how users communicate with products and how the product communicates with the user. This means we need to start thinking beyond visceral emotional design and seek out opportunities for behavioural and reflective emotional design.
I think conversations around replicating physical communication in digital systems is heavily understated, particularly because we tend to forget that communication is not just talking to someone, it’s a continuous loop of listening, processing and giving feedback. In the design of digital products, we need to start thinking about interaction design in the same guise, and start asking ourselves questions related to the interaction of users with our product and vice versa. Doing this will result in more humane designs, which is where I believe emotional design theory comes into play. The way I see it, “we need to design interactions and microinteractions in a way that prevents humans from switching modes (modes being context and mental models), in a natural way that replicates the physical model of body language.” Users not having to switch modes and things working the way they expect them to are key tenets of behavioural emotional design, this means that there is opportunity for us to use microinteractions to implement behavioural emotional design.
I intentionally used the word “humans” in the previous statement because sometimes, we focus so much on designing for users and customers that we forget to design for their humanity. The way I see things, our users and customers are first humans, then users or customers. So this is a push for designers to go a level deeper, for us to go past user-centricity to human-centricity, which is where reflective emotional design theory comes into play. Whether we choose to acknowledge it or not, our product/services evoke certain emotions in people. Most products choose to ignore this fact and focus rather on creating aesthetically pleasing interfaces, but I am of the opinion that they are missing out on a big opportunity to create a personal connection with their users. Focusing on forming a personal bond with the customers may not have a tangible short run benefit but in the long run, it makes your product sticky and initiates customer loyalty which has long term benefits on revenue, customer satisfaction and overall business outlook.
',
                                                                57,6);
-- tech
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Whether it’s fighting the belief that all gamers are antisocial weirdos or advocating for more inclusiveness in the medium’s protagonists, we here at OGN have always been dedicated to telling the truth about games. That is why we have undertaken a historic and undoubtedly forbidden investigation into one of the most common misconceptions about gaming: The idea that they make gamers more prone to violence. To do that, we adopted a young infant from a local orphanage, locked him inside of a cabin in the woods, and exposed him to nothing but violent video games for the past 12 years. The end result? It turns out critics are dead wrong. The child didn’t turn out violent at all, even though we have to admit he became an extremely weird individual.
We’re calling it now: Any naysayers out there who have painted games as the source of society’s violence need to admit their mistake, because while there’s definitely something off with this kid, it has nothing to do with violence.
The boy—whom we named “Sonic” after the titular hedgehog and because that is the one word he ever learned to speak—has never once attacked or even threatened another human being. In fact, he seems mostly content to just continue playing the games that have constituted his entire life since he first developed object permanence. That being said, Sonic can mostly only speak in gibberish sentences, and even a small amount of eye contact often ends with him foaming at the mouth and hiding in a closet for hours. But still, no violence.
To create a perfectly controlled environment for our experiment, young Sonic has been kept in a windowless room for his entire life. For years, he’s been fed nothing but mashed bananas and oatmeal and has never been allowed contact with any humans other than our one OGN researcher. In fact, to ensure he has a constant view of a violent video game, Sonic hasn’t even seen daylight in his decade on this earth. In this perfectly controlled environment, he has been either playing or watching some of the goriest and most bloody games ever produced from Mortal Kombat and the Postal series to Manhunt and Gears Of War.
Then came the long-awaited culmination to the experiment. Last month, we brought Sonic out of his locked habitat and into the OGN offices, where we enrolled him a local public school. What proved incredible to both us—and any detractors of the gaming medium—is that he has not shown a single impulse to harm another living thing. Even when he has been bullied for his name or translucent skin and feral appearance, he has never lashed out, instead electing to press his hands against his ears and shake his head wildly or run out of the room in this strange, toddling walk that’s almost akin to a penguin.
',
                                                                23,6);
-- music + business
INSERT INTO content (body,likes_difference,author_id) VALUES ('
In their many (justified) laments about the trajectory of their profession in the digital age, songwriters and musicians regularly assert that music has been “devalued.” Over the years they’ve pointed at two outstanding culprits. First, it was music piracy and the futility of “competing with free.” More recently the focus has been on the seemingly miniscule payments songs generate when they’re streamed on services such as Spotify or Apple Music.
These are serious issues, and many agree that the industry and lawmakers have a lot of work to do. But at least there is dialogue and progress being made toward new models for rights and royalties in the new music economy.
Less obvious are a number of other forces and trends that have devalued music in a more pernicious way than the problems of hyper-supply and inter-industry jockeying. And by music I don’t mean the popular song formats that one sees on awards shows and hears on commercial radio. I mean music the sonic art form — imaginative, conceptual composition and improvisation rooted in harmonic and rhythmic ideas. In other words, music as it was defined and regarded four or five decades ago, when art music (incompletely but generally called “classical” and “jazz”) had a seat at the table.
When I hear songwriters of radio hits decry their tiny checks from Spotify, I think of today’s jazz prodigies who won’t have a shot at even a fraction of the old guard’s popular success. They can’t even imagine working in a music environment that might lead them to household name status of the Miles Davis or John Coltrane variety. They are struggling against forces at the very nexus of commerce, culture and education that have conspired to make music less meaningful to the public at large. Here are some of the most problematic issues musicians are facing in the industry’s current landscape.',
                                                                63,6);
-- business
INSERT INTO content (body,likes_difference,author_id) VALUES ('
On Sunday, New York announced that the state now accounts for roughly 5% of coronavirus cases worldwide, and nearly 2,000 of those who tested positive for the virus in the state have been hospitalized.
In response, a growing number of governors, from New York to California, have ordered various restrictions on public gatherings and businesses. Such measures, necessary though they are, have crippled the economy at every level, from corporate to small business to individual.
Recognizing the moral imperative of the moment, Congress has proposed a $1.8 trillion economic stabilization package to aid families and businesses affected by the pandemic. (That’s nearly double the size of the expected federal budget deficit this year, but market stabilization and Americans’ well-being takes precedence in a moment like this.)
A bailout of this size and scope is unprecedented. Every industry is being devastated by the coronavirus. The American public and industries need immediate relief, and for the first time, voting in favor of a bailout will not cost an elected official their position. There are no conversations around deficit spending when the Las Vegas Strip is closed for business.
Unlike businesses selling goods like clothing or food, barbers can’t transition their business to online commerce or takeout service; there are no unemployment benefits to apply for. A vanity service doesn’t immediately come to mind for a bailout — and it won’t without a representative convincing legislators of its worth.
',
                                                                4,7);
-- sports
INSERT INTO content (body,likes_difference,author_id) VALUES ('
San Francisco 49ers quarterback Colin Kaepernick told reporters today that he will no longer kneel in protest during the anthem to raise awareness about police violence against people of color. And it’s all thanks to coming across one of your aunt’s posts on Facebook about the issue.
“It had eight likes and three shares so it pretty much went viral and landed in my feed,” Kaepernick said of the post your aunt put up last night at 10:11 p.m. after she finished watching Designated Survivor. “She presented things in a way I had not considered before. For example, it turns out that regardless of what I say, I hate the troops. And her uncle served in the Coast Guard in the late ’70s so it really hits home for her. Also, she made it clear that because I make a lot of money, there is no reason for me to complain about anything and that I’m actually the racist one when you break it all down. So silence it is for me from here on out.”
The quarterback said he briefly considered commenting on your aunt’s post to thank her for her wisdom, but then remembered that she made the case that he should just “shut his mouth and be grateful.”
“I don’t deserve to speak out like your aunt does,” said Kaepernick. “She is using her platform to say how she feels and that’s just how it is, even if I didn’t agree. It’s her right and everybody’s right to use what means they can to say what they think. It’s just not my right, unfortunately.”
Kaepernick said he also liked a post on your aunt’s page celebrating that it’s “Only one more day to Friday!”
“You see, Fridays are fun because it’s the end of the standard work week,” said the quarterback. “Although a lot of people of color in this country and those born into poverty have to work nights and weekends because of systematic economic disparity in the United States. Oh, man. I’m so sorry. I didn’t mean to say that. Please forgive me.”
',
                                                                42,7);
-- science
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Global sea ice is retreating at unprecedented speed with its impact already being felt across the globe, a leading scientist has warned.
While ice in the Arctic is close to record lows, the Antarctic has seen sea ice running at lowest ever levels for this time of year since records began.
Professor Peter Wadhams, head of the polar ocean physics group at Cambridge University, said rates of ice growth in winter had slowed and rising temperatures were causing it to melt faster in the summer, causing a dramatic reduction in area and thickness.
He warned the global repercussions of the reduction of sea ice were already being felt, long before the ice has fully disappeared.
“As the ice area gets less, you are changing the albedo of the earth, which is the fraction of solar radiation that gets reflected straight away back into space, so you are absorbing radiation which warms the earth quicker creating a feedback effect as the ice retreats,” he told The Independent.
“The only secure way of stopping the sea ice to retreat is stopping warming the climate and that is really by reducing our carbon dioxide emissions.”
He also warned of the disastrous implications melting sea ice had for rising sea levels across the world.
',
                                                                85,7);
-- tech
INSERT INTO content (body,likes_difference,author_id) VALUES ('
You’re almost finished with the long grind of interviewing. You’re almost free of the stress associated with interviewing at big tech companies. You’re practically envisioning sending off a final “I’ll sign” email.
But you’re not quite there yet.
There’s one last hurdle to clear: negotiating an offer.
You’ve shown that you can interview, but how well can you close a deal?
Late last year I interviewed at six top companies in Silicon Valley in six days, and stumbled into six job offers. I had one last task to make sure my work wasn’t in vain. Here are the principles I had, the rules I followed, and what I did to negotiate an offer worth $100,000/year more than I planned on.
',
                                                                0,1);
-- tech + health
INSERT INTO content (body,likes_difference,author_id) VALUES ('More than 100 YouTube stars have recorded a video message urging their fans to "stay home" during the coronavirus outbreak. The video is introduced by entertainer JJ Olatunji, known online as KSI, who has more than 21 million subscribers on the video clip platform. "We are here looking to spread awareness on the UK government s current advice to stay at home," he says. The video was posted by the Sidemen, who have 7.6 million subscribers. The group, which KSI is part of, came up with the idea for the montage themselves.',
                                                                3,2);
-- tech
INSERT INTO content (body,likes_difference,author_id) VALUES ('
I am privileged to live in Silicon Valley. I was born here, I grew up here, and now I work here as a product manager at Google. The weather is lovely, the crime rate is low, and the schools are well funded. The adults have cushy jobs and the kids have endless resources. People feast on $15 sushirritos and $6 Blue Bottle coffees. The streets are filled with Teslas and self-driving cars.
It’s a place of opportunity. Many new graduates, myself included, are making six-figure salaries straight out of college, plus equity, bonuses, and benefits on top of that. I get unlimited free food at work — three full meals a day and as many snacks as I want in between. There’s a place to do laundry and get a haircut. There’s even a bowling alley and a bouldering wall.
This is Silicon Valley. Who wouldn’t want to live here?
When I was in eighth grade, over a six-month period four students at a nearby school committed suicide by jumping in front of the Caltrain. During my sophomore year of high school, a schoolmate I used to walk with to the library took her own life. In my senior year, every single one of my peers had a college counselor. Some paid up to $400 an hour for counselors to edit their essays, and I witnessed other students paying to have their essays literally written for them. My classmates cried over getting an A- on a test, cried over getting fewer than 100 likes on their profile pictures, and cried over not getting into Harvard. (I admit, I cried over that one, too.) They pulled multiple all-nighters every week to survive their seven AP classes and seven after-school activities, starved themselves to fit in with the “popular kids,” stole money from their parents to buy brand name clothing, and developed harrowing mental health disorders that still persist today, years after high school graduation.
This is Silicon Valley.
',
                                                                -8,3);
-- tech
INSERT INTO content (body,likes_difference,author_id) VALUES ('
A few months ago I wrote about how you can encrypt your entire life in less than an hour. Well, all the security in the world can’t save you if someone has physical possession of your phone or laptop, and can intimidate you into giving up your password.
And a few weeks ago, that’s precisely what happened to a US citizen returning home from abroad.
On January 30th, Sidd Bikkannavar, a US-born scientist at NASA’s Jet Propulsion Laboratory flew back to Houston, Texas from Santiago, Chile.
On his way through the airport, Customs and Border Patrol agents pulled him aside. They searched him, then detained him in a room with a bunch of other people sleeping in cots. They eventually returned and said they’d release him if he told them the password to unlock his phone.
',
                                                                2,5);
-- tech VF
INSERT INTO content (body,likes_difference,author_id) VALUES ('
I’ve been programming in Object Oriented languages for decades. The first OO language I used was C++ and then Smalltalk and finally .NET and Java.
I was gung-ho to leverage the benefits of Inheritance, Encapsulation, and Polymorphism. The Three Pillars of the Paradigm.
I was eager to gain the promise of Reuse and leverage the wisdom gained by those who came before me in this new and exciting landscape.
I couldn’t contain my excitement at the thought of mapping my real-world objects into their Classes and expected the whole world to fall neatly into place.
I couldn’t have been more wrong.
',
                                                                7,8);
-- tech V2
INSERT INTO content (body,most_recent,likes_difference,author_id) VALUES ('
I’ve been programming in Object Oriented languages for decades.
The Three Pillars of the Paradigm are Inheritance, Encapsulation, and Polymorphism.
I couldn’t contain my excitement at the thought of mapping my real-world objects into their Classes and expected the whole world to fall neatly into place.
I was so so wrong.
',
                                                                'false',-3,8);
-- tech V1
INSERT INTO content (body,most_recent,likes_difference,author_id) VALUES ('
I’ve been programming in OOL for decades and they suck.
I was so excited at the thought of mapping my real-world objects into their Classes and expected the whole world to fall neatly into place.
I was dead wrong.
',
                                                                'false',-7,8);
-- health
INSERT INTO content (body,likes_difference,author_id) VALUES ('
It started with an artichoke. Or rather, it started with my inability to recall the word artichoke, even though I was holding one in my hand. “What did you get for dinner?” my partner Will asked from the other room, and I said, “Salmon and… ” My brain went blank. Or rather it went from blank to asparagus, even though I knew that asparagus, while in the correct spiky vegetable ballpark, was wrong.
“Yes?” said Will.
I started to panic. Words are my stock-in-trade. They’re how I make my living. If I couldn’t come up with a simple word for the vegetable right there in my own hand, who was I? I carried the mystery object into the room where Will was working. “What is this?” I said. “I can’t remember how to say it.”
He looked alarmed. “You mean… an artichoke?” He smiled. Was this some sort of a joke?
My relief was palpable. “Oh my god, yes! Thank you!” And yet I was still disturbed. What just happened? I’d been having what I thought were all the normal issues with word recall, keys and glasses locating, and wait-why-did-I-just-go-into-this-room moments over the last few years after turning 50, but this felt different somehow, more disturbing. More urgent.
I immediately Googled “memory loss menopause,” and 13.8 million hits appeared on my screen. Was memory loss an inevitable by-product of menopause? And if so, why? I started digging. And that’s when I stumbled upon a recent op-ed in the New York Times by neuroscientist Lisa Mosconi, who is studying the link between menopause and Alzheimer’s. The question she asked herself, in her research, was a deceptively simple one: Why do twice as many women get Alzheimer’s as men? The statistics with regard to women’s longevity versus men’s cannot explain away this enormous discrepancy. Could menopause offer any answers?
',
                                                                0,9);
-- health
INSERT INTO content (body,likes_difference,author_id) VALUES ('
At 3 p.m. on a Monday afternoon, death announced it was coming for him. He was only eight years old; his cancer cells were not responding to treatment anymore. His body’s leukemic blast cell counts were doubling daily. Bone marrow was no longer making red or white blood cells, not even platelets. The marrow was only churning out cancer cells. In a process similar to churning butter, his blood was thickening with homogenous, malicious content: cancer. And like churning butter, it was exhausting work. The battered remnants of his healthy self were beaten down by chemo. And yet, every fiber pressed on.
He was so very tired. You could see it in his eyes. At the same time, you could see his love. His love for life was front and center. His love for sweetness crystalized on his tongue in the taste of sun-soaked strawberries. His love for satisfaction could be heard in the snapping sound of a puzzle piece set in place. His love for the simple, soothing smells of lavender emanating from a medicine ball was cherished, as was the fact that he could still hold a ball in his hands. He loved life down to the core, as only an eight-year-old can, and he was doing everything he could to stay alive.
Death was easy to detect. It was right under our eyes, sending the simplest of signals. No appetite. Breathing strained. Cold hands and feet, meaning compromised blood flow. Ankles swollen. Standing up was becoming nearly impossible. His body was shutting down. But it was his temperature that told us the landslide of disease was accelerating and about to swallow us whole.
At 3 p.m. on a Monday afternoon, his temperature was 107.2 degrees.
',
                                                                0,10);
-- music
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Nicknamed "Pappy Grove", Manu Dibango was a musical innovator whose work over six decades inspired some of the greatest artists of our time.
The Cameroonian saxophonist, who died at 86 this week after contracting coronavirus, also influenced many musical genres.
Whether it was Congolese rumba in the 1950s, disco in the 1970s or hip-hop in the 1990s, his contribution to the development of modern music cannot be overstated.
In the 1950s he was at the epicentre of rumba that formed the foundation for modern popular African music.
His songs amplified the hope felt by newly independent African states and formed the soundtrack to an optimistic era.
The singer, songwriter and producer then turned his attention to another genre, and was in the vanguard of the disco era in the early 1970s.
But Dibango s first love was jazz, which celebrates virtuosity and encourages improvisation and cross-genre experimentation.
"Through jazz I discovered all the music that I love, starting with classical music," he told Courier, the magazine for the UN s cultural organisation, Unesco.
"Jazz is a much more rigorous form of music than is generally thought."
',
                                                                0,10);
-- health
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Thinking about something in endless circles — is exhausting.
While everyone overthinks a few things once in a while, chronic over-thinkers spend most of their waking time ruminating, which puts pressure on themselves. They then mistake that pressure to be stress.
“There are people who have levels of overthinking that are just pathological,” says clinical psychologist Catherine Pittman, an associate professor in the psychology department at Saint Mary’s College in Notre Dame, Indiana.
“But the average person also just tends to overthink things.” Pittman is also the author of “Rewire Your Anxious Brain: How to Use the Neuroscience of Fear to End Anxiety, Panic, and Worry.”
Overthinking can take many forms: endlessly deliberating when making a decision (and then questioning the decision), attempting to read minds, trying to predict the future, reading into the smallest of details, etc.
People who overthink consistently run commentaries in their heads, criticising and picking apart what they said and did yesterday, terrified that they look bad — and fretting about a terrible future that might await them
‘What ifs’ and ‘shoulds’ dominate their thinking, as if an invisible jury is sitting in judgement on their lives. And they also agonise over what to post online because they are deeply concerned about how other people will interpret their posts and updates.
They don’t sleep well because ruminating and worrying keep them awake at night. “Ruminators repetitively go over events, asking big questions: Why did that happen? What does it mean?” adds Susan Nolen-Hoeksema, the chair of the department of psychology at Yale University and the author of Women Who Think Too Much: How to Break Free of Overthinking and Reclaim Your Life. “But they never find any answers.”
',
                                                                -21,11);

-- 10 no de 6 PLOG - 4
-- has version of replies
-- comment 1 Id 28
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Finally!!!! Why did it took him so long??
', 2,5);
-- rep 1 - 1
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Right?? I have no idea it has been 2 months
', 1,1);
-- rep 2 - 1
INSERT INTO content (body,likes_difference,author_id) VALUES ('
2 months???? Since it has been 5 months since the first assigment
', 1,2);
-- comment 2 Id 31
INSERT INTO content (body,likes_difference,author_id) VALUES ('
We should unite and do a revolution. This is out of order
', 3,11);
-- rep 1 - 2
INSERT INTO content (body,likes_difference,author_id) VALUES ('
In my time, we invaded the Rectory
', 0,12);
-- rep 2 - 2
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Dear all, We must stay calm and united in this time of need
', -2,10);
-- rep 3 - 2
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Caros portugueses, não é com revoluções anarquicas que chegaremos a algum lado. No meu tempo Salazar dar-nos-ia com a colher. Não está certo, mas também não sei se será esta a melhor maneira de o fazer...
', -1,9);
-- comment 3 Id 35
INSERT INTO content (body,likes_difference,author_id) VALUES ('
I want to say sorry to all my fans. I know I should do better and I will try next time.
', -5,7);
-- rep 1 - 3
INSERT INTO content (body,likes_difference,author_id) VALUES ('
United we stand!! <3 <3 S2 S2
', 1,10);
-- rep 2 - 3
INSERT INTO content (body,likes_difference,author_id) VALUES ('
F off you weirdo
', 0,6);
-- rep 3 - 3 VF
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Caaaaalma portugueses, isto não pode ser de qualquer maneira...
', 1,9);
-- rep 3 - 3 VI
INSERT INTO content (body,most_recent,likes_difference,author_id) VALUES ('
Calminha carochinha, isto n pode ser de qq maneira, né??
','false',0,9);

-- 10 no de 7 Elon - 6
-- 1 comment deleted by the user, and one banned with a thread
-- comment 1 Id 40
INSERT INTO content (body,likes_difference,author_id) VALUES ('
YOOOOO thats braaazy. So pumped for this
', 0,6);
-- comment 2
INSERT INTO content (body,likes_difference,author_id) VALUES ('
About to drop the best feature of the whole project!!!!!
', 2,3);
-- reply 1 - 2
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Naaah man, the world aint reaaady. Did you produce anything on it??
', 1,2);
-- reply 2 - 2
INSERT INTO content (body,likes_difference,author_id) VALUES ('
No. All the production is Bjork and its absolute fire
', 3,3);
-- comment 3 Id 44
INSERT INTO content (body,likes_difference,author_id) VALUES ('
About to drop an 11/10!!! The video was sick
', 1,2);
-- reply 1 - 3
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Guess who did the visuals? You damn right I did
', 3,4);
-- comment 4 Id 46
INSERT INTO content (body,likes_difference,author_id) VALUES ('
I love the concept. But the concept of the Concept?? Thats where the gold is at
', 0,8);
-- comment 5
INSERT INTO content (body,likes_difference,author_id) VALUES (NULL,
 -7,NULL);
-- reply 1 - 5
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Thats just racist man. Just because he was born in South Africa...
', 1,4);
-- comment 6 Id 49
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Dont really care tbh
', -1,NULL);
-- reply 1 - 6
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Bro! If you dont care just move on. Do smt positive with your life instead of spreading the hate
', 1,1);

-- 5 - 2
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Tell them queeeeen!!!
', 0,7);
-- 9
INSERT INTO content (body,likes_difference,author_id) VALUES ('
I always knew that this design ish was kinda bs
', 0,7);

-- 18 - 3
-- VF
INSERT INTO content (body,likes_difference,author_id) VALUES ('
If we are beeing completly honest here, they just want attention
', 0,6);
-- V1
INSERT INTO content (body,most_recent,likes_difference,author_id) VALUES ('
Attention whores!!
','false',0,6);
INSERT INTO content (body,likes_difference,author_id) VALUES ('
My daughter told me about me about this. Props to them
', 0,7);

-- 19 - 4 Id 56
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Looking forward to the continuation
', 0,6);
INSERT INTO content (body,likes_difference,author_id) VALUES ('
I wish I was in Silicon Valley right now. Fed up of all this students
', 0,7);

-- 21 - 5
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Whaaat?? I didnt know I was wrong all this time
', 0,6);
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Prolog to the rescue!!!!
', 0,7);

-- 24 - 6
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Yeah after menopause my mother didnt remember who I was...
', 0,6);
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Oh thats why my wife acts like she has never seen me before
', 0,7);

-- 25 - 7 Id 62
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Beautifull. Just wonderfull!!! Thank you
', 0,6);
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Saved this to read whenever I need it. This post is great!!
', 0,7);

-- 26 - 8
INSERT INTO content (body,likes_difference,author_id) VALUES ('
It is a very very day for the music and Jazz World... RIP <3
', 0,6);
INSERT INTO content (body,likes_difference,author_id) VALUES ('
I didnt know him but its sad all this corona stuff
', 0,7);

-- 27 - 9
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Introduction relatable af. Subscribed
', 0,6);
INSERT INTO content (body,likes_difference,author_id) VALUES ('
This helped me a lot to focus and get the work done
', 0,7);

-- 3 - 10
INSERT INTO content (body,likes_difference,author_id) VALUES ('
I have witnessed this and it is craaazy. Get out of his way if you find him drunk. That was the only reason I gave him a 18
', 0,7);
-- 8 - 10
INSERT INTO content (body,likes_difference,author_id) VALUES ('
Wholesome post!!
', 0,6);

-- post --
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (1,'Corona Hits the US Pacific Northwest','2020-04-28 3:38:9',NULL,'true','News','6792bcbc00390b5a464c15e8118bcf51ec1c664792e82321c21d83f4b6a6f4e8.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (2,'Brexit: What happens now?','2020-04-30 19:25:29',NULL,'true','News','8d8a178aae77930f274752a6e6d4a444f1e3f485196957191ef6ed4f8a4e11eb.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (3,'Torrinheiras headbutt is one of the deadliest moves','2020-04-28 18:55:53',NULL,'true','News','50f52f5dcc40349482cecdd872e31308a7e352647c2ef300e3b798bc3f03b8ba.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (4,'PLOG grades finnally out','2020-01-29 18:58:00',NULL,'true','News','7ec8b2a3414c276cdc3ea87b23942b580abe65c357eb18bea1d1eb653db31e23.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (5,'How I Learned Beauty Can Be a Curse','2019-12-27 12:50:18',NULL,'true','Opinion','dac8e15734b4a3f70fde5758fec266e4b745147b6bebc96529b968275012e8d2.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (6,'New Elon Musks Album','2020-02-10 23:48:45',NULL,'true','News','7c92d1fd0761ddb46cafc8614fac7946a668ab7ce698134d126d3c85555f4c0d.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (7,'US government officials using mobile ad location data to study coronavirus spread','2020-02-01 13:00:30',NULL,'false','News','ac50c887addac4c843af1abc06d49fce311eb230d3a2fbf50d4ff36b8c1bd517.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (8,'5 Life Lessons Told Over 5 Glasses of Wine from a 99-Year Old Filled With Regret','2017-07-26 23:19:14',NULL,'true','Opinion','a13815d3fd5e9060226c0f6eb60dd9f2d02c4fdd4b1c0111d0c34cbc3e5133ce.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (9,'UX myths to forget in 2020','2019-12-09 13:57:50',NULL,'false','Opinion','27843b047c898efd0b6e7de2476382faed72a2dcdf28ea26e01bd911634db126.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (10,'My Semester With the Snowflakes','2016-07-17 10:52:36',NULL,'true','Opinion','269b68b3abee509413515a8bef0e7835ed7bf84f9c7ad5ac364fee25a1571b47.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (11,'Microinteractions, Emotional Design and Humanity.','2017-04-01 11:48:40',NULL,'true','Opinion','68e2fc025aa3d4b3cd16d1e2ad29e9f5ce650a7a19c6c46fc0a5d6f6ac723c1a.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (12,'We Exposed An Orphan Only To Violent Games For The First 12 Years Of His Life','2019-01-23 11:51:12',NULL,'true','News','15f75e6487b2c37b39b0758daf7bdc235bacb975d02861e3b315cced306e37f5.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (13,'The Devaluation of Music: It’s Worse Than You Think','2019-07-08 12:41:10',NULL,'true','Opinion','0ebf7039150cd52f55dd866bb3c14250ce077bba485d8992348b3aeff40684d8.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (14,'Congress Is About to Bail Out Everyone — Except Black Business Owners','2017-08-06 16:22:49',NULL,'true','Opinion','d2d06d741fa54eed682e92f2960db1f290f7b85a9b5ce7ea621bc193380dcc5a.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (15,'Colin Kaepernick announces he will no longer protest after reading your aunt’s Facebook post','2019-07-05 12:50:12',NULL,'true','Opinion','d764a848838ffc8a6b972d02732ca7252b3d96d37dbbeb40f60f350dc532176f.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (16,'Global sea ice shrinking at unprecedented speeds, warns scientist','2018-09-08 11:13:41',NULL,'true','News','8e0220155ea3743675a2af2eca5ff85b1a826b04d0f5b3cacb094519e8738e49.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (17,'How I negotiated a $300,000 job offer in Silicon Valley','2020-05-08 14:34:35',NULL,'false','Opinion','265dbba9b1e7f15eaecbdf5e572cf4b6d61fe53f701cb6918cb97c4176a29334.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (18,'Coronavirus: YouTube stars urge fans to stay at home','2016-04-08 11:21:00',NULL,'true','News','d394eedc0a35d3120b208e1641fd0317693e2887d03a62f81717678f3886e1ba.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (19,'This Is Silicon Valley','2017-04-16 19:16:29',NULL,'true','Opinion','21f801618414ddad42ec849f7e0403db43c7475ec8c6e6eb26c1804a0e67e704.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (20,'I’ll never bring my phone on an international flight again. Neither should you.','2018-09-17 22:51:29',NULL,'true','Opinion','30c32d28890f532aefd2594fb517ada8e8c4cd97bf5e3980e46ff2f9c1cff84c.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (21,'Goodbye, Object Oriented Programming','2016-09-03 16:32:16','2018-05-13 23:35:00','true','Opinion','2f20bad18a7bf04a894b433373bb3f241ff8c0668fdde316cb2fd2750a0c832a.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (22,'Object Oriented Programming is not that Good','2016-09-03 20:12:51','2016-09-26 12:16:30','true','Opinion','2f20bad18a7bf04a894b433373bb3f241ff8c0668fdde316cb2fd2750a0c832a.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (23,'Object Oriented Programming Sucks TBH','2016-09-03 20:07:11',NULL,'true','Opinion','2f20bad18a7bf04a894b433373bb3f241ff8c0668fdde316cb2fd2750a0c832a.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (24,'Exploring the Link Between Menopause and Alzheimer’s','2017-05-02 15:38:50',NULL,'true','Opinion','ee9f4127ee122033d6a115eb72092906f31c1d333bb3aa3bef6f60a737a255d5.png'); -- scheduled
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (25,'Three Magical Phrases to Comfort a Dying Person','2019-04-30 16:02:45',NULL,'true','Opinion','d602445ca85e56755e4fa843dc1a3aaf09fa55a303d165a17351e938fa71efee.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (26,'Manu Dibango: The saxophone legend who inspired a disco groove','2020-04-27 16:30:39',NULL,'true','News','5065974caf2f440a6f1093607ea376c36527041b01453efb588e8845f6c8174e.png');
INSERT INTO post (content_id,title,publication_date,modification_date,visible,type,photo) VALUES (27,'Psychologists Explain How To Stop Overthinking Everything','2016-07-06 14:03:43',NULL,'true','Opinion','072600350019de6c43dec2e04de9ffa12f084d1461c5643c125dce5e91a11c51.png');

-- post_version --
INSERT INTO post_version (past_version_id,cur_version_id) VALUES (22,21);
INSERT INTO post_version (past_version_id,cur_version_id) VALUES (23,21);

-- post_tag --
INSERT INTO post_tag (post_id,tag_id) VALUES (1,1);
INSERT INTO post_tag (post_id,tag_id) VALUES (1,9);
INSERT INTO post_tag (post_id,tag_id) VALUES (2,6);
INSERT INTO post_tag (post_id,tag_id) VALUES (3,5);
INSERT INTO post_tag (post_id,tag_id) VALUES (4,10);
INSERT INTO post_tag (post_id,tag_id) VALUES (5,2);
INSERT INTO post_tag (post_id,tag_id) VALUES (6,7);
INSERT INTO post_tag (post_id,tag_id) VALUES (6,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (7,1);
INSERT INTO post_tag (post_id,tag_id) VALUES (7,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (8,2);
INSERT INTO post_tag (post_id,tag_id) VALUES (9,3);
INSERT INTO post_tag (post_id,tag_id) VALUES (10,6);
INSERT INTO post_tag (post_id,tag_id) VALUES (10,10);
INSERT INTO post_tag (post_id,tag_id) VALUES (11,3);
INSERT INTO post_tag (post_id,tag_id) VALUES (12,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (13,4);
INSERT INTO post_tag (post_id,tag_id) VALUES (13,7);
INSERT INTO post_tag (post_id,tag_id) VALUES (14,4);
INSERT INTO post_tag (post_id,tag_id) VALUES (15,5);
INSERT INTO post_tag (post_id,tag_id) VALUES (16,9);
INSERT INTO post_tag (post_id,tag_id) VALUES (17,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (18,1);
INSERT INTO post_tag (post_id,tag_id) VALUES (18,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (19,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (20,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (21,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (22,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (23,8);
INSERT INTO post_tag (post_id,tag_id) VALUES (24,1);
INSERT INTO post_tag (post_id,tag_id) VALUES (25,1);
INSERT INTO post_tag (post_id,tag_id) VALUES (26,7);
INSERT INTO post_tag (post_id,tag_id) VALUES (27,1);


-- comment --
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (28,'2020-01-30 11:39:45',NULL,4);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (31,'2020-02-02 14:41:31',NULL,4);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (35,'2020-02-13 01:02:15',NULL,4);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (40,'2020-02-11 02:05:45',NULL,6);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (41,'2020-02-16 12:34:33',NULL,6);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (44,'2020-02-20 20:57:36',NULL,6);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (46,'2020-02-27 09:32:09',NULL,6);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (47,'2020-02-29 13:43:40',NULL,6);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (49,'2020-03-02 17:55:12',NULL,6);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (51,'2020-02-18 18:35:33',NULL,2);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (52,'2019-12-15 09:51:05',NULL,9);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (53,'2016-10-14 07:35:41','2016-10-28 20:40:38',18);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (54,'2016-10-14 07:35:41',NULL,18);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (55,'2017-10-17 05:54:33',NULL,18);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (56,'2017-05-31 00:29:30',NULL,19);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (57,'2017-07-27 21:38:40',NULL,19);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (58,'2017-11-08 20:31:14',NULL,21);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (59,'2018-04-19 11:58:46',NULL,21);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (60,'2017-11-29 09:16:38',NULL,24);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (61,'2018-02-15 19:24:33',NULL,24);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (62,'2019-08-27 14:29:41',NULL,25);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (63,'2019-09-14 06:57:26',NULL,25);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (64,'2020-03-29 15:35:18',NULL,26);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (65,'2020-03-30 14:36:32',NULL,26);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (66,'2016-10-29 13:57:57',NULL,27);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (67,'2017-07-18 16:15:24',NULL,27);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (68,'2019-06-23 08:03:29',NULL,3);
INSERT INTO comment (content_id,publication_date,modification_date,post_id) VALUES (69,'2018-03-19 03:03:24',NULL,8);

-- comment_version --
INSERT INTO comment_version (past_version_id,cur_version_id) VALUES (54,53);

-- reply --
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (29,'2020-02-18 09:58:33',NULL,28);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (30,'2020-03-01 06:28:06',NULL,28);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (32,'2020-02-10 08:48:46',NULL,31);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (33,'2020-02-13 16:51:14',NULL,31);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (34,'2020-03-04 13:34:45',NULL,31);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (36,'2020-02-15 10:16:53',NULL,35);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (37,'2020-02-17 19:31:32',NULL,35);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (38,'2020-02-24 23:15:29','2020-02-27 08:30:08',35);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (39,'2020-02-24 23:15:29',NULL,35);

INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (42,'2020-02-20 04:46:11',NULL,41);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (43,'2020-02-29 17:44:46',NULL,41);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (45,'2020-02-27 08:30:08',NULL,44);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (48,'2020-03-03 02:59:25',NULL,47);
INSERT INTO reply (content_id,publication_date,modification_date,comment_id) VALUES (50,'2020-03-03 13:35:21',NULL,49);

-- reply_version --
INSERT INTO reply_version (past_version_id,cur_version_id) VALUES (39,38);

-- reason --
INSERT INTO reason (name) VALUES ('Abusive Language');
INSERT INTO reason (name) VALUES ('Fake News');
INSERT INTO reason (name) VALUES ('Hate Speech');
INSERT INTO reason (name) VALUES ('Advertisement');
INSERT INTO reason (name) VALUES ('Clickbait');
INSERT INTO reason (name) VALUES ('Other');

-- report --
-- comment --
-- INSERT INTO report (explanation,closed,reporter_id,solver_id) VALUES ('The comment is racist','true',1,8);
-- post --
INSERT INTO report (explanation,closed,reporter_id) VALUES ('The post is fake news','false',6);
-- user 11--
INSERT INTO report (explanation,closed,reporter_id,solver_id) VALUES ('Inappopriate conduct','true',9,8);
-- user 3--
INSERT INTO report (explanation,closed,reporter_id) VALUES ('He is just a random guy and I dont like him','false',2);
-- tag --
INSERT INTO report (explanation,closed,reporter_id) VALUES ('This tag is no good guys','false',9);

-- report_reason --
-- INSERT INTO report_reason (report_id,reason_id) VALUES (1,1);
-- INSERT INTO report_reason (report_id,reason_id) VALUES (1,3);
INSERT INTO report_reason (report_id,reason_id) VALUES (1,2);
INSERT INTO report_reason (report_id,reason_id) VALUES (1,5);
INSERT INTO report_reason (report_id,reason_id) VALUES (2,1);
INSERT INTO report_reason (report_id,reason_id) VALUES (2,6);
INSERT INTO report_reason (report_id,reason_id) VALUES (3,6);
INSERT INTO report_reason (report_id,reason_id) VALUES (4,3);

-- user_report --
INSERT INTO user_report (report_id,user_id) VALUES (2,11);
INSERT INTO user_report (report_id,user_id) VALUES (3,3);

-- content_report --
-- INSERT INTO content_report (report_id,content_id) VALUES (1,47);
INSERT INTO content_report (report_id,content_id) VALUES (1,15);

-- tag_report --
INSERT INTO tag_report (report_id,tag_id) VALUES (4,11);

-- rating --
-- post ratings --
INSERT INTO rating (content_id,user_id,"like") VALUES (1,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (1,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (2,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (2,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (3,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (3,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (4,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (4,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (5,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (5,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (6,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,6,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (6,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (7,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,6,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (7,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (8,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,6,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (8,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (9,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,6,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (9,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (10,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,6,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,94,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,95,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,96,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,97,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,98,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,99,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,100,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (10,101,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (11,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,99,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,82,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (11,100,'false');

INSERT INTO rating (content_id,user_id,"like") VALUES (12,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,77,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,93,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,68,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,81,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,78,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,74,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,67,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,73,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,84,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,100,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,101,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,82,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,85,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,75,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,79,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,98,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,71,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,66,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,83,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,95,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,94,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,91,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,96,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,92,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,80,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,69,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (12,76,'false');

INSERT INTO rating (content_id,user_id,"like") VALUES (13,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,76,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,77,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,78,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,79,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,80,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,81,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,82,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,83,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,84,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,85,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,86,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (13,87,'false');

INSERT INTO rating (content_id,user_id,"like") VALUES (14,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,84,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,85,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,86,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (14,87,'false');

INSERT INTO rating (content_id,user_id,"like") VALUES (15,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,5,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,6,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,52,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,53,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,54,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,55,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,56,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,57,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,58,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (15,59,'false');

INSERT INTO rating (content_id,user_id,"like") VALUES (16,8,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,9,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,14,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,15,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,16,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,19,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,20,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,21,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,22,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,23,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,25,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,26,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,27,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,28,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,29,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,30,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,31,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,33,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,34,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,35,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,36,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,37,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,38,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,39,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,40,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,41,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,44,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,45,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,46,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,47,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,48,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,49,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,51,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,52,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,53,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,54,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,55,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,56,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,57,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,58,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,59,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,60,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,61,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,62,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,63,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,64,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,65,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,66,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,67,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,68,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,69,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,70,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,71,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,72,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,74,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,75,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,76,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,77,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,79,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,80,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,81,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,82,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,83,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,84,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,85,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,86,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,87,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,88,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,89,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,90,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,91,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,92,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,93,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (16,1,'false');

INSERT INTO rating (content_id,user_id,"like") VALUES (18,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (18,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (18,4,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (19,75,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (19,32,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (19,95,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (19,24,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (19,40,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (19,82,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (19,23,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (19,27,'false');

INSERT INTO rating (content_id,user_id,"like") VALUES (20,78,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (20,63,'true');

INSERT INTO rating (content_id,user_id,"like") VALUES (21,50,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,17,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,10,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,42,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,32,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,24,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,73,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,43,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,13,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,18,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,74,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,87,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (21,51,'false');

INSERT INTO rating (content_id,user_id,"like") VALUES (27,41,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,100,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,15,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,71,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,74,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,19,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,29,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,20,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,57,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,96,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,40,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,49,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,30,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,64,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,82,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,84,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,76,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,18,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,33,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,87,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (27,55,'false');
-- comment ratings --
INSERT INTO rating (content_id,user_id,"like") VALUES (28,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (28,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (29,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (30,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (31,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (31,4,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (31,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (33,1,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (33,11,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (34,1,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (35,1,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (35,2,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (35,11,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (35,6,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (35,5,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (36,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (38,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (41,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (41,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (42,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (43,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (43,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (43,11,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (44,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (45,1,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (45,2,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (45,3,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (47,1,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (47,2,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (47,3,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (47,4,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (47,5,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (47,6,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (47,7,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (48,7,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (49,12,'true');
INSERT INTO rating (content_id,user_id,"like") VALUES (49,7,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (49,11,'false');
INSERT INTO rating (content_id,user_id,"like") VALUES (50,7,'true');


-- user_subscription --
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,8);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,9);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,10);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,11);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,12);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,13);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,14);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,15);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,16);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,17);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,18);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,19);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,20);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,21);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (2,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (3,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (4,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (5,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (7,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (8,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (9,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (10,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (11,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (12,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (13,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (14,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (15,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (16,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (17,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (18,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (19,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (20,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (21,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (22,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (23,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (24,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (25,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (26,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (27,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (28,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (29,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (30,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (31,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (32,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (33,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (34,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (35,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (36,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (37,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (38,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (39,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (40,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (41,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (42,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (43,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (44,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (45,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (46,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (47,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (48,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (49,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (50,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (51,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (52,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (53,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (54,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (55,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (56,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (57,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (58,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (59,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (60,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (61,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (62,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (63,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (64,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (65,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (66,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (67,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (68,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (69,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (70,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (71,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (72,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (73,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (74,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (75,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (76,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (77,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (78,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (79,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (80,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (81,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (82,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (83,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (84,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (85,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (86,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (87,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (88,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (89,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (90,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (91,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (92,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (93,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (94,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (95,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (96,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (97,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (98,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (99,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (100,6);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (101,6);

INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (1,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (2,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (3,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (4,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (5,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (6,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (8,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (9,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (10,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (11,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (12,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (13,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (14,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (15,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (16,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (17,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (18,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (19,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (20,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (21,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (22,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (23,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (24,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (25,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (26,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (27,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (28,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (29,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (30,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (31,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (32,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (33,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (34,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (35,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (36,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (37,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (38,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (39,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (40,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (41,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (42,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (43,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (44,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (45,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (46,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (47,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (48,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (49,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (50,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (51,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (52,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (53,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (54,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (55,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (56,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (57,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (58,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (59,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (60,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (61,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (62,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (63,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (64,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (65,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (66,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (67,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (68,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (69,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (70,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (71,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (72,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (73,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (74,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (75,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (76,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (77,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (78,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (79,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (80,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (81,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (82,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (83,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (84,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (85,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (86,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (87,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (88,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (89,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (90,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (91,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (92,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (93,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (94,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (95,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (96,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (97,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (98,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (99,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (100,7);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (101,7);
-- others --
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (68,4);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (101,4);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (53,5);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (20,1);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (4,5);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (28,5);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (94,5);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (25,1);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (100,2);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (69,4);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (48,5);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (70,3);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (16,3);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (45,1);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (99,5);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (37,4);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (72,3);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (31,1);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (97,5);
INSERT INTO user_subscription (subscribing_user_id,subscribed_user_id) VALUES (35,3);

-- tag_subscription --
INSERT INTO tag_subscription (user_id,tag_id) VALUES (1,9);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (1,6);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (1,3);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (1,2);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (1,1);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (67,9);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (65,9);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (75,7);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (88,1);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (86,1);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (14,8);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (53,8);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (69,4);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (48,11);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (21,9);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (91,6);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (2,8);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (31,5);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (64,3);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (64,11);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (58,2);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (36,3);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (49,5);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (10,11);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (85,3);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (89,6);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (83,9);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (85,2);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (14,4);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (33,2);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (50,11);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (95,4);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (2,1);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (88,3);
INSERT INTO tag_subscription (user_id,tag_id) VALUES (51,11);

-- saved_post --
INSERT INTO saved_post (user_id,post_id) VALUES (1,1);
INSERT INTO saved_post (user_id,post_id) VALUES (1,2);
INSERT INTO saved_post (user_id,post_id) VALUES (1,3);
INSERT INTO saved_post (user_id,post_id) VALUES (1,4);
INSERT INTO saved_post (user_id,post_id) VALUES (1,5);
INSERT INTO saved_post (user_id,post_id) VALUES (1,6);
INSERT INTO saved_post (user_id,post_id) VALUES (1,7);
INSERT INTO saved_post (user_id,post_id) VALUES (1,8);
INSERT INTO saved_post (user_id,post_id) VALUES (1,9);
INSERT INTO saved_post (user_id,post_id) VALUES (1,10);
INSERT INTO saved_post (user_id,post_id) VALUES (1,11);
INSERT INTO saved_post (user_id,post_id) VALUES (1,12);
INSERT INTO saved_post (user_id,post_id) VALUES (1,13);
INSERT INTO saved_post (user_id,post_id) VALUES (1,14);

INSERT INTO saved_post (user_id,post_id) VALUES (70,7);
INSERT INTO saved_post (user_id,post_id) VALUES (53,17);
INSERT INTO saved_post (user_id,post_id) VALUES (48,14);
INSERT INTO saved_post (user_id,post_id) VALUES (60,3);
INSERT INTO saved_post (user_id,post_id) VALUES (97,13);
INSERT INTO saved_post (user_id,post_id) VALUES (45,26);
INSERT INTO saved_post (user_id,post_id) VALUES (40,6);
INSERT INTO saved_post (user_id,post_id) VALUES (101,12);
INSERT INTO saved_post (user_id,post_id) VALUES (96,2);
INSERT INTO saved_post (user_id,post_id) VALUES (65,4);
INSERT INTO saved_post (user_id,post_id) VALUES (70,21);
INSERT INTO saved_post (user_id,post_id) VALUES (7,6);
INSERT INTO saved_post (user_id,post_id) VALUES (74,8);
INSERT INTO saved_post (user_id,post_id) VALUES (48,4);
INSERT INTO saved_post (user_id,post_id) VALUES (73,10);
INSERT INTO saved_post (user_id,post_id) VALUES (89,1);
INSERT INTO saved_post (user_id,post_id) VALUES (56,20);
INSERT INTO saved_post (user_id,post_id) VALUES (89,5);
INSERT INTO saved_post (user_id,post_id) VALUES (28,2);
INSERT INTO saved_post (user_id,post_id) VALUES (67,10);
INSERT INTO saved_post (user_id,post_id) VALUES (43,25);
INSERT INTO saved_post (user_id,post_id) VALUES (56,2);
INSERT INTO saved_post (user_id,post_id) VALUES (81,23);
INSERT INTO saved_post (user_id,post_id) VALUES (41,15);
INSERT INTO saved_post (user_id,post_id) VALUES (59,18);
INSERT INTO saved_post (user_id,post_id) VALUES (91,4);
INSERT INTO saved_post (user_id,post_id) VALUES (85,3);
INSERT INTO saved_post (user_id,post_id) VALUES (77,7);
INSERT INTO saved_post (user_id,post_id) VALUES (50,10);
INSERT INTO saved_post (user_id,post_id) VALUES (79,1);
INSERT INTO saved_post (user_id,post_id) VALUES (34,20);
INSERT INTO saved_post (user_id,post_id) VALUES (40,3);
INSERT INTO saved_post (user_id,post_id) VALUES (82,14);
INSERT INTO saved_post (user_id,post_id) VALUES (78,22);
INSERT INTO saved_post (user_id,post_id) VALUES (78,3);
INSERT INTO saved_post (user_id,post_id) VALUES (31,3);
INSERT INTO saved_post (user_id,post_id) VALUES (73,20);
INSERT INTO saved_post (user_id,post_id) VALUES (31,9);
INSERT INTO saved_post (user_id,post_id) VALUES (40,1);
INSERT INTO saved_post (user_id,post_id) VALUES (92,6);
INSERT INTO saved_post (user_id,post_id) VALUES (32,7);
INSERT INTO saved_post (user_id,post_id) VALUES (39,3);
INSERT INTO saved_post (user_id,post_id) VALUES (32,5);
INSERT INTO saved_post (user_id,post_id) VALUES (36,1);
INSERT INTO saved_post (user_id,post_id) VALUES (77,25);
INSERT INTO saved_post (user_id,post_id) VALUES (96,18);
INSERT INTO saved_post (user_id,post_id) VALUES (31,13);
INSERT INTO saved_post (user_id,post_id) VALUES (67,12);
INSERT INTO saved_post (user_id,post_id) VALUES (53,1);
INSERT INTO saved_post (user_id,post_id) VALUES (33,25);

-- notification --
INSERT INTO "notification" ("text",icon,domain,"date",user_id) VALUES ('
You received a new badge: Mr WorldWide
','fas fa-globe-europe','user?=6/badges','2020-02-20 09:59:04',6);
INSERT INTO "notification" ("text",icon,domain,"date",user_id) VALUES ('
You reach 100 subscribers
','fas fa-trophy','user?=6','2020-02-25 16:13:45',6);
INSERT INTO "notification" ("text",icon,domain,"date",user_id) VALUES ('
You have been banned
','fas fa-ban',NULL,'2019-02-20 13:10:32',11);
INSERT INTO "notification" ("text",icon,domain,"date",user_id) VALUES ('
Proto notification 1
','fas fa-portrait',NULL,'2020-02-28 15:53:50',1);
INSERT INTO "notification" ("text",icon,domain,"date",user_id) VALUES ('
Proto notification 2
','fas fa-portrait',NULL,'2020-03-03 12:26:21',1);
INSERT INTO "notification" ("text",icon,domain,"date",user_id) VALUES ('
Proto notification 3
','fas fa-portrait',NULL,'2020-03-05 09:52:51',1);


---------------------------------
---------------------------------
---- Second round of triggers ---
---------------------------------
---------------------------------

--------------- UPDATE RATING TRIGGER

CREATE OR REPLACE FUNCTION upd_rating_on_insert() RETURNS TRIGGER AS
$BODY$

BEGIN

    IF EXISTS (SELECT * FROM content WHERE id = NEW.content_id and author_id = NEW.user_id) THEN

        RAISE EXCEPTION 'User cannot give itself a rating';

    END IF;

    IF NEW."like" = TRUE THEN
        UPDATE content SET likes_difference = likes_difference + 1 WHERE id = NEW.content_id;
    ELSE
        UPDATE content SET likes_difference = likes_difference - 1 WHERE id = NEW.content_id;
    END IF;

    RETURN NEW;

END

$BODY$
LANGUAGE plpgsql;

-----

CREATE OR REPLACE FUNCTION upd_rating_on_update() RETURNS TRIGGER AS
$BODY$

BEGIN

    IF EXISTS (SELECT * FROM content WHERE id = NEW.content_id and author_id = NEW.user_id) THEN

        RAISE EXCEPTION 'User cannot give itself a rating';
	END IF;
    -- Remove old rating
    IF OLD."like" = TRUE THEN
        UPDATE content SET likes_difference = likes_difference - 1 WHERE id = NEW.content_id;

    ELSE
        UPDATE content SET likes_difference = likes_difference + 1 WHERE id = NEW.content_id;

    END IF;

    -- Add new rating
    IF NEW."like" = TRUE THEN
        UPDATE content SET likes_difference = likes_difference + 1 WHERE id = NEW.content_id;
    ELSE
        UPDATE content SET likes_difference = likes_difference - 1 WHERE id = NEW.content_id;
    END IF;

    RETURN NEW;

END

$BODY$
LANGUAGE plpgsql;

-----

CREATE OR REPLACE FUNCTION upd_rating_on_delete() RETURNS TRIGGER AS
$BODY$

BEGIN

    -- Remove old rating
    IF OLD."like" = TRUE THEN
        UPDATE content SET likes_difference = likes_difference - 1 WHERE id = OLD.content_id;

    ELSE
        UPDATE content SET likes_difference = likes_difference + 1 WHERE id = OLD.content_id;

    END IF;

    RETURN NULL;
END

$BODY$
LANGUAGE plpgsql;


DROP TRIGGER IF EXISTS update_rating_on_insert ON rating;
CREATE TRIGGER update_rating_on_insert
    BEFORE INSERT ON rating
    FOR EACH ROW
    EXECUTE PROCEDURE upd_rating_on_insert();

DROP TRIGGER IF EXISTS update_rating_on_update ON rating;
CREATE TRIGGER update_rating_on_update
    BEFORE UPDATE ON rating
    FOR EACH ROW
    EXECUTE PROCEDURE upd_rating_on_update();

DROP TRIGGER IF EXISTS update_rating_on_delete ON rating;
CREATE TRIGGER update_rating_on_delete
    AFTER DELETE ON rating
    FOR EACH ROW
    EXECUTE PROCEDURE upd_rating_on_delete();


--------------- REPORTS SOLVED STATISTIC TRIGGER

CREATE OR REPLACE FUNCTION upd_admin_reports_solved() RETURNS TRIGGER AS
$BODY$

BEGIN
    UPDATE "admin" SET reports_solved = reports_solved + 1 WHERE user_id = NEW.solver_id;

    RETURN NEW;
END
$BODY$

LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS update_admin_reports_solved_on_update ON report;
CREATE TRIGGER update_admin_reports_solved_on_update
    BEFORE UPDATE ON report
    FOR EACH ROW
    WHEN (NEW.solver_id IS NOT NULL)
    EXECUTE PROCEDURE upd_admin_reports_solved();

DROP TRIGGER IF EXISTS update_admin_reports_solved_on_insert ON report;
CREATE TRIGGER update_admin_reports_solved_on_insert
    BEFORE INSERT ON report
    FOR EACH ROW
    WHEN (NEW.solver_id IS NOT NULL)
    EXECUTE PROCEDURE upd_admin_reports_solved();

--------------- USERS BANNED STATISTIC TRIGGER

CREATE OR REPLACE FUNCTION upd_admin_users_banned() RETURNS TRIGGER AS
$BODY$

BEGIN

    UPDATE "admin" SET users_banned = users_banned + 1 WHERE user_id = NEW.admin_id;

    RETURN NULL;
END
$BODY$

LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS update_admin_users_banned ON ban;
CREATE TRIGGER update_admin_users_banned
    AFTER INSERT ON ban
    FOR EACH ROW
    EXECUTE PROCEDURE upd_admin_users_banned();

--------------- BANNED STATUS TRIGGER

CREATE OR REPLACE FUNCTION upd_user_banned_status() RETURNS TRIGGER AS
$BODY$

BEGIN

    IF NEW.ban_end is NULL or NEW.ban_end > now() THEN
        UPDATE "user" SET banned = TRUE WHERE id = NEW.user_id;
        DELETE FROM "has_badge" as hb WHERE hb.user_id = NEW.user_id;

    ELSE
        UPDATE "user" SET banned = FALSE WHERE id = NEW.user_id;
    END IF;

	RETURN NULL;
END
$BODY$

LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS update_user_banned_status ON ban;
CREATE TRIGGER update_user_banned_status
    AFTER INSERT OR UPDATE ON ban
    FOR EACH ROW
    EXECUTE PROCEDURE upd_user_banned_status();

--------------- SELF REPORT TRIGGER

CREATE OR REPLACE FUNCTION chck_self_report() RETURNS TRIGGER AS
$BODY$

DECLARE
    reporter INTEGER;

BEGIN

    SELECT reporter_id INTO reporter FROM report WHERE id = NEW.report_id;

    IF reporter = NEW.user_id THEN
        RAISE EXCEPTION 'Cannot report self';
    END IF;

    RETURN NEW;
END
$BODY$

LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS check_self_report ON user_report;
CREATE TRIGGER check_self_report
    BEFORE INSERT ON user_report
    FOR EACH ROW
    EXECUTE PROCEDURE chck_self_report();


--------------- BADGES TRIGGER


--- CHAMPION TRIGGER
CREATE OR REPLACE FUNCTION give_badge_champion() RETURNS TRIGGER AS
$BODY$

DECLARE

    num_popular_articles INTEGER;
    badgeid INTEGER;
    authorid INTEGER;

BEGIN

    SELECT id INTO badgeid FROM badge WHERE name = 'Champion' ;

    SELECT c.author_id INTO authorid
    FROM (
            SELECT *
            FROM "content"
            WHERE id = NEW.content_id

    ) as c;

    IF EXISTS ( SELECT * FROM has_badge WHERE user_id = authorid AND badge_id = badgeid) THEN
        RETURN NULL;
    END IF;

    -- if content is post
    IF NEW.content_id IN (SELECT content_id FROM post) THEN
        -- get all posts of the user with 100+ likes
        SELECT count(c.id) INTO num_popular_articles
        FROM "content" c INNER JOIN "post" p ON p.content_id  = c.id
        WHERE c.author_id = authorid AND (SELECT count(r.user_id) FROM rating r WHERE r.content_id = c.id) >= 100;

        IF EXISTS (SELECT * FROM "user" WHERE id = authorid AND banned = TRUE) THEN
            RETURN NULL;
        END IF;

        IF (num_popular_articles >= 5) THEN
            INSERT INTO has_badge (user_id,badge_id) VALUES (authorid,badgeid);
        END IF;

    END IF;

    RETURN NULL;
END
$BODY$

LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS give_badge_champion ON rating;
CREATE TRIGGER give_badge_champion
    AFTER INSERT ON rating
    FOR EACH ROW
    WHEN (NEW."like" = TRUE)
    EXECUTE PROCEDURE give_badge_champion();




--------------- POPULAR BADGE

CREATE OR REPLACE FUNCTION give_badge_popular() RETURNS TRIGGER AS
$BODY$

DECLARE
  sub_count INTEGER;
  bdge_id INTEGER;

BEGIN
    SELECT id INTO bdge_id FROM badge WHERE name = 'Popular' ;

    IF EXISTS (SELECT 1 FROM has_badge hb WHERE hb.user_id = NEW.subscribed_user_id AND hb.badge_id = bdge_id) THEN
        RETURN NULL;
    END IF;

    SELECT COUNT(*) INTO sub_count FROM user_subscription WHERE subscribed_user_id = NEW.subscribed_user_id;

    IF EXISTS (SELECT * FROM "user" WHERE id = NEW.subscribed_user_id AND banned = TRUE) THEN
        RETURN NULL;
    END IF;

    IF sub_count >= 100 THEN
       INSERT INTO has_badge (user_id,badge_id) VALUES (NEW.subscribed_user_id,bdge_id);
	END IF;

	RETURN NULL;
END
$BODY$

LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS give_badge_popular ON user_subscription;
CREATE TRIGGER give_badge_popular
    AFTER INSERT ON user_subscription
    FOR EACH ROW
    EXECUTE PROCEDURE give_badge_popular();


--------------- VIOLET BADGE TRIGGER

CREATE OR REPLACE FUNCTION give_badge_violet() RETURNS TRIGGER AS
$BODY$

DECLARE

    authorid INTEGER;
    comment_count INTEGER;
    reply_count INTEGER;
    bdge_id INTEGER;

BEGIN

    SELECT c.author_id INTO authorid
    FROM "content" c inner join comment cm on c.id = cm.content_id
    WHERE cm.content_id = NEW.content_id;

    SELECT id INTO bdge_id FROM badge WHERE name = 'Violet' ;

    IF EXISTS (SELECT 1 FROM has_badge hb WHERE hb.user_id = authorid AND hb.badge_id = bdge_id) THEN
        RETURN NULL;
    END IF;

    SELECT COUNT(DISTINCT cm.post_id) INTO comment_count
    FROM "content" c inner join comment cm on c.id = cm.content_id
    WHERE c.author_id = authorid AND c.most_recent = TRUE;

    SELECT COUNT(DISTINCT cm.post_id) INTO reply_count
    FROM "content" c inner join reply r on c.id = r.content_id inner join comment cm on cm.content_id = r.comment_id
    WHERE c.author_id = authorid AND c.most_recent = TRUE;

    IF EXISTS (SELECT * FROM "user" WHERE id = authorid AND banned = TRUE) THEN
        RETURN NULL;
    END IF;

    IF comment_count + reply_count >= 10 THEN
       INSERT INTO has_badge (user_id,badge_id) VALUES (authorid,bdge_id);
    END IF;
    RETURN NULL;
END
$BODY$

LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS give_badge_violet_comment ON comment;
CREATE TRIGGER give_badge_violet_comment
    AFTER INSERT ON comment
    FOR EACH ROW
    EXECUTE PROCEDURE give_badge_violet();

DROP TRIGGER IF EXISTS give_badge_violet_reply ON reply;
CREATE TRIGGER give_badge_violet_reply
    AFTER INSERT ON reply
    FOR EACH ROW
    EXECUTE PROCEDURE give_badge_violet();


--------------- TARGETED BADGE TRIGGER

CREATE OR REPLACE FUNCTION give_badge_targeted_comment() RETURNS TRIGGER AS
$BODY$

DECLARE

    total_count INTEGER;
    authorid INTEGER;
    bdge_id INTEGER;

BEGIN

    SELECT c.author_id INTO authorid
    FROM (
            SELECT *
            FROM "content"
            WHERE id = NEW.post_id

    ) as c;

    SELECT id INTO bdge_id FROM badge WHERE name = 'Targeted' ;

    IF EXISTS (SELECT 1 FROM has_badge hb WHERE hb.user_id = authorid AND hb.badge_id = bdge_id) THEN
        RETURN NULL;
    END IF;

    SELECT COUNT(*) INTO total_count
    FROM
        (SELECT r.content_id as id
        FROM "content" c INNER JOIN reply r on c.id = r.content_id
        WHERE c.most_recent = TRUE and c.author_id <> authorid and r.comment_id in (SELECT cm.content_id
        												FROM "content" c INNER JOIN "comment" cm on c.id = cm.content_id
        												WHERE cm.post_id = NEW.post_id and c.most_recent = TRUE)

        UNION ALL

        (SELECT cm.content_id
        FROM "content" c INNER JOIN "comment" cm on c.id = cm.content_id
        WHERE cm.post_id = NEW.post_id and c.author_id <>  authorid and c.most_recent = TRUE)) as cnt;

    IF EXISTS (SELECT * FROM "user" WHERE id = authorid AND banned = TRUE) THEN
        RETURN NULL;
    END IF;

    IF total_count >= 10 THEN
       INSERT INTO has_badge (user_id,badge_id) VALUES (authorid,bdge_id);
    END IF;

    RETURN NULL;

END
$BODY$
LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION give_badge_targeted_reply() RETURNS TRIGGER AS
$BODY$

DECLARE

    total_count INTEGER;
    authorid INTEGER;
    bdge_id INTEGER;
    postid INTEGER;

BEGIN

    SELECT c.post_id INTO postid
    FROM (
            SELECT *
            FROM "comment" cm INNER JOIN reply r on cm.content_id = r.comment_id
            WHERE r.content_id = NEW.content_id

    ) as c;

    SELECT c.author_id INTO authorid
    FROM (
            SELECT *
            FROM "content"
            WHERE id = postid

    ) as c;

    SELECT id INTO bdge_id FROM badge WHERE name = 'Targeted' ;

    IF EXISTS (SELECT 1 FROM has_badge hb WHERE hb.user_id = authorid AND hb.badge_id = bdge_id) THEN
        RETURN NULL;
    END IF;

    SELECT COUNT(*) INTO total_count
    FROM
        (SELECT r.content_id as id
        FROM "content" c INNER JOIN reply r on c.id = r.content_id
        WHERE c.most_recent = TRUE and c.author_id <> authorid and r.comment_id in (SELECT cm.content_id
        												FROM "content" c INNER JOIN "comment" cm on c.id = cm.content_id
        												WHERE cm.post_id = postid and c.most_recent = TRUE)

        UNION ALL

        (SELECT cm.content_id
        FROM "content" c INNER JOIN "comment" cm on c.id = cm.content_id
        WHERE cm.post_id = postid and c.author_id <>  authorid and c.most_recent = TRUE)) as cnt;

    IF EXISTS (SELECT * FROM "user" WHERE id = authorid AND banned = TRUE) THEN
        RETURN NULL;
    END IF;

    IF total_count >= 10 THEN
       INSERT INTO has_badge (user_id,badge_id) VALUES (authorid,bdge_id);
    END IF;

    RETURN NULL;

END
$BODY$

LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS give_badge_targeted ON comment;
CREATE TRIGGER give_badge_targeted
    AFTER INSERT ON comment
    FOR EACH ROW
    EXECUTE PROCEDURE give_badge_targeted_comment();

DROP TRIGGER IF EXISTS give_badge_targeted ON reply;
CREATE TRIGGER give_badge_targeted
    AFTER INSERT ON reply
    FOR EACH ROW
    EXECUTE PROCEDURE give_badge_targeted_reply();

--------------- WORLDWIDE BADGE TRIGGER

CREATE OR REPLACE FUNCTION give_badge_worldwide() RETURNS TRIGGER AS
$BODY$

DECLARE

    country_count INTEGER;
    bdge_id INTEGER;

BEGIN
    SELECT id INTO bdge_id FROM badge WHERE name = 'Mr WorldWide' ;

    IF EXISTS (SELECT 1 FROM has_badge hb WHERE hb.user_id = NEW.subscribed_user_id AND hb.badge_id = bdge_id) THEN
        RETURN NULL;
    END IF;

    SELECT count(DISTINCT c.name) INTO country_count
    FROM user_subscription sub  JOIN "user" u ON sub.subscribing_user_id = u.id
							JOIN "location" loc ON loc.id = u.location_id
							JOIN "country" c ON loc.country_id = c.id
    WHERE subscribed_user_id = NEW.subscribed_user_id;

    IF EXISTS (SELECT * FROM "user" WHERE id = NEW.subscribed_user_id AND banned = TRUE) THEN
        RETURN NULL;
    END IF;

    IF country_count >= 10 THEN
       INSERT INTO has_badge (user_id,badge_id) VALUES (NEW.subscribed_user_id,bdge_id);
    END IF;

    RETURN NULL;
END
$BODY$

LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS give_badge_worldwide ON user_subscription;
CREATE TRIGGER give_badge_worldwide
    AFTER INSERT ON user_subscription
    FOR EACH ROW
    EXECUTE PROCEDURE give_badge_worldwide();


-- UPDATE CONTENT LATEST VERSION

CREATE OR REPLACE FUNCTION content_latest_version() RETURNS TRIGGER AS
$BODY$
DECLARE
	cid bigint;
	tuple RECORD;
	row RECORD;
BEGIN
    IF NEW.body is NULL THEN
        RETURN NEW;
    END IF;
	INSERT INTO "content" (body, most_recent, likes_difference, author_id) VALUES (OLD.body, FALSE, OLD.likes_difference, OLD.author_id) RETURNING "id" INTO cid;

    IF OLD.id IN (SELECT content_ID FROM post)
	THEN
		SELECT * INTO tuple FROM post WHERE content_id = OLD.id;

		INSERT INTO post VALUES (
			cid, tuple.title, tuple.publication_date, tuple.modification_date, tuple.visible, tuple.type, tuple.photo
		);

		INSERT INTO post_version VALUES(cid, OLD.id);

        FOR row IN SELECT * FROM post_tag WHERE post_id = OLD.id
		LOOP
			INSERT INTO post_tag VALUES (cid, row.tag_id);
		END LOOP;

	ELSEIF OLD.id IN (SELECT content_ID FROM "comment")
	THEN
		SELECT * INTO tuple FROM "comment" WHERE content_id = OLD.id;

		INSERT INTO "comment" VALUES (
			cid, tuple.publication_date, tuple.modification_date, tuple.post_id
		);

        UPDATE "comment" SET modification_date = now() WHERE content_id = OLD.id;

		INSERT INTO comment_version VALUES(cid, OLD.id);

	ELSEIF OLD.id IN (SELECT content_id FROM reply)
	THEN
		SELECT * INTO tuple FROM reply WHERE content_id = OLD.id;

		INSERT INTO "reply" VALUES (
			cid, tuple.publication_date, tuple.modification_date, tuple.comment_id
		);

        UPDATE "reply" SET modification_date = now() WHERE content_id = OLD.id;

		INSERT INTO reply_version VALUES(cid, OLD.id);
	END IF;

    RETURN NEW;
END;
$BODY$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS content_latest_version ON "content";
CREATE TRIGGER content_latest_version
    BEFORE UPDATE OF body ON "content"
    FOR EACH ROW
    EXECUTE PROCEDURE content_latest_version();


-- MODIFICATION DATE

CREATE OR REPLACE FUNCTION update_modification_date() RETURNS TRIGGER AS
$BODY$
BEGIN
    NEW.modification_date = now();
    RETURN NEW;
END;
$BODY$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS post_modification_date ON post;
CREATE TRIGGER post_modification_date
    BEFORE UPDATE OF title, photo ON post
    FOR EACH ROW
    EXECUTE PROCEDURE update_modification_date();


-- VERIFY TAG

CREATE OR REPLACE FUNCTION update_user_verified() RETURNS TRIGGER AS
$BODY$
DECLARE
	total_badges bigint;
	user_badges bigint;
BEGIN
    SELECT COUNT(badge_id) INTO user_badges
	FROM has_badge hb INNER JOIN
		 badge b ON b.id = hb.badge_id
	WHERE hb.user_id = NEW.user_id AND b.name != 'Verified';

	SELECT COUNT("id") INTO total_badges
	FROM badge
	WHERE badge.name != 'Verified';

	IF user_badges = total_badges
	THEN
		UPDATE "user" SET verified = TRUE WHERE id = NEW.user_id;
		INSERT INTO has_badge (user_id, badge_id) VALUES (NEW.user_id, 1);
	END IF;

	RETURN NEW;
END
$BODY$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_update_user_verified ON has_badge;
CREATE TRIGGER trigger_update_user_verified
    AFTER INSERT OR UPDATE ON has_badge
    FOR EACH ROW
	WHEN (NEW.badge_id != 1)
    EXECUTE PROCEDURE update_user_verified();

-- GIVE TAG MASTER BADGE

CREATE OR REPLACE FUNCTION give_badge_tag_master() RETURNS TRIGGER AS
$BODY$
DECLARE
    author bigint;
    bdge_id INTEGER;
BEGIN
    SELECT author_id INTO author FROM content WHERE content.id = NEW.post_id;

    SELECT id INTO bdge_id FROM badge WHERE name = 'Tag Master' ;

    IF EXISTS (SELECT 1 FROM has_badge hb WHERE hb.user_id = author AND hb.badge_id = bdge_id) THEN
        RETURN NULL;
    END IF;

	IF (SELECT COUNT (DISTINCT post_tag.tag_id)
        FROM content INNER JOIN
			 post ON content.id = post.content_id INNER JOIN
		     post_tag ON content_id = post_tag.post_id
        WHERE content.author_id = author AND content.most_recent = TRUE) >= 10
	THEN
		INSERT INTO has_badge VALUES (author, bdge_id);
    END IF;
    RETURN NULL;
END
$BODY$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_give_badge_tag_master ON post_tag;
CREATE TRIGGER trigger_give_badge_tag_master
    AFTER INSERT OR UPDATE ON post_tag
    FOR EACH ROW EXECUTE PROCEDURE give_badge_tag_master();

-- INSTEAD OF DELETE COMMENT TRIGGER
CREATE OR REPLACE FUNCTION set_comment_null() RETURNS TRIGGER AS
$BODY$
DECLARE
    row RECORD;
BEGIN
    IF EXISTS (SELECT 1 FROM comment c WHERE c.content_id = OLD.id AND OLD.most_recent = TRUE) THEN
        IF EXISTS (SELECT 1 FROM reply r WHERE r.comment_id = OLD.id) THEN
            UPDATE content SET body = NULL, author_id = NULL WHERE content.id = OLD.id;
            FOR row IN SELECT * FROM comment_version WHERE cur_version_id = OLD.id
            LOOP
                DELETE FROM content WHERE content.id = row.past_version_id;
            END LOOP;

            RETURN NULL;
        END IF;
    END IF;

    RETURN OLD;
END
$BODY$
LANGUAGE plpgsql;


DROP TRIGGER IF EXISTS trigger_instead_delete_comment ON content;
CREATE TRIGGER trigger_instead_delete_comment
    BEFORE DELETE ON content
    FOR EACH ROW EXECUTE PROCEDURE set_comment_null();


-- BEFORE DELETE REPLY TRIGGER
CREATE OR REPLACE FUNCTION remove_past_replies() RETURNS TRIGGER AS
$BODY$
DECLARE
    row RECORD;
BEGIN
    FOR row IN SELECT * FROM reply_version WHERE cur_version_id = OLD.content_id
    LOOP
        DELETE FROM content WHERE content.id = row.past_version_id;
    END LOOP;
    RETURN OLD;
END
$BODY$
LANGUAGE plpgsql;


DROP TRIGGER IF EXISTS trigger_before_delete_reply ON reply;
CREATE TRIGGER trigger_before_delete_reply
    BEFORE DELETE ON reply
    FOR EACH ROW EXECUTE PROCEDURE remove_past_replies();


-- BEFORE DELETE COMMENT TRIGGER
CREATE OR REPLACE FUNCTION remove_past_comments() RETURNS TRIGGER AS
$BODY$
DECLARE
    row RECORD;
BEGIN
    FOR row IN SELECT * FROM comment_version WHERE cur_version_id = OLD.content_id
    LOOP
        DELETE FROM content WHERE content.id = row.past_version_id;
    END LOOP;
    RETURN OLD;
END
$BODY$
LANGUAGE plpgsql;


DROP TRIGGER IF EXISTS trigger_before_delete_comment ON comment;
CREATE TRIGGER trigger_before_delete_comment
    BEFORE DELETE ON comment
    FOR EACH ROW EXECUTE PROCEDURE remove_past_comments();


-- BEFORE DELETE POST TRIGGER
CREATE OR REPLACE FUNCTION remove_past_posts() RETURNS TRIGGER AS
$BODY$
DECLARE
    row RECORD;
    row2 RECORD;
BEGIN
    -- Delete past versions
    FOR row IN SELECT * FROM post_version WHERE cur_version_id = OLD.content_id
    LOOP
        DELETE FROM content WHERE content.id = row.past_version_id;
    END LOOP;
    -- Delete comments
    FOR row IN SELECT * FROM comment WHERE comment.post_id = OLD.content_id
    LOOP
        -- Delete replies
        FOR row2 IN SELECT * FROM reply WHERE reply.comment_id = row.content_id
        LOOP
            DELETE FROM content WHERE content.id = row2.content_id;
        END LOOP;
        DELETE FROM content WHERE content.id = row.content_id;
    END LOOP;
    RETURN OLD;
END
$BODY$
LANGUAGE plpgsql;


DROP TRIGGER IF EXISTS trigger_before_delete_post ON post;
CREATE TRIGGER trigger_before_delete_post
    BEFORE DELETE ON post
    FOR EACH ROW EXECUTE PROCEDURE remove_past_posts();

-- DELETE LAST REPLY
CREATE OR REPLACE FUNCTION delete_null_comment() RETURNS TRIGGER AS
$BODY$
DECLARE
    cmtID bigint;
BEGIN
    IF EXISTS (SELECT 1 FROM reply r WHERE r.comment_id = OLD.comment_id) THEN
        RETURN NULL;
    END IF;

    SELECT cmt.content_id INTO cmtID
    FROM comment cmt INNER JOIN content c ON cmt.content_id = c.id
    WHERE cmt.content_id = OLD.comment_id AND c.body IS NULL AND c.author_id IS NULL;

    IF (cmtID IS NOT NULL)  THEN

        DELETE FROM content WHERE content.id = cmtID;
    END IF;

    RETURN NULL;
END
$BODY$
LANGUAGE plpgsql;


DROP TRIGGER IF EXISTS trigger_delete_last_reply ON reply;
CREATE TRIGGER trigger_delete_last_reply
    AFTER DELETE ON reply
    FOR EACH ROW EXECUTE PROCEDURE delete_null_comment();

------- Other User defined functions

CREATE OR REPLACE FUNCTION get_best_post(user_id INTEGER) RETURNS INTEGER AS $$

DECLARE
    max_likes INTEGER;
    best_post INTEGER;
BEGIN

    IF NOT EXISTS(SELECT * FROM post p INNER JOIN "content" c ON p.content_id = c.id WHERE c.author_id = user_id) THEN
        RETURN NULL;
    END IF;

    SELECT MAX(c.likes_difference) INTO max_likes
    FROM post p INNER JOIN "content" c ON p.content_id = c.id
    WHERE c.author_id = user_id and c.most_recent = TRUE;

    SELECT id INTO best_post
    FROM post p INNER JOIN "content" c ON p.content_id = c.id
    WHERE c.likes_difference = max_likes
    ORDER BY p.publication_date DESC
    LIMIT 1;

    RETURN best_post;

END

$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_number_subs_for_age(integer) RETURNS SETOF RECORD AS $$
DECLARE
 rec record;
BEGIN
    SELECT '13-20', count(distinct u_sub.subscribing_user_id)
    FROM user_subscription u_sub inner join "user" u on u_sub.subscribing_user_id = u.id
    WHERE u_sub.subscribed_user_id = $1 AND date_part('year', age(now(), u.birthday)) >= 13 AND date_part('year', age(now(), u.birthday)) <= 20 INTO rec;

    RETURN NEXT rec;

    SELECT '21-30', count(distinct u_sub.subscribing_user_id)
    FROM user_subscription u_sub inner join "user" u on u_sub.subscribing_user_id = u.id
    WHERE u_sub.subscribed_user_id = $1 AND date_part('year', age(now(), u.birthday)) >= 21 AND date_part('year', age(now(), u.birthday)) <= 30 INTO rec;

    RETURN NEXT rec;

    SELECT '31-40', count(distinct u_sub.subscribing_user_id)
    FROM user_subscription u_sub inner join "user" u on u_sub.subscribing_user_id = u.id
    WHERE u_sub.subscribed_user_id = $1 AND date_part('year', age(now(), u.birthday)) >= 31 AND date_part('year', age(now(), u.birthday)) <= 40 INTO rec;

    RETURN NEXT rec;

    SELECT '41-50', count(distinct u_sub.subscribing_user_id)
    FROM user_subscription u_sub inner join "user" u on u_sub.subscribing_user_id = u.id
    WHERE u_sub.subscribed_user_id = $1 AND date_part('year', age(now(), u.birthday)) >= 41 AND date_part('year', age(now(), u.birthday)) <= 50 INTO rec;

    RETURN NEXT rec;

    SELECT '51-60', count(distinct u_sub.subscribing_user_id)
    FROM user_subscription u_sub inner join "user" u on u_sub.subscribing_user_id = u.id
    WHERE u_sub.subscribed_user_id = $1 AND date_part('year', age(now(), u.birthday)) >= 51 AND date_part('year', age(now(), u.birthday)) <= 60 INTO rec;

    RETURN NEXT rec;

    SELECT '60+', count(distinct u_sub.subscribing_user_id)
    FROM user_subscription u_sub inner join "user" u on u_sub.subscribing_user_id = u.id
    WHERE u_sub.subscribed_user_id = $1 AND date_part('year', age(now(), u.birthday)) > 60 INTO rec;

    RETURN NEXT rec;

END $$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_subs_location(integer) RETURNS table (country_name text, cnt bigint) AS $$
BEGIN
    RETURN query
        SELECT c.name, count(*) as cnt
        FROM user_subscription u_sub inner join "user" u on u_sub.subscribing_user_id = u.id inner join "location" l on u.location_id = l.id inner join country c on l.country_id = c.id
        WHERE u_sub.subscribed_user_id = $1
        GROUP BY c.name
        ORDER BY cnt DESC
        LIMIT 5;


END $$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION get_posts_stats(integer) RETURNS SETOF RECORD AS $$
DECLARE
 rec record;
BEGIN
    SELECT 'num_total_posts', count(*) as n_posts_pub
    FROM "content" c inner join post p on c.id = p.content_id
    WHERE c.author_id = $1 and c.most_recent = TRUE INTO rec;

    RETURN NEXT rec;

    SELECT 'num_total_likes', sum(c.likes_difference)
    FROM "content" c inner join post p on c.id = p.content_id
    WHERE c.author_id = $1 and c.most_recent = TRUE INTO rec;

    RETURN NEXT rec;

END $$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION get_comment_stats(user_id INTEGER) RETURNS SETOF RECORD AS $$
DECLARE
    rec record;
    comment_count INTEGER;
    like_count INTEGER;
BEGIN

    SELECT 'comment_count', COUNT(*)
    FROM (SELECT cm.content_id
    FROM "content" c inner join comment cm on c.id = cm.content_id inner join post p on cm.post_id = p.content_id
    WHERE c.author_id = user_id and c.most_recent = TRUE
    UNION
    SELECT rp.content_id
    FROM "content" c inner join reply rp on c.id = rp.content_id inner join "comment" cm on cm.content_id = rp.comment_id inner join post p on cm.post_id = p.content_id
    WHERE c.author_id = user_id and c.most_recent = TRUE) as comments_and_replies INTO rec;

    RETURN NEXT rec;

    SELECT 'like_count', SUM(likes_difference)
    FROM (SELECT cm.content_id, c.likes_difference
        FROM "content" c inner join comment cm on c.id = cm.content_id inner join post p on cm.post_id = p.content_id
        WHERE c.author_id = 1 and c.most_recent = TRUE
        UNION
        SELECT rp.content_id, c.likes_difference
        FROM "content" c inner join reply rp on c.id = rp.content_id inner join "comment" cm on cm.content_id = rp.comment_id inner join post p on cm.post_id = p.content_id
        WHERE c.author_id = 1 and c.most_recent = TRUE) as comments_and_replies INTO rec;

    RETURN NEXT rec;
END

$$ LANGUAGE plpgsql;

-- Remove user reports

CREATE OR REPLACE FUNCTION remove_user_reports() RETURNS TRIGGER AS
$BODY$
BEGIN

    DELETE FROM report WHERE id = OLD.report_id;
	RETURN NULL;
END
$BODY$
LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS remove_user_reports_on_delete ON has_badge;
CREATE TRIGGER remove_user_reports_on_delete
    AFTER DELETE ON user_report
    FOR EACH ROW
    EXECUTE PROCEDURE remove_user_reports();