\timing on

TRUNCATE TABLE
    cinemas, halls, seat_categories, seats, genres, movies,
    movie_genres, sessions, session_seat_prices, customers, tickets
    RESTART IDENTITY CASCADE;

\echo '--- seat_categories ---'
INSERT INTO seat_categories (name, description)
VALUES ('standart', 'Стандартные места'),
       ('comfort', 'Комфортные места'),
       ('vip', 'VIP-места');

\echo '--- genres ---'
INSERT INTO genres (name)
SELECT name
FROM unnest(ARRAY[
    'Фантастика', 'Боевик', 'Драма', 'Комедия', 'Триллер', 'Ужасы', 'Мелодрама', 'Приключения',
    'Анимация', 'Детектив', 'Фэнтези', 'Военный', 'Исторический', 'Спорт', 'Мюзикл', 'Биография',
    'Криминал', 'Семейный', 'Документальный', 'Вестерн'
    ]) AS t(name);

\echo '--- cinemas ---'
INSERT INTO cinemas (name, address, phone)
SELECT 'Кинотеатр №' || gs.n,
       'г. Город ' || 1 + ((gs.n - 1) % 15) || ', ул. Кинозальная, д. ' || gs.n,
       '+7 900 ' || (100 + gs.n)::text || '-00-00'
FROM generate_series(1, :n_cinemas) AS gs(n);

\echo '--- halls ---'
INSERT INTO halls (cinema_id, name, description)
SELECT c.id,
       'Зал ' || h.n,
       CASE WHEN h.n % 5 = 0 THEN 'IMAX' WHEN h.n % 3 = 0 THEN '3D' ELSE '2D' END
FROM cinemas c
         CROSS JOIN generate_series(1, :halls_per_cinema) AS h(n)
ORDER BY c.id, h.n;

\echo '== seats =='
INSERT INTO seats (hall_id, row_number, seat_number, seat_category_id)
SELECT h.id,
       r.n,
       s.n,
       (SELECT id
        FROM seat_categories
        WHERE name = CASE
                         WHEN r.n <= round(:hall_rows * 0.2) THEN 'standart'
                         WHEN r.n <= round(:hall_rows * 0.8) THEN 'comfort'
                         ELSE 'vip'
            END)
FROM halls h
         CROSS JOIN generate_series(1, :hall_rows) AS r(n)
         CROSS JOIN generate_series(1, :hall_seats_per_row) AS s(n);

\echo '--- movies ---'
INSERT INTO movies (title, description, duration_minutes, release_date, age_rating, country)
SELECT 'Фильм №' || gs.n,
       'Описание фильма №' || gs.n,
       60 + floor(random() * 120)::int,
       (CURRENT_DATE - (floor(random() * 3650) || ' days')::interval)::date,
       (ARRAY ['0+','6+','12+','16+','18+'])[floor(random() * 5)::int],
       (ARRAY ['Россия','США','Франция','Южная Корея','Индия','Великобритания'])[floor(random() * 6)::int]
FROM generate_series(1, :n_movies) AS gs(n);

\echo '--- movie_genres ---'
INSERT INTO movie_genres (movie_id, genre_id)
SELECT m.id, g.id
FROM movies m
         CROSS JOIN LATERAL (
    SELECT id FROM genres ORDER BY random() LIMIT (1 + floor(random() * 3)::int)
    ) g;

\echo '--- sessions ---'
-- [-60; +30) дней от CURRENT_DATE для того чтобы какой то сеанс выпал на "сегодня"
INSERT INTO sessions (movie_id, hall_id, format, start_time, end_time)
SELECT mv.id,
       hl.id,
       (ARRAY ['2D','3D','IMAX'])[1 + floor(random() * 3)::int],
       st.start_time,
       st.start_time + (mv.duration_minutes || ' minutes')::interval
FROM (
         SELECT 1 + floor(random() * (SELECT count(*) FROM movies))::bigint AS movie_id,
                1 + floor(random() * (SELECT count(*) FROM halls))::bigint  AS hall_id,
                CURRENT_DATE
                    + (floor(random() * 90) - 60 || ' days')::interval
                    + make_interval(hours => 10 + floor(random() * 13)::int,
                                    mins => (floor(random() * 12) * 5)::int) AS start_time
         FROM generate_series(1, :n_sessions) AS gs(n)
     ) st
         JOIN movies mv ON mv.id = st.movie_id
         JOIN halls hl ON hl.id = st.hall_id;

\echo '--- session_seat_prices ---'
INSERT INTO session_seat_prices (session_id, seat_category_id, price)
SELECT s.id,
       sc.id,
       round((CASE sc.name WHEN 'standart' THEN 250 WHEN 'comfort' THEN 380 ELSE 650 END
                  + random() * 150)::numeric, 2)
FROM sessions s
         CROSS JOIN seat_categories sc;

\echo '--- customers ---'
INSERT INTO customers (full_name, email, phone, registered_at)
SELECT (ARRAY ['Иван','Пётр','Анна','Мария','Сергей','Дарья','Олег','Елена','Игорь','Ольга','Артём','Наталья'])
           [1+ floor(random() * 12)::int]
           || ' ' ||
       (ARRAY ['Иванов','Петров','Сидоров','Кузнецов','Смирнов','Новиков','Морозов','Волков','Захаров','Соколов'])
           [1+ floor(random() * 10)::int],
       'customer' || gs.n || '@example.com',
       '+7 9' || lpad((floor(random() * 99999999))::text, 8, '0'),
       now() - (floor(random() * 1000) || ' days')::interval
FROM generate_series(1, :n_customers) AS gs(n);

\echo '--- tickets---'
-- выбираем для каждого зала минимальный id места и их количество.
CREATE TEMP TABLE hall_seat_ranges AS
SELECT hall_id, min(id) AS min_seat_id, count(*) AS seat_cnt
FROM seats
GROUP BY hall_id;

INSERT INTO tickets (session_id, seat_id, customer_id, price, status, purchased_at, returned_at)
SELECT t.session_id,
       t.seat_id,
       1 + floor(random() * :n_customers)::bigint,
       ssp.price,
       CASE WHEN t.is_returned THEN 'returned' ELSE 'sold' END,
       t.purchased_at,
       CASE
           WHEN t.is_returned THEN t.purchased_at + (floor(random() * 48) || ' hours')::interval
           END
FROM (
         SELECT se.id AS session_id,
                hsr.min_seat_id + floor(random() * hsr.seat_cnt)::bigint AS seat_id,
                (CURRENT_DATE
                    - (floor(random() * 180) || ' days')::interval
                    - (floor(random() * 24) || ' hours')::interval) AS purchased_at,
                (random() < 0.03) AS is_returned
         FROM generate_series(1, :n_tickets) AS gg(n)
                  JOIN sessions se
                       ON se.id = 1 + floor(random() * (SELECT count(*) FROM sessions))::bigint
                  JOIN hall_seat_ranges hsr ON hsr.hall_id = se.hall_id
     ) t
         JOIN seats st ON st.id = t.seat_id
         JOIN session_seat_prices ssp
              ON ssp.session_id = t.session_id AND ssp.seat_category_id = st.seat_category_id;

DROP TABLE hall_seat_ranges;

\echo '--- ANALYZE ---'
ANALYZE;

\echo '--- count all ---'
SELECT 'cinemas' AS table_name, count(*) FROM cinemas
UNION ALL
SELECT 'halls', count(*) FROM halls
UNION ALL
SELECT 'seat_categories', count(*) FROM seat_categories
UNION ALL
SELECT 'seats', count(*) FROM seats
UNION ALL
SELECT 'genres', count(*) FROM genres
UNION ALL
SELECT 'movies', count(*) FROM movies
UNION ALL
SELECT 'movie_genres', count(*) FROM movie_genres
UNION ALL
SELECT 'sessions', count(*) FROM sessions
UNION ALL
SELECT 'session_seat_prices', count(*) FROM session_seat_prices
UNION ALL
SELECT 'customers', count(*) FROM customers
UNION ALL
SELECT 'tickets', count(*) FROM tickets;
