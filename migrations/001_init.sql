CREATE TABLE cache_entries (
    cache_key    TEXT    PRIMARY KEY,
    resource     TEXT    NOT NULL,
    payload      TEXT    NOT NULL,
    is_not_found INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT    NOT NULL,
    expires_at   TEXT    NOT NULL
);

CREATE INDEX idx_cache_entries_expires_at ON cache_entries (expires_at);

CREATE TABLE api_tokens (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT    NOT NULL,
    token_hash   TEXT    NOT NULL UNIQUE,
    is_active    INTEGER NOT NULL DEFAULT 1,
    daily_limit  INTEGER NOT NULL,
    created_at   TEXT    NOT NULL,
    last_used_at TEXT
);

CREATE TABLE request_log (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    request_id   TEXT    NOT NULL,
    token_id     INTEGER REFERENCES api_tokens (id),
    method       TEXT    NOT NULL,
    path         TEXT    NOT NULL,
    status       INTEGER NOT NULL,
    duration_ms  INTEGER NOT NULL,
    cache_status TEXT,
    client_ip    TEXT,
    created_at   TEXT    NOT NULL
);

CREATE INDEX idx_request_log_token_created ON request_log (token_id, created_at);

CREATE TABLE upstream_calls (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    request_id          TEXT    NOT NULL,
    endpoint            TEXT    NOT NULL,
    attempt             INTEGER NOT NULL,
    http_status         INTEGER,
    meta_status         TEXT,
    today_request_count INTEGER,
    balance             REAL,
    duration_ms         INTEGER NOT NULL,
    error               TEXT,
    created_at          TEXT    NOT NULL
);

CREATE INDEX idx_upstream_calls_created ON upstream_calls (created_at);
