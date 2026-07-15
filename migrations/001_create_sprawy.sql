-- Tabela spraw (centralny obiekt Kanbana). Model wg docs/kanban-zapytania-urzedowe.md.
CREATE TABLE IF NOT EXISTS sprawy (
    id                  SERIAL PRIMARY KEY,
    source              VARCHAR(10)  NOT NULL CHECK (source IN ('email', 'skan', 'reczne')),
    gmail_thread_id     VARCHAR(255) UNIQUE,
    scan_path           VARCHAR(1024),
    ocr_text            TEXT,
    subject             VARCHAR(1024) NOT NULL DEFAULT '',
    sender              VARCHAR(512)  NOT NULL DEFAULT '',
    snippet             VARCHAR(2048) NOT NULL DEFAULT '',
    label               VARCHAR(255),
    stage               VARCHAR(20)  NOT NULL DEFAULT 'nowe'
                            CHECK (stage IN ('nowe', 'w_trakcie', 'czekam', 'odlozone', 'zrobione')),
    position            INTEGER      NOT NULL DEFAULT 0,
    google_task_id      VARCHAR(255),
    google_task_list_id VARCHAR(255),
    calendar_event_id   VARCHAR(255),
    notes               TEXT         NOT NULL DEFAULT '',
    archived            BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Kolejnosc kart w kolumnie (drag&drop) czytana po (stage, position).
CREATE INDEX IF NOT EXISTS idx_sprawy_stage_position ON sprawy (stage, position);
