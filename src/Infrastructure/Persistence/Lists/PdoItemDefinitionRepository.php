<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Lists;

use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\Tag;
use InvalidArgumentException;
use PDO;

final class PdoItemDefinitionRepository implements ItemDefinitionRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByList(
        string $listId,
        ?string $accountId = null,
        ?string $tagId = null,
        ?bool $owned = null,
        ?string $search = null,
    ): array {
        $conditions = ['i.list_id = :list_id'];
        $parameters = ['list_id' => $listId];

        if ($tagId !== null) {
            $conditions[] = 'EXISTS (
                SELECT 1 FROM item_definition_tags idt
                WHERE idt.item_definition_id = i.id AND idt.tag_id = :tag_id
            )';
            $parameters['tag_id'] = $tagId;
        }

        if ($search !== null && $search !== '') {
            $conditions[] = '(i.name ILIKE :search OR i.description ILIKE :search)';
            $parameters['search'] = '%' . $search . '%';
        }

        if ($owned !== null) {
            if ($accountId === null) {
                throw new InvalidArgumentException('Account id required when filtering by ownership');
            }

            $parameters['account_id'] = $accountId;

            if ($owned) {
                $conditions[] = 'EXISTS (
                    SELECT 1 FROM item_entries e
                    WHERE e.item_definition_id = i.id AND e.account_id = :account_id
                )';
            } else {
                $conditions[] = 'NOT EXISTS (
                    SELECT 1 FROM item_entries e
                    WHERE e.item_definition_id = i.id AND e.account_id = :account_id
                )';
            }
        }

        $sql = sprintf(
            'SELECT i.* FROM item_definitions i WHERE %s ORDER BY i.name',
            implode(' AND ', $conditions)
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if ($rows === []) {
            return [];
        }

        $itemIds = array_map(static fn(array $row): string => $row['id'], $rows);
        $tags = $this->loadTagsForItems($itemIds);

        $items = [];

        foreach ($rows as $row) {
            $items[] = ItemDefinition::fromDatabaseRow($row, $tags[$row['id']] ?? []);
        }

        return $items;
    }

    public function findByIdForList(string $itemId, string $listId): ?ItemDefinition
    {
        $statement = $this->pdo->prepare('SELECT * FROM item_definitions WHERE id = :item_id AND list_id = :list_id LIMIT 1');
        $statement->execute([
            'item_id' => $itemId,
            'list_id' => $listId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $tags = $this->loadTagsForItems([$itemId]);

        return ItemDefinition::fromDatabaseRow($row, $tags[$itemId] ?? []);
    }

    /**
     * @param string[] $itemIds
     * @return array<string, Tag[]>
     */
    private function loadTagsForItems(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        $placeholders = [];
        $parameters = [];

        foreach (array_values($itemIds) as $index => $itemId) {
            $placeholder = ':item_' . $index;
            $placeholders[] = $placeholder;
            $parameters[$placeholder] = $itemId;
        }

        $sql = sprintf(
            'SELECT idt.item_definition_id, t.id, t.list_id, t.name, t.color
             FROM item_definition_tags idt
             INNER JOIN list_tags t ON t.id = idt.tag_id
             WHERE idt.item_definition_id IN (%s)
             ORDER BY t.name',
            implode(',', $placeholders)
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        $result = [];

        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            $itemId = $row['item_definition_id'];
            $result[$itemId] ??= [];
            $result[$itemId][] = Tag::fromDatabaseRow([
                'id' => $row['id'],
                'list_id' => $row['list_id'],
                'name' => $row['name'],
                'color' => $row['color'],
            ]);
        }

        return $result;
    }
}

