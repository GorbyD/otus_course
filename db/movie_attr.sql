-- Типы атрибутов
CREATE TABLE movie_attribute_types (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code varchar(50) NOT NULL UNIQUE,
    name varchar(100) NOT NULL UNIQUE,
    value_type varchar(20) NOT NULL CHECK (value_type IN
        ('text', 'boolean', 'date', 'timestamp', 'integer', 'numeric', 'float')),
    is_marketing boolean NOT NULL DEFAULT false,
    is_service boolean NOT NULL DEFAULT false,
    CHECK (NOT (is_marketing AND is_service))
);

-- Фтрибуты
CREATE TABLE movie_attributes (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    attribute_type_id bigint NOT NULL REFERENCES movie_attribute_types (id),
    code varchar(100) NOT NULL UNIQUE,
    name varchar(255) NOT NULL,
    description text,
    UNIQUE (attribute_type_id, name)
);

-- Значения
CREATE TABLE movie_attribute_values (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    movie_id bigint NOT NULL REFERENCES movies (id) ON DELETE CASCADE,
    attribute_id bigint NOT NULL REFERENCES movie_attributes (id) ON DELETE CASCADE,
    value_text text,
    value_boolean boolean,
    value_date date,
    value_timestamp timestamp,
    value_integer bigint,
    value_numeric numeric,
    value_float double precision,
    CHECK (num_nonnulls(
                   value_text,
                   value_boolean,
                   value_date,
                   value_timestamp,
                   value_integer,
                   value_numeric,
                   value_float) = 1
        ),
    CHECK (
        value_float IS NULL OR value_float NOT IN (
           'NaN'::double precision,
           'Infinity'::double precision,
           '-Infinity'::double precision
          )
        ),
    UNIQUE (movie_id, attribute_id)
);

-- Индексы
CREATE INDEX idx_movie_attributes_type ON movie_attributes (attribute_type_id);
CREATE INDEX idx_movie_attribute_values_attribute ON movie_attribute_values (attribute_id);
CREATE INDEX idx_movie_attribute_values_movie_attribute ON movie_attribute_values (movie_id, attribute_id);
CREATE INDEX idx_movie_attribute_values_date ON movie_attribute_values (value_date) WHERE value_date IS NOT NULL;

-- Данные
INSERT INTO movie_attribute_types (code, name, value_type, is_marketing, is_service)
VALUES ('reviews', 'Рецензии', 'text', true, false),
       ('awards', 'Премии', 'boolean', true, false),
       ('important_dates', 'Важные даты', 'date', true, false),
       ('service_dates', 'Служебные даты', 'date', false, true);

INSERT INTO movie_attributes (attribute_type_id, code, name)
SELECT id, 'critic_review', 'Рецензия критиков' FROM movie_attribute_types WHERE code = 'reviews'
UNION ALL SELECT id, 'academy_review', 'Отзыв неизвестной киноакадемии' FROM movie_attribute_types WHERE code = 'reviews'
UNION ALL SELECT id, 'oscar', 'Оскар' FROM movie_attribute_types WHERE code = 'awards'
UNION ALL SELECT id, 'nika', 'Ника' FROM movie_attribute_types WHERE code = 'awards'
UNION ALL SELECT id, 'world_premiere', 'Мировая премьера' FROM movie_attribute_types WHERE code = 'important_dates'
UNION ALL SELECT id, 'russian_premiere', 'Премьера в РФ' FROM movie_attribute_types WHERE code = 'important_dates'
UNION ALL SELECT id, 'ticket_sales_start', 'Дата начала продажи билетов' FROM movie_attribute_types WHERE code = 'service_dates'
UNION ALL SELECT id, 'tv_advertising_start', 'Запуск рекламы на ТВ' FROM movie_attribute_types WHERE code = 'service_dates';










CREATE VIEW movie_service_tasks AS
SELECT m.title AS movie,
       string_agg(ma.name, ', ' ORDER BY ma.name)
           FILTER (WHERE mat.is_service AND mav.value_date = CURRENT_DATE) AS tasks_today,
       string_agg(ma.name, ', ' ORDER BY ma.name)
           FILTER (WHERE mat.is_service AND mav.value_date = CURRENT_DATE + 20) AS tasks_in_20_days
FROM movies m
LEFT JOIN movie_attribute_values mav ON mav.movie_id = m.id
LEFT JOIN movie_attributes ma ON ma.id = mav.attribute_id
LEFT JOIN movie_attribute_types mat ON mat.id = ma.attribute_type_id
GROUP BY m.id, m.title;

CREATE VIEW movie_marketing_data AS
SELECT m.title AS movie,
       mat.name AS attribute_type,
       ma.name AS attribute,
       CASE mat.value_type
           WHEN 'text' THEN mav.value_text
           WHEN 'boolean' THEN CASE WHEN mav.value_boolean THEN 'да' ELSE 'нет' END
           WHEN 'date' THEN to_char(mav.value_date, 'YYYY-MM-DD')
           WHEN 'timestamp' THEN to_char(mav.value_timestamp, 'YYYY-MM-DD HH24:MI:SS')
           WHEN 'integer' THEN mav.value_integer::text
           WHEN 'numeric' THEN mav.value_numeric::text
           WHEN 'float' THEN mav.value_float::text
       END AS value
FROM movie_attribute_values mav
JOIN movies m ON m.id = mav.movie_id
JOIN movie_attributes ma ON ma.id = mav.attribute_id
JOIN movie_attribute_types mat ON mat.id = ma.attribute_type_id
WHERE mat.is_marketing;
