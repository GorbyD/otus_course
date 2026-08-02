| Запрос | 10к | 10М | 10М + index | 
|---|---|---|-------------|
| Q1 — фильмы на сегодня | 0.115 ms | 10.350 ms | 2.077 ms    |
| Q2 — билеты за неделю | 1.454 ms | 603.322 ms | 14.211 ms   |
| Q3 — афиша на сегодня | 0.340 ms | 11.662 ms | 7.904 ms    | 
| Q4 — топ-3 фильма по выручке | 1.694 ms | 630.647 ms | 426.083 ms  |
| Q5 — карта зала | 0.214 ms | 0.305 ms | 0.325 ms    | 
| Q6 — мин/макс цена сеанса | 0.048 ms | 0.055 ms | 0.049 ms    | 

---

# Q1

## SQL
```sql
SELECT id, title, age_rating, duration_minutes
FROM movies
WHERE id IN (
    SELECT movie_id
    FROM sessions
    WHERE start_time >= CURRENT_DATE
      AND start_time < CURRENT_DATE + INTERVAL '1 day'
)
ORDER BY title;
```

## 10к
```
Hash Semi Join  (cost=16.32..21.32 rows=6 width=30) (actual time=0.078..0.088 rows=6 loops=1)
  Hash Cond: (movies.id = sessions.movie_id)
  ->  Seq Scan on movies  (cost=0.00..4.50 rows=150 width=30) (actual time=0.007..0.013 rows=150 loops=1)
  ->  Hash  (cost=16.25..16.25 rows=6 width=8) (actual time=0.055..0.055 rows=6 loops=1)
        ->  Seq Scan on sessions  (cost=0.00..16.25 rows=6 width=8) (actual time=0.034..0.047 rows=6 loops=1)
              Filter: ((start_time >= CURRENT_DATE) AND (start_time < (CURRENT_DATE + '1 day'::interval)))
              Rows Removed by Filter: 494
Planning Time: 0.524 ms
Execution Time: 0.115 ms
```

## 10М
```
Hash Join  (cost=2568.75..2796.40 rows=867 width=31) (actual time=8.613..10.183 rows=874 loops=1)
  Hash Cond: (movies.id = sessions.movie_id)
  ->  Seq Scan on movies  (cost=0.00..197.00 rows=8000 width=31) (actual time=0.013..0.949 rows=8000 loops=1)
  ->  Hash  (cost=2558.43..2558.43 rows=826 width=8) (actual time=8.585..8.586 rows=874 loops=1)
        ->  HashAggregate  (cost=2550.17..2558.43 rows=826 width=8) (actual time=8.449..8.500 rows=874 loops=1)
              Group Key: sessions.movie_id
              ->  Seq Scan on sessions  (cost=0.00..2548.00 rows=867 width=8) (actual time=0.012..8.246 rows=935 loops=1)
                    Filter: ((start_time >= CURRENT_DATE) AND (start_time < (CURRENT_DATE + '1 day'::interval)))
                    Rows Removed by Filter: 79065
Planning Time: 1.787 ms
Execution Time: 10.350 ms
```

## 10М + index
```
Hash Join  (cost=836.43..1063.55 rows=820 width=31) (actual time=0.942..1.948 rows=874 loops=1)
  Hash Cond: (movies.id = sessions.movie_id)
  ->  Seq Scan on movies  (cost=0.00..197.00 rows=8000 width=31) (actual time=0.006..0.374 rows=8000 loops=1)
  ->  Hash  (cost=826.64..826.64 rows=783 width=8) (actual time=0.921..0.922 rows=874 loops=1)
        ->  HashAggregate  (cost=818.81..826.64 rows=783 width=8) (actual time=0.792..0.840 rows=874 loops=1)
              Group Key: sessions.movie_id
              ->  Bitmap Heap Scan on sessions  (cost=16.70..816.76 rows=820 width=8) (actual time=0.176..0.652 rows=935 loops=1)
                    Recheck Cond: ((start_time >= CURRENT_DATE) AND (start_time < (CURRENT_DATE + '1 day'::interval)))
                    Heap Blocks: exact=544
                    ->  Bitmap Index Scan on idx_sessions_start_time  (cost=0.00..16.50 rows=820 width=0) (actual time=0.127..0.127 rows=935 loops=1)
                          Index Cond: ((start_time >= CURRENT_DATE) AND (start_time < (CURRENT_DATE + '1 day'::interval)))
Planning Time: 0.864 ms
Execution Time: 2.077 ms
```

`Seq Scan on sessions` заменился на `Bitmap Index Scan
using idx_sessions_start_time`. Доступ к `sessions` по времени сеанса теперь избирательный, а не полным сканированием.

---

# Q2

## SQL
```sql
SELECT count(*) AS tickets_sold_last_week
FROM tickets
WHERE status = 'sold'
  AND purchased_at >= now() - interval '7 days';
```

## 10к
```
Aggregate  (cost=243.66..243.67 rows=1 width=8) (actual time=1.438..1.439 rows=1 loops=1)
  ->  Seq Scan on tickets  (cost=0.00..243.00 rows=264 width=0) (actual time=0.016..1.426 rows=263 loops=1)
        Filter: (((status)::text = 'sold'::text) AND (purchased_at >= (now() - '7 days'::interval)))
        Rows Removed by Filter: 7737
Planning Time: 0.145 ms
Execution Time: 1.454 ms
```

## 10М
```
Finalize Aggregate  (cost=168714.91..168714.92 rows=1 width=8) (actual time=581.935..587.618 rows=1 loops=1)
  ->  Gather  (cost=168714.69..168714.90 rows=2 width=8) (actual time=581.730..587.598 rows=3 loops=1)
        Workers Planned: 2
        Workers Launched: 2
        ->  Partial Aggregate  (cost=167714.69..167714.70 rows=1 width=8) (actual time=567.421..567.422 rows=1 loops=3)
              ->  Parallel Seq Scan on tickets  (cost=0.00..167387.89 rows=130720 width=0) (actual time=4.680..562.574 rows=102221 loops=3)
                    Filter: (((status)::text = 'sold'::text) AND (purchased_at >= (now() - '7 days'::interval)))
                    Rows Removed by Filter: 2881112
Planning Time: 0.585 ms
Execution Time: 603.322 ms
```

## 10М + index
```
Finalize Aggregate  (cost=5945.00..5945.01 rows=1 width=8) (actual time=12.050..14.171 rows=1 loops=1)
  ->  Gather  (cost=5944.79..5945.00 rows=2 width=8) (actual time=11.928..14.167 rows=3 loops=1)
        Workers Planned: 2
        Workers Launched: 2
        ->  Partial Aggregate  (cost=4944.79..4944.80 rows=1 width=8) (actual time=9.065..9.065 rows=1 loops=3)
              ->  Parallel Index Only Scan using idx_tickets_purchased_at_sold on tickets  (cost=0.44..4627.05 rows=127093 width=0) (actual time=0.085..5.640 rows=102221 loops=3)
                    Index Cond: (purchased_at >= (now() - '7 days'::interval))
                    Heap Fetches: 0
Planning Time: 0.534 ms
Execution Time: 14.211 ms
```

при параллельном плане `Parallel Seq
Scan on tickets` было прочитано все ~9M строк. Частичный индекс содержит только нужные данные, поэтому план стал
`Parallel Index Only Scan`.
---

# Q3

## SQL
```sql
SELECT m.id, m.title, m.age_rating,
       string_agg(DISTINCT h.name, ', ' ORDER BY h.name) AS halls,
       string_agg(to_char(s.start_time, 'HH24:MI') || ' (' || s.format || ')',
                  ', ' ORDER BY s.start_time) AS showtimes
FROM movies m
         JOIN sessions s ON s.movie_id = m.id
         JOIN halls h ON h.id = s.hall_id
WHERE s.start_time >= CURRENT_DATE
  AND s.start_time < CURRENT_DATE + INTERVAL '1 day'
GROUP BY m.id, m.title, m.age_rating
ORDER BY m.title;
```

## 10к
```
GroupAggregate  (cost=23.75..23.96 rows=6 width=92) (actual time=0.159..0.248 rows=6 loops=1)
  Group Key: m.id
  ->  Sort  (cost=23.75..23.76 rows=6 width=48) (actual time=0.109..0.112 rows=6 loops=1)
        Sort Key: m.id, h.name
        ->  Hash Join  (cost=17.59..23.67 rows=6 width=48) (actual time=0.088..0.099 rows=6 loops=1)
              Hash Cond: (s.hall_id = h.id)
              ->  Hash Join  (cost=16.32..22.38 rows=6 width=47) (actual time=0.069..0.079 rows=6 loops=1)
                    Hash Cond: (m.id = s.movie_id)
                    ->  Seq Scan on movies m  (cost=0.00..4.50 rows=150 width=28)
                    ->  Hash  ->  Seq Scan on sessions s  (cost=0.00..16.25 rows=6 width=27) (actual time=0.030..0.043 rows=6 loops=1)
                          Filter: ((start_time >= CURRENT_DATE) AND (start_time < (CURRENT_DATE + '1 day'::interval)))
                          Rows Removed by Filter: 494
              ->  Hash  ->  Seq Scan on halls h  (cost=0.00..1.12 rows=12 width=17)
Planning Time: 0.559 ms
Execution Time: 0.340 ms
```

## 10М
```
GroupAggregate  (cost=2897.30..2927.65 rows=867 width=93) (actual time=8.831..11.420 rows=874 loops=1)
  Group Key: m.id
  ->  Sort  (cost=2897.30..2899.47 rows=867 width=49) (actual time=8.692..8.761 rows=935 loops=1)
        Sort Key: m.id, h.name
        ->  Hash Join  (cost=302.38..2854.99 rows=867 width=49) (actual time=1.922..8.359 rows=935 loops=1)
              Hash Cond: (s.hall_id = h.id)
              ->  Hash Join  (cost=297.00..2847.28 rows=867 width=48) (actual time=1.873..8.177 rows=935 loops=1)
                    Hash Cond: (s.movie_id = m.id)
                    ->  Seq Scan on sessions s  (cost=0.00..2548.00 rows=867 width=27) (actual time=0.007..6.039 rows=935 loops=1)
                          Filter: ((start_time >= CURRENT_DATE) AND (start_time < (CURRENT_DATE + '1 day'::interval)))
                          Rows Removed by Filter: 79065
                    ->  Hash  ->  Seq Scan on movies m  (cost=0.00..197.00 rows=8000 width=29)
              ->  Hash  ->  Seq Scan on halls h  (cost=0.00..3.50 rows=150 width=17)
Planning Time: 0.950 ms
Execution Time: 11.662 ms
```

## 10М + index
```
GroupAggregate  (cost=1163.18..1191.88 rows=820 width=93) (actual time=3.171..7.475 rows=874 loops=1)
  Group Key: m.id
  ->  Sort  (cost=1163.18..1165.23 rows=820 width=49) (actual time=3.101..3.198 rows=935 loops=1)
        Sort Key: m.id, h.name
        ->  Hash Join  (cost=319.08..1123.50 rows=820 width=49) (actual time=2.114..2.885 rows=935 loops=1)
              Hash Cond: (s.hall_id = h.id)
              ->  Hash Join  (cost=313.70..1115.91 rows=820 width=48) (actual time=2.064..2.721 rows=935 loops=1)
                    Hash Cond: (s.movie_id = m.id)
                    ->  Bitmap Heap Scan on sessions s  (cost=16.70..816.76 rows=820 width=27) (actual time=0.145..0.609 rows=935 loops=1)
                          Recheck Cond: ((start_time >= CURRENT_DATE) AND (start_time < (CURRENT_DATE + '1 day'::interval)))
                          Heap Blocks: exact=544
                          ->  Bitmap Index Scan on idx_sessions_start_time  (cost=0.00..16.50 rows=820 width=0) (actual time=0.101..0.101 rows=935 loops=1)
                    ->  Hash  ->  Seq Scan on movies m  (cost=0.00..197.00 rows=8000 width=29)
              ->  Hash  ->  Seq Scan on halls h  (cost=0.00..3.50 rows=150 width=17)
Planning Time: 1.066 ms
Execution Time: 7.904 ms
```

`Seq Scan on sessions` заменился на `Bitmap Index Scan
using idx_sessions_start_time`.
---

# Q4

## SQL
```sql
SELECT m.id, m.title, SUM(t.price) AS revenue
FROM tickets t
         JOIN sessions s ON s.id = t.session_id
         JOIN movies m ON m.id = s.movie_id
WHERE t.status = 'sold'
  AND t.purchased_at >= now() - interval '7 days'
GROUP BY m.id, m.title
ORDER BY revenue DESC
LIMIT 3;
```

## 10к
```
Limit  (cost=263.08..263.09 rows=3 width=57) (actual time=1.612..1.614 rows=2 loops=1)
  ->  Sort  (cost=263.08..263.45 rows=150 width=57) (actual time=1.612..1.613 rows=2 loops=1)
        Sort Key: (sum(t.price)) DESC
        ->  HashAggregate  (cost=259.27..261.14 rows=150 width=57) (actual time=1.602..1.604 rows=2 loops=1)
              Group Key: m.id
              ->  Hash Join  (cost=6.66..257.95 rows=264 width=32) (actual time=0.071..1.560 rows=263 loops=1)
                    Hash Cond: (s.movie_id = m.id)
                    ->  Nested Loop  (cost=0.28..250.85 rows=264 width=15) (actual time=0.030..1.485 rows=263 loops=1)
                          ->  Seq Scan on tickets t  (cost=0.00..243.00 rows=264 width=15) (actual time=0.016..1.392 rows=263 loops=1)
                                Filter: (((status)::text = 'sold'::text) AND (purchased_at >= (now() - '7 days'::interval)))
                                Rows Removed by Filter: 7737
                          ->  Memoize (Index Scan using sessions_pkey on sessions s)
                    ->  Hash  ->  Seq Scan on movies m  (cost=0.00..4.50 rows=150 width=25)
Planning Time: 0.494 ms
Execution Time: 1.694 ms
```

## 10М
```
Limit  (cost=175381.82..175381.83 rows=3 width=58) (actual time=621.030..628.449 rows=1 loops=1)
  ->  Sort  (cost=175381.82..175401.82 rows=8000 width=58) (actual time=608.084..615.502 rows=1 loops=1)
        Sort Key: (sum(t.price)) DESC
        ->  Finalize GroupAggregate  (cost=173191.63..175278.42 rows=8000 width=58) (actual time=608.032..615.449 rows=1 loops=1)
              Group Key: m.id
              ->  Gather Merge  (cost=173191.63..175058.42 rows=16000 width=58) (actual time=608.010..615.428 rows=3 loops=1)
                    Workers Planned: 2
                    ->  Sort (partial, per worker)
                          ->  Partial HashAggregate
                                ->  Hash Join  (cost=2845.00..170919.37 rows=130720 width=33) (actual time=26.160..579.459 rows=102221 loops=3)
                                      Hash Cond: (s.movie_id = m.id)
                                      ->  Hash Join  (cost=2548.00..170279.05 rows=130720 width=15) (actual time=15.981..556.624 rows=102221 loops=3)
                                            Hash Cond: (t.session_id = s.id)
                                            ->  Parallel Seq Scan on tickets t  (cost=0.00..167387.89 rows=130720 width=15) (actual time=0.044..526.795 rows=102221 loops=3)
                                                  Filter: (((status)::text = 'sold'::text) AND (purchased_at >= (now() - '7 days'::interval)))
                                                  Rows Removed by Filter: 2881112
                                            ->  Hash  ->  Seq Scan on sessions s  (cost=0.00..1548.00 rows=80000 width=16)
                                      ->  Hash  ->  Seq Scan on movies m  (cost=0.00..197.00 rows=8000 width=26)
Planning Time: 0.575 ms
Execution Time: 630.647 ms
```

## 10М + index
```
Limit  (cost=153344.07..153344.08 rows=3 width=58) (actual time=398.097..408.763 rows=1 loops=1)
  ->  Sort  (cost=153344.07..153364.07 rows=8000 width=58) (actual time=383.303..393.968 rows=1 loops=1)
        Sort Key: (sum(t.price)) DESC
        ->  Finalize GroupAggregate  (cost=151153.88..153240.67 rows=8000 width=58) (actual time=383.291..393.956 rows=1 loops=1)
              Group Key: m.id
              ->  Gather Merge  (cost=151153.88..153020.67 rows=16000 width=58) (actual time=383.275..393.939 rows=3 loops=1)
                    Workers Planned: 2
                    ->  Sort (partial, per worker)
                          ->  Partial HashAggregate
                                ->  Hash Join  (cost=6277.38..148899.76 rows=127093 width=33) (actual time=33.308..354.127 rows=102221 loops=3)
                                      Hash Cond: (s.movie_id = m.id)
                                      ->  Hash Join  (cost=5980.38..148268.96 rows=127093 width=15) (actual time=23.236..331.096 rows=102221 loops=3)
                                            Hash Cond: (t.session_id = s.id)
                                            ->  Parallel Bitmap Heap Scan on tickets t  (cost=3432.38..145387.32 rows=127093 width=15) (actual time=8.716..299.887 rows=102221 loops=3)
                                                  Recheck Cond: ((purchased_at >= (now() - '7 days'::interval)) AND ((status)::text = 'sold'::text))
                                                  Rows Removed by Index Recheck: 1025396
                                                  Heap Blocks: exact=18992 lossy=11234
                                                  ->  Bitmap Index Scan on idx_tickets_purchased_at_sold  (cost=0.00..3356.12 rows=305024 width=0) (actual time=18.887..18.888 rows=306663 loops=1)
                                            ->  Hash  ->  Seq Scan on sessions s  (cost=0.00..1548.00 rows=80000 width=16)
                                      ->  Hash  ->  Seq Scan on movies m  (cost=0.00..197.00 rows=8000 width=26)
Planning Time: 0.716 ms
Execution Time: 426.083 ms
```

Оптимизация слабее остальных (не в разы).
Индекс сработал — `Parallel Seq Scan` заменился на `Parallel Bitmap Heap Scan on idx_tickets_purchased_at_sold` — но не полностью: `Heap Blocks: exact=18992 lossy=11234` означает, что совпадений оказалось слишком много (~305 тыс.) и точный список адресов строк не влез в `work_mem`.
Часть страниц Postgres пометил "огрублённо" — просто "где-то тут есть совпадение", без точных адресов — и по таким страницам пришлось вручную перепроверять условие по каждой строке (`Rows Removed by Index Recheck: 1025396`) вместо прямого попадания.
---

# Q5

## SQL
```sql
SELECT st.row_number, st.seat_number, sc.name AS category,
       CASE WHEN t.id IS NULL THEN 'free' ELSE 'occupied' END AS status
FROM seats st
         JOIN sessions se ON se.id = :session_id
         JOIN seat_categories sc ON sc.id = st.seat_category_id
         LEFT JOIN tickets t
                   ON t.seat_id = st.id
                       AND t.session_id = se.id
                       AND t.status = 'sold'
WHERE st.hall_id = se.hall_id
ORDER BY st.row_number, st.seat_number;
```

## 10к
```
Sort  (cost=28.04..28.15 rows=42 width=43) (actual time=0.115..0.117 rows=42 loops=1)
  Sort Key: st.row_number, st.seat_number
  ->  Nested Loop  (cost=12.33..26.91 rows=42 width=43) (actual time=0.081..0.105 rows=42 loops=1)
        ->  Hash Left Join  (cost=12.19..25.32 rows=42 width=20) (actual time=0.071..0.083 rows=42 loops=1)
              Hash Cond: (st.id = t.seat_id)
              ->  Nested Loop  (cost=4.87..17.83 rows=42 width=28) (actual time=0.056..0.065 rows=42 loops=1)
                    ->  Index Scan using sessions_pkey on sessions se  (cost=0.27..8.29 rows=1 width=16) (actual time=0.035..0.035 rows=1 loops=1)
                          Index Cond: (id = 324)
                    ->  Bitmap Heap Scan on seats st  (cost=4.60..9.12 rows=42 width=28) (actual time=0.020..0.024 rows=42 loops=1)
                          Recheck Cond: (hall_id = se.hall_id)
                          ->  Bitmap Index Scan on seats_hall_id_row_number_seat_number_key  (cost=0.00..4.59 rows=42 width=0)
              ->  Hash  ->  Index Scan using idx_tickets_session_id on tickets t  (cost=0.28..7.30 rows=1 width=24) (actual time=0.007..0.007 rows=0 loops=1)
                    Index Cond: (session_id = 324)
                    Filter: ((status)::text = 'sold'::text)
        ->  Memoize (Index Scan using seat_categories_pkey on seat_categories sc)
Planning Time: 0.423 ms
Execution Time: 0.214 ms
```

## 10М
```
Sort  (cost=116.31..116.51 rows=80 width=43) (actual time=0.208..0.211 rows=80 loops=1)
  Sort Key: st.row_number, st.seat_number
  ->  Nested Loop Left Join  (cost=5.77..113.78 rows=80 width=43) (actual time=0.118..0.167 rows=80 loops=1)
        Join Filter: (t.seat_id = st.id)
        ->  Nested Loop  (cost=5.34..108.12 rows=80 width=27) (actual time=0.093..0.131 rows=80 loops=1)
              ->  Nested Loop  (cost=5.20..105.75 rows=80 width=28) (actual time=0.079..0.096 rows=80 loops=1)
                    ->  Index Scan using sessions_pkey on sessions se  (cost=0.29..8.31 rows=1 width=16) (actual time=0.050..0.050 rows=1 loops=1)
                          Index Cond: (id = 16579)
                    ->  Bitmap Heap Scan on seats st  (cost=4.90..96.64 rows=80 width=28) (actual time=0.027..0.038 rows=80 loops=1)
                          Recheck Cond: (hall_id = se.hall_id)
                          ->  Bitmap Index Scan on seats_hall_id_row_number_seat_number_key  (cost=0.00..4.88 rows=80 width=0)
              ->  Memoize (Index Scan using seat_categories_pkey on seat_categories sc)
        ->  Materialize  ->  Index Scan using idx_tickets_session_id on tickets t  (cost=0.43..4.46 rows=1 width=24) (actual time=0.021..0.021 rows=0 loops=1)
              Index Cond: (session_id = 16579)
              Filter: ((status)::text = 'sold'::text)
Planning Time: 1.728 ms
Execution Time: 0.305 ms
```

## 10М + index
```
Sort  (cost=116.31..116.51 rows=80 width=43) (actual time=0.190..0.193 rows=80 loops=1)
  Sort Key: st.row_number, st.seat_number
  ->  Nested Loop Left Join  (cost=5.77..113.78 rows=80 width=43) (actual time=0.107..0.149 rows=80 loops=1)
        Join Filter: (t.seat_id = st.id)
        ->  Nested Loop  (cost=5.34..108.12 rows=80 width=27) (actual time=0.080..0.111 rows=80 loops=1)
              ->  Nested Loop  (cost=5.20..105.75 rows=80 width=28) (actual time=0.067..0.077 rows=80 loops=1)
                    ->  Index Scan using sessions_pkey on sessions se  (cost=0.29..8.31 rows=1 width=16) (actual time=0.043..0.043 rows=1 loops=1)
                          Index Cond: (id = 11860)
                    ->  Bitmap Heap Scan on seats st  (cost=4.90..96.64 rows=80 width=28) (actual time=0.021..0.025 rows=80 loops=1)
                          Recheck Cond: (hall_id = se.hall_id)
                          ->  Bitmap Index Scan on seats_hall_id_row_number_seat_number_key  (cost=0.00..4.88 rows=80 width=0)
              ->  Memoize (Index Scan using seat_categories_pkey on seat_categories sc)
        ->  Materialize  ->  Index Scan using idx_tickets_session_id on tickets t  (cost=0.43..4.46 rows=1 width=24) (actual time=0.021..0.021 rows=0 loops=1)
              Index Cond: (session_id = 11860)
              Filter: ((status)::text = 'sold'::text)
Planning Time: 1.937 ms
Execution Time: 0.325 ms
```

---

# Q6

## SQL
```sql
SELECT session_id, MIN(price) AS min_price, MAX(price) AS max_price
FROM session_seat_prices
WHERE session_id = :session_id
GROUP BY session_id;
```

## 10к
```
GroupAggregate  (cost=4.30..11.43 rows=1 width=72) (actual time=0.015..0.015 rows=1 loops=1)
  ->  Bitmap Heap Scan on session_seat_prices  (cost=4.30..11.41 rows=3 width=14) (actual time=0.010..0.011 rows=3 loops=1)
        Recheck Cond: (session_id = 324)
        ->  Bitmap Index Scan on session_seat_prices_pkey  (cost=0.00..4.30 rows=3 width=0) (actual time=0.004..0.004 rows=3 loops=1)
              Index Cond: (session_id = 324)
Planning Time: 0.113 ms
Execution Time: 0.048 ms
```

## 10М
```
GroupAggregate  (cost=0.42..12.00 rows=1 width=72) (actual time=0.035..0.036 rows=1 loops=1)
  ->  Index Scan using session_seat_prices_pkey on session_seat_prices  (cost=0.42..11.97 rows=3 width=14) (actual time=0.031..0.031 rows=3 loops=1)
        Index Cond: (session_id = 16579)
Planning Time: 0.296 ms
Execution Time: 0.055 ms
```

## 10М + index
```
GroupAggregate  (cost=0.42..12.00 rows=1 width=72) (actual time=0.029..0.030 rows=1 loops=1)
  ->  Index Scan using session_seat_prices_pkey on session_seat_prices  (cost=0.42..11.97 rows=3 width=14) (actual time=0.025..0.025 rows=3 loops=1)
        Index Cond: (session_id = 16579)
Planning Time: 0.304 ms
Execution Time: 0.049 ms
```
