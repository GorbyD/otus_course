-- Кинотеатры
CREATE TABLE cinemas
(
    id      bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name    varchar(255) NOT NULL,
    address varchar(500) NOT NULL,
    phone   varchar(20)
);

-- Залы
CREATE TABLE halls
(
    id          bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    cinema_id   bigint       NOT NULL REFERENCES cinemas (id) ON DELETE CASCADE,
    name        varchar(100) NOT NULL,
    description text,
    UNIQUE (cinema_id, name)
);

-- Категории мест
CREATE TABLE seat_categories
(
    id          bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name        varchar(50) NOT NULL UNIQUE,
    description text
);

-- Места в зале
CREATE TABLE seats
(
    id               bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    hall_id          bigint   NOT NULL REFERENCES halls (id) ON DELETE CASCADE,
    row_number       smallint NOT NULL CHECK (row_number > 0),
    seat_number      smallint NOT NULL CHECK (seat_number > 0),
    seat_category_id bigint   NOT NULL REFERENCES seat_categories (id),
    UNIQUE (hall_id, row_number, seat_number)
);

-- Жанры
CREATE TABLE genres
(
    id   bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name varchar(50) NOT NULL UNIQUE
);

-- Фильмы
CREATE TABLE movies
(
    id               bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    title            varchar(255) NOT NULL,
    description      text,
    duration_minutes smallint     NOT NULL CHECK (duration_minutes > 0),
    release_date     date,
    age_rating       varchar(10),
    country          varchar(100)
);

-- Связь фильм-жанр
CREATE TABLE movie_genres
(
    movie_id bigint NOT NULL REFERENCES movies (id) ON DELETE CASCADE,
    genre_id bigint NOT NULL REFERENCES genres (id) ON DELETE CASCADE,
    PRIMARY KEY (movie_id, genre_id)
);

-- Сеансы
CREATE TABLE sessions
(
    id         bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    movie_id   bigint      NOT NULL REFERENCES movies (id),
    hall_id    bigint      NOT NULL REFERENCES halls (id),
    format     varchar(10) NOT NULL DEFAULT '2D' CHECK (format IN ('2D', '3D', 'IMAX')),
    start_time timestamp   NOT NULL,
    end_time   timestamp   NOT NULL,
    CHECK (end_time > start_time)
);
CREATE INDEX idx_sessions_movie_id ON sessions (movie_id);
CREATE INDEX idx_sessions_hall_id ON sessions (hall_id);

-- Цены мест по категориям для конкретного сеанса
CREATE TABLE session_seat_prices
(
    session_id       bigint         NOT NULL REFERENCES sessions (id) ON DELETE CASCADE,
    seat_category_id bigint         NOT NULL REFERENCES seat_categories (id),
    price            numeric(10, 2) NOT NULL CHECK (price >= 0),
    PRIMARY KEY (session_id, seat_category_id)
);

-- Клиенты
CREATE TABLE customers
(
    id            bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    full_name     varchar(255) NOT NULL,
    email         varchar(255) NOT NULL UNIQUE,
    phone         varchar(20),
    registered_at timestamp    NOT NULL DEFAULT now()
);

-- Билеты
CREATE TABLE tickets
(
    id           bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    session_id   bigint         NOT NULL REFERENCES sessions (id),
    seat_id      bigint         NOT NULL REFERENCES seats (id),
    customer_id  bigint         NOT NULL REFERENCES customers (id),
    price        numeric(10, 2) NOT NULL CHECK (price >= 0),
    status       varchar(10)    NOT NULL DEFAULT 'sold' CHECK (status IN ('sold', 'returned')),
    purchased_at timestamp      NOT NULL DEFAULT now(),
    returned_at  timestamp,
    CHECK (
        (status = 'returned' AND returned_at IS NOT NULL) OR
        (status = 'sold' AND returned_at IS NULL)
        )
);
CREATE INDEX idx_tickets_session_id ON tickets (session_id);
CREATE INDEX idx_tickets_customer_id ON tickets (customer_id);
CREATE INDEX idx_tickets_seat_id ON tickets (seat_id);
