CREATE TABLE IF NOT EXISTS accounts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refresh_token_sessions (
    id CHAR(36) NOT NULL PRIMARY KEY,
    account_id CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    expires_at DATETIME(6) NOT NULL,
    revoked_at DATETIME(6) NULL,
    CONSTRAINT refresh_token_sessions_account_fk FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_refresh_token_sessions_account_id ON refresh_token_sessions (account_id);

CREATE TABLE IF NOT EXISTS refresh_tokens (
    id CHAR(36) NOT NULL PRIMARY KEY,
    session_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    expires_at DATETIME(6) NOT NULL,
    used_at DATETIME(6) NULL,
    revoked_at DATETIME(6) NULL,
    CONSTRAINT refresh_tokens_session_fk FOREIGN KEY (session_id) REFERENCES refresh_token_sessions(id) ON DELETE CASCADE,
    CONSTRAINT refresh_tokens_account_fk FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_refresh_tokens_session_id ON refresh_tokens (session_id);
CREATE INDEX IF NOT EXISTS idx_refresh_tokens_account_id ON refresh_tokens (account_id);

CREATE TABLE IF NOT EXISTS games (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS game_item_templates (
    id CHAR(36) NOT NULL PRIMARY KEY,
    game_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_url TEXT NULL,
    storage_type VARCHAR(20) NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CONSTRAINT game_item_templates_storage_type_check CHECK (storage_type IN ('boolean', 'count', 'text')),
    CONSTRAINT game_item_templates_game_fk FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    CONSTRAINT game_item_templates_unique_name UNIQUE (game_id, name)
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

CREATE TABLE IF NOT EXISTS list_share_tokens (
    id CHAR(36) NOT NULL PRIMARY KEY,
    list_id CHAR(36) NOT NULL,
    token CHAR(64) NOT NULL UNIQUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    revoked_at DATETIME(6) NULL,
    CONSTRAINT list_share_tokens_list_fk FOREIGN KEY (list_id) REFERENCES lists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_list_share_tokens_list_id ON list_share_tokens (list_id);

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
    ('33333333-3333-3333-3333-333333333333', 'Final Fantasy XIV'),
    ('55555555-5555-5555-5555-555555555555', 'Minecraft'),
    ('66666666-6666-6666-6666-666666666666', 'World of Warcraft');

INSERT IGNORE INTO game_item_templates (id, game_id, name, description, image_url, storage_type)
VALUES
    ('aaaaaaa1-aaaa-4aaa-aaaa-aaaaaaaaaaa1', '11111111-1111-1111-1111-111111111111', 'Smithing Stone [1]', 'Upgrade material for low-level weapons.', NULL, 'count'),
    ('aaaaaaa2-aaaa-4aaa-aaaa-aaaaaaaaaaa2', '11111111-1111-1111-1111-111111111111', 'Golden Rune', 'Consumable that grants a burst of runes.', NULL, 'count'),
    ('aaaaaaa3-aaaa-4aaa-aaaa-aaaaaaaaaaa3', '11111111-1111-1111-1111-111111111111', 'Sacred Tear', 'Improves the effectiveness of flasks.', NULL, 'count'),
    ('bbbbbbb1-bbbb-4bbb-bbbb-bbbbbbbbbbb1', '22222222-2222-2222-2222-222222222222', 'Korok Seed', 'Hidden puzzle rewards used to expand inventory slots.', NULL, 'count'),
    ('bbbbbbb2-bbbb-4bbb-bbbb-bbbbbbbbbbb2', '22222222-2222-2222-2222-222222222222', 'Zonaite Battery', 'Additional energy cell for Zonai devices.', NULL, 'count'),
    ('bbbbbbb3-bbbb-4bbb-bbbb-bbbbbbbbbbb3', '22222222-2222-2222-2222-222222222222', 'Master Sword Durability', 'Track whether the Master Sword has been recovered.', NULL, 'boolean'),
    ('ccccccc1-cccc-4ccc-cccc-ccccccccccc1', '33333333-3333-3333-3333-333333333333', 'Tomestone of Causality', 'Weekly-capped endgame currency.', NULL, 'count'),
    ('ccccccc2-cccc-4ccc-cccc-ccccccccccc2', '33333333-3333-3333-3333-333333333333', 'Raid Weapon Token', 'Token exchanged for raid weapon coffers.', NULL, 'count'),
    ('ccccccc3-cccc-4ccc-cccc-ccccccccccc3', '33333333-3333-3333-3333-333333333333', 'Housing Lottery Entry', 'Track submissions to the housing lottery.', NULL, 'text');

INSERT IGNORE INTO accounts (id, email, password_hash, is_admin)
VALUES
    ('44444444-4444-4444-4444-444444444444', 'demo@example.com', '$2y$12$ANYzvRJAHOcKJ/EvZrireO8D0jBTp0qvZJEjZSd9VKlfidOn8AEYC', 0);

INSERT IGNORE INTO lists (id, account_id, game_id, name, description, is_published)
VALUES
    ('77777777-7777-7777-7777-777777777777', '44444444-4444-4444-4444-444444444444', '55555555-5555-5555-5555-555555555555', 'Minecraft Survival Kit', 'Starter checklist for a fresh world.', 1),
    ('88888888-8888-8888-8888-888888888888', '44444444-4444-4444-4444-444444444444', '66666666-6666-6666-6666-666666666666', 'Raid Night Preparation', 'Consumables and weekly tasks ready for raid.', 1);

INSERT IGNORE INTO item_definitions (id, list_id, name, description, image_url, storage_type)
VALUES
    ('10101010-aaaa-4aaa-aaaa-000000000001', '77777777-7777-7777-7777-777777777777', 'Gather Oak Logs', 'Stock up on building material for the first shelter.', NULL, 'count'),
    ('10101010-aaaa-4aaa-aaaa-000000000002', '77777777-7777-7777-7777-777777777777', 'Craft Stone Pickaxe', 'Upgrade tools after the first night.', NULL, 'boolean'),
    ('10101010-aaaa-4aaa-aaaa-000000000003', '77777777-7777-7777-7777-777777777777', 'Build First Shelter', 'Secure a lit shelter before nightfall.', NULL, 'boolean'),
    ('10101010-aaaa-4aaa-aaaa-000000000004', '77777777-7777-7777-7777-777777777777', 'Stock Cooked Beef', 'Keep a stash of cooked food for adventures.', NULL, 'count'),
    ('20202020-bbbb-4bbb-bbbb-000000000001', '88888888-8888-8888-8888-888888888888', 'Flask of Alchemical Chaos', 'Carry flasks for every boss pull.', NULL, 'count'),
    ('20202020-bbbb-4bbb-bbbb-000000000002', '88888888-8888-8888-8888-888888888888', 'Feast of the Divine', 'Have a raid feast ready for the group.', NULL, 'count'),
    ('20202020-bbbb-4bbb-bbbb-000000000003', '88888888-8888-8888-8888-888888888888', 'Weapon Oils Restocked', 'Ensure temporary weapon enchants are available.', NULL, 'boolean'),
    ('20202020-bbbb-4bbb-bbbb-000000000004', '88888888-8888-8888-8888-888888888888', 'Augment Runes', 'Purchase augment runes before raid night.', NULL, 'count'),
    ('20202020-bbbb-4bbb-bbbb-000000000005', '88888888-8888-8888-8888-888888888888', 'Repair Hammer & Kits', 'Bring repair tools for post-wipe fixes.', NULL, 'boolean'),
    ('20202020-bbbb-4bbb-bbbb-000000000006', '88888888-8888-8888-8888-888888888888', 'Weekly Mythic+ Completed', 'Finish at least one Mythic+ for the vault.', NULL, 'boolean'),
    ('20202020-bbbb-4bbb-bbbb-000000000007', '88888888-8888-8888-8888-888888888888', 'Raid Strategy Notes', 'Keep personal notes handy for each encounter.', NULL, 'text'),
    ('20202020-bbbb-4bbb-bbbb-000000000008', '88888888-8888-8888-8888-888888888888', 'Bonus Rolls Available', 'Confirm seals or tokens are ready to spend.', NULL, 'count');

INSERT IGNORE INTO item_entries (id, item_definition_id, account_id, value_boolean, value_integer, value_text)
VALUES
    ('30303030-cccc-4ccc-cccc-000000000001', '10101010-aaaa-4aaa-aaaa-000000000001', '44444444-4444-4444-4444-444444444444', NULL, 64, NULL),
    ('30303030-cccc-4ccc-cccc-000000000002', '10101010-aaaa-4aaa-aaaa-000000000002', '44444444-4444-4444-4444-444444444444', 1, NULL, NULL),
    ('30303030-cccc-4ccc-cccc-000000000003', '10101010-aaaa-4aaa-aaaa-000000000003', '44444444-4444-4444-4444-444444444444', 1, NULL, NULL),
    ('30303030-cccc-4ccc-cccc-000000000004', '10101010-aaaa-4aaa-aaaa-000000000004', '44444444-4444-4444-4444-444444444444', NULL, 12, NULL),
    ('40404040-dddd-4ddd-dddd-000000000001', '20202020-bbbb-4bbb-bbbb-000000000001', '44444444-4444-4444-4444-444444444444', NULL, 2, NULL),
    ('40404040-dddd-4ddd-dddd-000000000002', '20202020-bbbb-4bbb-bbbb-000000000002', '44444444-4444-4444-4444-444444444444', NULL, 1, NULL),
    ('40404040-dddd-4ddd-dddd-000000000003', '20202020-bbbb-4bbb-bbbb-000000000003', '44444444-4444-4444-4444-444444444444', 1, NULL, NULL),
    ('40404040-dddd-4ddd-dddd-000000000004', '20202020-bbbb-4bbb-bbbb-000000000004', '44444444-4444-4444-4444-444444444444', NULL, 3, NULL),
    ('40404040-dddd-4ddd-dddd-000000000005', '20202020-bbbb-4bbb-bbbb-000000000005', '44444444-4444-4444-4444-444444444444', 1, NULL, NULL),
    ('40404040-dddd-4ddd-dddd-000000000006', '20202020-bbbb-4bbb-bbbb-000000000006', '44444444-4444-4444-4444-444444444444', 0, NULL, NULL),
    ('40404040-dddd-4ddd-dddd-000000000007', '20202020-bbbb-4bbb-bbbb-000000000007', '44444444-4444-4444-4444-444444444444', NULL, NULL, 'Focus adds during phase two and rotate healer cooldowns.'),
    ('40404040-dddd-4ddd-dddd-000000000008', '20202020-bbbb-4bbb-bbbb-000000000008', '44444444-4444-4444-4444-444444444444', NULL, 2, NULL);
