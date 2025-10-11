<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Lists;

use GameItemsList\Domain\Lists\ItemEntry;
use GameItemsList\Domain\Lists\ItemEntryRepositoryInterface;
use GameItemsList\Infrastructure\Persistence\UuidGenerator;
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

        return array_map(static fn (array $row): ItemEntry => ItemEntry::fromDatabaseRow($row), $rows);
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

        $id = UuidGenerator::v4();

        $sql = <<<'SQL'
            INSERT INTO item_entries (id, item_definition_id, account_id, value_boolean, value_integer, value_text)
            VALUES (:id, :item_definition_id, :account_id, :value_boolean, :value_integer, :value_text)
            ON DUPLICATE KEY UPDATE
                value_boolean = VALUES(value_boolean),
                value_integer = VALUES(value_integer),
                value_text = VALUES(value_text),
                updated_at = CURRENT_TIMESTAMP(6)
        SQL;

        $statement = $this->pdo->prepare($sql);

        $statement->execute([
            'id' => $id,
            'item_definition_id' => $itemId,
            'account_id' => $accountId,
            'value_boolean' => $valueBoolean,
            'value_integer' => $valueInteger,
            'value_text' => $valueText,
        ]);

        $selectSql = <<<'SQL'
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
            WHERE e.item_definition_id = :item_definition_id
              AND e.account_id = :account_id
              AND d.list_id = :list_id
            LIMIT 1
        SQL;

        $select = $this->pdo->prepare($selectSql);

        $select->execute([
            'item_definition_id' => $itemId,
            'account_id' => $accountId,
            'list_id' => $listId,
        ]);

        $row = $select->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Failed to persist item entry');
        }

        return ItemEntry::fromDatabaseRow($row);
    }
}
