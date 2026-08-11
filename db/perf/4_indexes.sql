-- Q1, Q3: фильтр "сеансы сегодня" (start_time >= CURRENT_DATE AND < +1 day).
-- Без индекса это Seq Scan по всей sessions. "Сегодня" — узкий срез
CREATE INDEX IF NOT EXISTS idx_sessions_start_time ON sessions (start_time);

-- Q2, Q4: "проданные билеты за неделю" (status='sold' AND purchased_at >= now()-7d).
-- tickets — самая большая таблица, полное сканирование - дорого. Индекс частичный, т.к. оба запроса всегда ищут только проданные билеты
CREATE INDEX IF NOT EXISTS idx_tickets_purchased_at_sold
    ON tickets (purchased_at)
    WHERE status = 'sold';

-- Q5: карта зала на конкретный сеанс . session_id уже был проиндексирован, но фильтр по status применялся после чтения строк
CREATE INDEX IF NOT EXISTS idx_tickets_session_status ON tickets (session_id, status);

ANALYZE sessions;
ANALYZE tickets;
