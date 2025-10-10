<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Lists;

use GameItemsList\Domain\Lists\ItemEntry;
use GameItemsList\Domain\Lists\ItemEntryRepositoryInterface;
use PDO;
use RuntimeException;

final class PdoItemEntryRepository implements ItemEntryRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByListAndAccount(string $listId, string $accountId): array
    {
        $sql = <<<'SQL'
            SELECT
                e.id,
                e.item_definition_id,
                e.account_id,
                e.value_boolean,
                e.value_integer,
                e.value_text,
                e.updated_at,
                d.list_id
            FROM item_entries e
            INNER JOIN item_definitions d ON d.id = e.item_definition_id
            WHERE d.list_id = :list_id AND e.account_id = :account_id
            ORDER BY e.updated_at DESC
        SQL;

        $statement = $this->pdo->prepare($sql);

        $statement->execute([
            'list_id' => $listId,
            'account_id' => $accountId,
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn(array $row): ItemEntry => ItemEntry::fromDatabaseRow($row), $rows);
    }

    public function upsert(
        string $listId,
        string $itemId,
        string $accountId,
        bool|int|string $value,
        string $storageType,
    ): ItemEntry {
        $valueBoolean = null;
        $valueInteger = null;
        $valueText = null;

        if ($storageType === 'boolean') {
            $valueBoolean = (bool) $value;
        } elseif ($storageType === 'count') {
            $valueInteger = (int) $value;
        } else {
            $valueText = (string) $value;
        }

        $sql = <<<'SQL'
            WITH upsert AS (
                INSERT INTO item_entries (item_definition_id, account_id, value_boolean, value_integer, value_text)
                VALUES (:item_definition_id, :account_id, :value_boolean, :value_integer, :value_text)
                ON CONFLICT (item_definition_id, account_id) DO UPDATE SET
                    value_boolean = EXCLUDED.value_boolean,
                    value_integer = EXCLUDED.value_integer,
                    value_text = EXCLUDED.value_text,
                    updated_at = NOW()
                RETURNING id, item_definition_id, account_id, value_boolean, value_integer, value_text, updated_at
            )
            SELECT
                u.id,
                u.item_definition_id,
                u.account_id,
                u.value_boolean,
                u.value_integer,
                u.value_text,
                u.updated_at,
                d.list_id
            FROM upsert u
            INNER JOIN item_definitions d ON d.id = u.item_definition_id
            WHERE d.list_id = :list_id
        SQL;

        $statement = $this->pdo->prepare($sql);

        $statement->execute([
            'item_definition_id' => $itemId,
            'account_id' => $accountId,
            'value_boolean' => $valueBoolean,
            'value_integer' => $valueInteger,
            'value_text' => $valueText,
            'list_id' => $listId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Failed to persist item entry');
        }

        return ItemEntry::fromDatabaseRow($row);
    }
}
