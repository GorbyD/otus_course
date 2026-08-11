\o /db/perf/results/top15.txt
SELECT n.nspname || '.' || c.relname                 AS object_name,
       CASE c.relkind
           WHEN 'r' THEN 'table'
           WHEN 'i' THEN 'index'
           ELSE c.relkind::text
           END                                        AS object_type,
       pg_size_pretty(pg_relation_size(c.oid))         AS size,
       pg_relation_size(c.oid)                         AS size_bytes
FROM pg_class c
         JOIN pg_namespace n ON n.oid = c.relnamespace
WHERE n.nspname = 'public'
  AND c.relkind IN ('r', 'i')
ORDER BY pg_relation_size(c.oid) DESC
LIMIT 15;
\o

\o /db/perf/results/top5.txt
SELECT schemaname,
       relname     AS table_name,
       indexrelname AS index_name,
       idx_scan
FROM pg_stat_user_indexes
ORDER BY idx_scan DESC
    LIMIT 5;

SELECT schemaname,
       relname     AS table_name,
       indexrelname AS index_name,
       idx_scan
FROM pg_stat_user_indexes
ORDER BY idx_scan ASC, indexrelname
    LIMIT 5;
\o