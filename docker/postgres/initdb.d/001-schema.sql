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

CREATE TABLE IF NOT EXISTS list_tags (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    list_id UUID NOT NULL REFERENCES lists(id) ON DELETE CASCADE,
    name CITEXT NOT NULL,
    color TEXT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT list_tags_unique_list_name UNIQUE (list_id, name),
    CONSTRAINT list_tags_color_format CHECK (color IS NULL OR color ~ '^#[0-9A-Fa-f]{6}$')
);

CREATE TABLE IF NOT EXISTS item_definitions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    list_id UUID NOT NULL REFERENCES lists(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    description TEXT NULL,
    image_url TEXT NULL,
    storage_type TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT item_definitions_storage_type_check CHECK (storage_type IN ('boolean', 'count', 'text'))
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_item_definitions_unique_name
    ON item_definitions (list_id, lower(name));

CREATE TABLE IF NOT EXISTS item_definition_tags (
    item_definition_id UUID NOT NULL REFERENCES item_definitions(id) ON DELETE CASCADE,
    tag_id UUID NOT NULL REFERENCES list_tags(id) ON DELETE CASCADE,
    PRIMARY KEY (item_definition_id, tag_id)
);

CREATE INDEX IF NOT EXISTS idx_item_definition_tags_tag_id ON item_definition_tags (tag_id);

CREATE TABLE IF NOT EXISTS item_entries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    item_definition_id UUID NOT NULL REFERENCES item_definitions(id) ON DELETE CASCADE,
    account_id UUID NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
    value_boolean BOOLEAN NULL,
    value_integer INTEGER NULL,
    value_text TEXT NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT item_entries_value_check CHECK (
        ((value_boolean IS NOT NULL)::int +
         (value_integer IS NOT NULL)::int +
         (value_text IS NOT NULL)::int) = 1
    ),
    CONSTRAINT item_entries_unique_account_item UNIQUE (item_definition_id, account_id)
);

CREATE INDEX IF NOT EXISTS idx_item_entries_account ON item_entries (account_id);

INSERT INTO games (id, name)
VALUES
    ('11111111-1111-1111-1111-111111111111', 'Elden Ring'),
    ('22222222-2222-2222-2222-222222222222', 'The Legend of Zelda: Tears of the Kingdom'),
    ('33333333-3333-3333-3333-333333333333', 'Final Fantasy XIV')
ON CONFLICT (id) DO NOTHING;
