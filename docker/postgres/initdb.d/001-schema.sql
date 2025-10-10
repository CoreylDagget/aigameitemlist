CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS citext;

CREATE TABLE IF NOT EXISTS accounts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email CITEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS games (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS lists (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    game_id UUID NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    description TEXT NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT lists_unique_account_game_name UNIQUE (account_id, game_id, name)
);

CREATE TABLE IF NOT EXISTS list_changes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    list_id UUID NOT NULL REFERENCES lists(id) ON DELETE CASCADE,
    actor_account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    type TEXT NOT NULL,
    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
    status TEXT NOT NULL DEFAULT 'pending',
    reviewed_by UUID NULL REFERENCES accounts(id) ON DELETE SET NULL,
    reviewed_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT list_changes_status_check CHECK (status IN ('pending', 'approved', 'rejected'))
);

CREATE INDEX IF NOT EXISTS idx_list_changes_list_id ON list_changes (list_id);
CREATE INDEX IF NOT EXISTS idx_list_changes_status ON list_changes (status);

INSERT INTO games (id, name)
VALUES
    ('11111111-1111-1111-1111-111111111111', 'Elden Ring'),
    ('22222222-2222-2222-2222-222222222222', 'The Legend of Zelda: Tears of the Kingdom'),
    ('33333333-3333-3333-3333-333333333333', 'Final Fantasy XIV')
ON CONFLICT (id) DO NOTHING;
