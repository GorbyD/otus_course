CREATE INDEX IF NOT EXISTS idx_sessions_start_time ON sessions (start_time);

CREATE INDEX IF NOT EXISTS idx_tickets_purchased_at_sold
    ON tickets (purchased_at)
    WHERE status = 'sold';

CREATE INDEX IF NOT EXISTS idx_tickets_session_status ON tickets (session_id, status);

ANALYZE sessions;
ANALYZE tickets;
