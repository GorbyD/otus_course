-- сброс
\ir 1_reset.sql

-- создание таблиц
\ir ../schema.sql
\ir ../movie_attr.sql

-- генерация данных
\ir 2_generate_10k.sql
-- \ir 3_generate_10m.sql
\ir generate_body.sql
