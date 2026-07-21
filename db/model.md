## Таблицы и атрибуты

### cinemas — сеть кинотеатров (площадки)
| Атрибут | Тип        | Ограничения           | Описание                    |
|---------|------------|-----------------------|------------------------------|
| id      | bigint     | PK                    | Идентификатор                |
| name    | varchar(255) | NOT NULL            | Название кинотеатра           |
| address | varchar(500) | NOT NULL            | Адрес                        |
| phone   | varchar(20)  |                      | Контактный телефон            |

### halls — залы
| Атрибут     | Тип         | Ограничения                          | Описание            |
|-------------|-------------|---------------------------------------|----------------------|
| id          | bigint      | PK                                     | Идентификатор        |
| cinema_id   | bigint      | NOT NULL, FK → cinemas(id)             | Кинотеатр, которому принадлежит зал |
| name        | varchar(100)| NOT NULL, UNIQUE(cinema_id, name)      | Название/номер зала   |
| description | text        |                                        | Доп. описание (напр. IMAX-проектор) |

### seat_categories — категории мест
| Атрибут     | Тип         | Ограничения      | Описание                              |
|-------------|-------------|-------------------|----------------------------------------|
| id          | bigint      | PK                 | Идентификатор                          |
| name        | varchar(50) | NOT NULL, UNIQUE   | Название категории (Standard/VIP/Couple)|
| description | text        |                    | Описание категории                     |

### seats — места в зале
| Атрибут           | Тип       | Ограничения                                              | Описание                     |
|-------------------|-----------|------------------------------------------------------------|--------------------------------|
| id                | bigint    | PK                                                           | Идентификатор                  |
| hall_id           | bigint    | NOT NULL, FK → halls(id)                                     | Зал                            |
| row_number        | smallint  | NOT NULL, CHECK > 0                                          | Номер ряда                     |
| seat_number       | smallint  | NOT NULL, CHECK > 0                                          | Номер места в ряду              |
| seat_category_id  | bigint    | NOT NULL, FK → seat_categories(id)                           | Категория места                |
|                   |           | UNIQUE(hall_id, row_number, seat_number)                     | Место в зале уникально          |

### genres — жанры
| Атрибут | Тип         | Ограничения       | Описание       |
|---------|-------------|--------------------|-----------------|
| id      | bigint      | PK                  | Идентификатор   |
| name    | varchar(50) | NOT NULL, UNIQUE    | Название жанра  |

### movies — фильмы
| Атрибут           | Тип          | Ограничения           | Описание                       |
|-------------------|--------------|-------------------------|----------------------------------|
| id                | bigint       | PK                       | Идентификатор                    |
| title             | varchar(255) | NOT NULL                 | Название фильма                  |
| description       | text         |                          | Описание/аннотация                |
| duration_minutes  | smallint     | NOT NULL, CHECK > 0      | Длительность в минутах            |
| release_date      | date         |                          | Дата выхода                       |
| age_rating        | varchar(10)  |                          | Возрастной рейтинг (0+, 16+, ...) |
| country           | varchar(100) |                          | Страна производства               |

### movie_genres — связь фильм–жанр (many-to-many)
| Атрибут  | Тип    | Ограничения                    | Описание |
|----------|--------|-----------------------------------|-----------|
| movie_id | bigint | PK, FK → movies(id)                | Фильм     |
| genre_id | bigint | PK, FK → genres(id)                | Жанр      |

### sessions — сеансы
| Атрибут    | Тип         | Ограничения                                                    | Описание                          |
|------------|-------------|--------------------------------------------------------------------|--------------------------------------|
| id         | bigint      | PK                                                                    | Идентификатор                        |
| movie_id   | bigint      | NOT NULL, FK → movies(id)                                             | Показываемый фильм                    |
| hall_id    | bigint      | NOT NULL, FK → halls(id)                                              | Зал показа                            |
| format     | varchar(10) | NOT NULL, CHECK IN ('2D','3D','IMAX')                                 | Формат показа                         |
| start_time | timestamp   | NOT NULL                                                              | Начало сеанса                         |
| end_time   | timestamp   | NOT NULL, CHECK end_time > start_time                                 | Окончание сеанса                      |

### session_seat_prices — цены мест на конкретный сеанс
| Атрибут          | Тип           | Ограничения                                | Описание                              |
|------------------|---------------|-----------------------------------------------|-----------------------------------------|
| session_id       | bigint        | PK, FK → sessions(id)                          | Сеанс                                   |
| seat_category_id | bigint        | PK, FK → seat_categories(id)                   | Категория места                          |
| price            | numeric(10,2) | NOT NULL, CHECK >= 0                           | Цена билета этой категории на этот сеанс |

### customers — клиенты
| Атрибут       | Тип          | Ограничения          | Описание             |
|---------------|--------------|------------------------|------------------------|
| id            | bigint       | PK                      | Идентификатор          |
| full_name     | varchar(255) | NOT NULL                | ФИО клиента             |
| email         | varchar(255) | NOT NULL, UNIQUE        | Email (используется как логин) |
| phone         | varchar(20)  |                         | Телефон                 |
| registered_at | timestamp    | NOT NULL, DEFAULT now() | Дата регистрации         |

### tickets — билеты
| Атрибут      | Тип           | Ограничения                                              | Описание                                    |
|--------------|---------------|--------------------------------------------------------------|-----------------------------------------------|
| id           | bigint        | PK                                                              | Идентификатор                                |
| session_id   | bigint        | NOT NULL, FK → sessions(id)                                     | Сеанс                                        |
| seat_id      | bigint        | NOT NULL, FK → seats(id)                                        | Место                                        |
| customer_id  | bigint        | NOT NULL, FK → customers(id)                                    | Покупатель                                    |
| price        | numeric(10,2) | NOT NULL, CHECK >= 0                                            | Фактическая цена покупки (снимок на момент продажи) |
| status       | varchar(10)   | NOT NULL, DEFAULT 'sold', CHECK IN ('sold','returned')          | Статус билета                                 |
| purchased_at | timestamp     | NOT NULL, DEFAULT now()                                         | Момент покупки                                |
| returned_at  | timestamp     | NULL, обязателен при status='returned'                          | Момент возврата                               |

