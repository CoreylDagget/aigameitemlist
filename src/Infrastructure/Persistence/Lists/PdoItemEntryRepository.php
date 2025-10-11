<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Lists;

use DateTimeImmutable;
use GameItemsList\Domain\Lists\ItemEntry;
use GameItemsList\Domain\Lists\ItemEntryRepositoryInterface;
use GameItemsList\Infrastructure\Persistence\UuidGenerator;
use PDO;
use PDOException;
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

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s.u');

        try {
            $this->pdo->beginTransaction();

            $existingStatement = $this->pdo->prepare(
                'SELECT id FROM item_entries WHERE item_definition_id = :item_definition_id AND account_id = :account_id LIMIT 1'
            );
            $existingStatement->execute([
                'item_definition_id' => $itemId,
                'account_id' => $accountId,
            ]);

            $existingRow = $existingStatement->fetch(PDO::FETCH_ASSOC);

            if ($existingRow === false) {
                $id = UuidGenerator::v4();

                $insert = $this->pdo->prepare(
                    'INSERT INTO item_entries (
                        id,
                        item_definition_id,
                        account_id,
                        value_boolean,
                        value_integer,
                        value_text,
                        updated_at
                    ) VALUES (
                        :id,
                        :item_definition_id,
                        :account_id,
                        :value_boolean,
                        :value_integer,
                        :value_text,
                        :updated_at
                    )'
                );

                $insert->execute([
                    'id' => $id,
                    'item_definition_id' => $itemId,
                    'account_id' => $accountId,
                    'value_boolean' => $valueBoolean,
                    'value_integer' => $valueInteger,
                    'value_text' => $valueText,
                    'updated_at' => $now,
                ]);
            } else {
                $id = $existingRow['id'];

                $update = $this->pdo->prepare(
                    'UPDATE item_entries SET
                        value_boolean = :value_boolean,
                        value_integer = :value_integer,
                        value_text = :value_text,
                        updated_at = :updated_at
                    WHERE id = :id'
                );

                $update->execute([
                    'value_boolean' => $valueBoolean,
                    'value_integer' => $valueInteger,
                    'value_text' => $valueText,
                    'updated_at' => $now,
                    'id' => $id,
                ]);
            }

            $this->pdo->commit();
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new RuntimeException('Failed to persist item entry', 0, $exception);
        }

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
