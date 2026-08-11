-- Q1
\o :stage_dir/q1.txt
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, title, age_rating, duration_minutes
FROM movies
WHERE id IN (
    SELECT movie_id FROM sessions
    WHERE start_time >= CURRENT_DATE AND start_time < CURRENT_DATE + INTERVAL '1 day'
    );
\o

-- Q2
\o :stage_dir/q2.txt
EXPLAIN (ANALYZE, BUFFERS)
SELECT count(*) FROM tickets
WHERE status = 'sold' AND purchased_at >= now() - interval '7 days';
\o

-- Q3
\o :stage_dir/q3.txt
EXPLAIN (ANALYZE, BUFFERS)
SELECT m.id, m.title, m.age_rating,
       string_agg(DISTINCT h.name, ', ' ORDER BY h.name) AS halls,
       string_agg(to_char(s.start_time, 'HH24:MI') || ' (' || s.format || ')',
                  ', ' ORDER BY s.start_time) AS showtimes
FROM movies m
         JOIN sessions s ON s.movie_id = m.id
         JOIN halls h ON h.id = s.hall_id
WHERE s.start_time >= CURRENT_DATE AND s.start_time < CURRENT_DATE + INTERVAL '1 day'
GROUP BY m.id, m.title, m.age_rating;
\o

-- Q4
\o :stage_dir/q4.txt
EXPLAIN (ANALYZE, BUFFERS)
SELECT m.id, m.title, SUM(t.price) AS revenue
FROM tickets t
         JOIN sessions s ON s.id = t.session_id
         JOIN movies m ON m.id = s.movie_id
WHERE t.status = 'sold' AND t.purchased_at >= now() - interval '7 days'
GROUP BY m.id, m.title
ORDER BY revenue DESC
    LIMIT 3;
\o

-- Q5/Q6 нужен конкретный session_id
SELECT id AS session_id FROM sessions
WHERE start_time >= CURRENT_DATE AND start_time < CURRENT_DATE + INTERVAL '1 day'
ORDER BY random() LIMIT 1
    \gset

-- Q5
    \o :stage_dir/q5.txt
EXPLAIN (ANALYZE, BUFFERS)
SELECT st.row_number, st.seat_number, sc.name AS category,
       CASE WHEN t.id IS NULL THEN 'free' ELSE 'occupied' END AS status
FROM seats st
         JOIN sessions se ON se.id = :session_id
         JOIN seat_categories sc ON sc.id = st.seat_category_id
         LEFT JOIN tickets t
                   ON t.seat_id = st.id AND t.session_id = se.id AND t.status = 'sold'
WHERE st.hall_id = se.hall_id
ORDER BY st.row_number, st.seat_number;
\o

-- Q6
\o :stage_dir/q6.txt
EXPLAIN (ANALYZE, BUFFERS)
SELECT session_id, MIN(price) AS min_price, MAX(price) AS max_price
FROM session_seat_prices
WHERE session_id = :session_id
GROUP BY session_id;
\o