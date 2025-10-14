# Basic concept of internal messenger

Egy egyszerű tábla a belső értesítésekhez:

id, type, title, body, url, created_at, read_at, recipient_user_id, from_user_id

típus lehet: info, alert, new_user, system, stb.


## Table
```sql 
CREATE TABLE messages (
    id BIGSERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    url TEXT NULL,
    from_user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    recipient_user_id BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,
    recipient_role VARCHAR(100) NULL,
    is_read SMALLINT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    read_at TIMESTAMPTZ NULL,

    -- Egyedi azonosító role broadcastnál (hogy később lehessen tisztítani/nyomkövetni)
    CONSTRAINT chk_target CHECK (
        (recipient_role IS NOT NULL AND recipient_user_id IS NULL)
        OR (recipient_role IS NULL AND recipient_user_id IS NOT NULL)
        OR (recipient_role IS NULL AND recipient_user_id IS NULL)
    )
);

-- Gyors keresés és badge-számítás támogatása:
CREATE INDEX idx_messages_unread_user ON messages (recipient_user_id, is_read);
CREATE INDEX idx_messages_role ON messages (recipient_role);
CREATE INDEX idx_messages_created ON messages (created_at DESC);
```

## 🧹 Automatikus tisztítás (cron / scheduler)

30 napnál régebbi olvasott üzenetek törlése:

```sql 
DELETE FROM messages
WHERE is_read = 1
  AND read_at < NOW() - INTERVAL '30 days';
```

Ez később automatizálható egy cleanup_messages.php cron scriptben
(pl. napi egyszer fut a WAMP gépen, vagy Task Scheduler-rel Windows alatt).