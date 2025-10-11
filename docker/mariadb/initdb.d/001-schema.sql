CREATE TABLE IF NOT EXISTS accounts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS games (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lists (
    id CHAR(36) NOT NULL PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    game_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT lists_unique_account_game_name UNIQUE (account_id, game_id, name),
    CONSTRAINT lists_account_fk FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    CONSTRAINT lists_game_fk FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS list_changes (
    id CHAR(36) NOT NULL PRIMARY KEY,
    list_id CHAR(36) NOT NULL,
    actor_account_id CHAR(36) NOT NULL,
    type VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reviewed_by CHAR(36) NULL,
    reviewed_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT list_changes_status_check CHECK (status IN ('pending', 'approved', 'rejected')),
    CONSTRAINT list_changes_list_fk FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE,
    CONSTRAINT list_changes_actor_fk FOREIGN KEY (actor_account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    CONSTRAINT list_changes_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_list_changes_list_id ON list_changes (list_id);
CREATE INDEX IF NOT EXISTS idx_list_changes_status ON list_changes (status);

CREATE TABLE IF NOT EXISTS list_tags (
    id CHAR(36) NOT NULL PRIMARY KEY,
    list_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    color CHAR(7) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT list_tags_unique_list_name UNIQUE (list_id, name),
    CONSTRAINT list_tags_color_format CHECK (color IS NULL OR color REGEXP '^#[0-9A-Fa-f]{6}$'),
    CONSTRAINT list_tags_list_fk FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_definitions (
    id CHAR(36) NOT NULL PRIMARY KEY,
    list_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_url TEXT NULL,
    storage_type VARCHAR(20) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT item_definitions_storage_type_check CHECK (storage_type IN ('boolean', 'count', 'text')),
    CONSTRAINT item_definitions_list_fk FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE,
    CONSTRAINT item_definitions_unique_name UNIQUE (list_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS item_definition_tags (
    item_definition_id CHAR(36) NOT NULL,
    tag_id CHAR(36) NOT NULL,
    PRIMARY KEY (item_definition_id, tag_id),
    CONSTRAINT item_definition_tags_item_fk FOREIGN KEY (item_definition_id) REFERENCES item_definitions(id) ON DELETE CASCADE,
    CONSTRAINT item_definition_tags_tag_fk FOREIGN KEY (tag_id) REFERENCES list_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_item_definition_tags_tag_id ON item_definition_tags (tag_id);

CREATE TABLE IF NOT EXISTS item_entries (
    id CHAR(36) NOT NULL PRIMARY KEY,
    item_definition_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    value_boolean TINYINT(1) NULL,
    value_integer INT NULL,
    value_text TEXT NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    CONSTRAINT item_entries_value_check CHECK (
        ((value_boolean IS NOT NULL) +
        (value_integer IS NOT NULL) +
        (value_text IS NOT NULL)) = 1
    ),
    CONSTRAINT item_entries_unique_account_item UNIQUE (item_definition_id, account_id),
    CONSTRAINT item_entries_item_fk FOREIGN KEY (item_definition_id) REFERENCES item_definitions(id) ON DELETE CASCADE,
    CONSTRAINT item_entries_account_fk FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_item_entries_account ON item_entries (account_id);

INSERT IGNORE INTO games (id, name)
VALUES
    ('11111111-1111-1111-1111-111111111111', 'Elden Ring'),
    ('22222222-2222-2222-2222-222222222222', 'The Legend of Zelda: Tears of the Kingdom'),
    ('33333333-3333-3333-3333-333333333333', 'Final Fantasy XIV');
