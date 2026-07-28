-- docker compose exec -T postgres psql -U {username} -d {database} -f  /db/perf/result.sql

-- 10K --
\ir 1_reset.sql
\ir ../schema.sql
\ir ../movie_attr.sql

-- генерация данных
\ir 2_generate_10k.sql
\ir generate_body.sql

-- снятие плана
\set stage_dir /db/perf/results/10k
\ir explain.sql


-- 10M --
\ir 1_reset.sql
\ir ../schema.sql
\ir ../movie_attr.sql

-- генерация данных
\ir 3_generate_10m.sql
\ir generate_body.sql

-- снятие плана
\set stage_dir /db/perf/results/10m
\ir explain.sql


-- 10M + индексы --
\ir 4_indexes.sql

-- снятие плана
\set stage_dir /db/perf/results/10m_optimized
\ir explain.sql
