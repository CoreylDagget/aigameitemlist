<?php

declare(strict_types=1);

namespace GameItemsList\Infrastructure\Persistence\Lists;

use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\Tag;
use InvalidArgumentException;

use function mb_strtolower;

use PDO;
use PDOException;
use RuntimeException;

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
            $conditions[] = "(
                LOWER(i.name) LIKE :search
                OR LOWER(COALESCE(i.description, '')) LIKE :search
            )";
            $parameters['search'] = '%' . mb_strtolower($search, 'UTF-8') . '%';
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

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === []) {
            return [];
        }

        $itemIds = array_map(static fn (array $row): string => $row['id'], $rows);
        $tags = $this->loadTagsForItems($itemIds);

        $items = [];

        foreach ($rows as $row) {
            $items[] = ItemDefinition::fromDatabaseRow($row, $tags[$row['id']] ?? []);
        }

        return $items;
    }

    public function findByIdForList(string $itemId, string $listId): ?ItemDefinition
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM item_definitions WHERE id = :item_id AND list_id = :list_id LIMIT 1'
        );
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

    public function create(
        string $listId,
        string $name,
        ?string $description,
        ?string $imageUrl,
        string $storageType,
        array $tagIds
    ): ItemDefinition {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO item_definitions (list_id, name, description, image_url, storage_type) '
                . 'VALUES (:list_id, :name, :description, :image_url, :storage_type) '
                . 'RETURNING *'
            );
            $statement->execute([
                'list_id' => $listId,
                'name' => $name,
                'description' => $description,
                'image_url' => $imageUrl,
                'storage_type' => $storageType,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to create item definition', 0, $exception);
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException('Failed to fetch created item definition');
        }

        $itemId = $row['id'];

        $this->syncTags($itemId, $tagIds);

        return $this->findByIdForList($itemId, $listId)
            ?? throw new RuntimeException('Created item definition not found after insert');
    }

    public function update(string $itemId, string $listId, array $changes): ItemDefinition
    {
        $fields = [];
        $parameters = [
            'item_id' => $itemId,
            'list_id' => $listId,
        ];

        if (array_key_exists('name', $changes)) {
            $fields[] = 'name = :name';
            $parameters['name'] = $changes['name'];
        }

        if (array_key_exists('description', $changes)) {
            $fields[] = 'description = :description';
            $parameters['description'] = $changes['description'];
        }

        if (array_key_exists('imageUrl', $changes)) {
            $fields[] = 'image_url = :image_url';
            $parameters['image_url'] = $changes['imageUrl'];
        }

        if (array_key_exists('storageType', $changes)) {
            $fields[] = 'storage_type = :storage_type';
            $parameters['storage_type'] = $changes['storageType'];
        }

        if ($fields !== []) {
            $sql = sprintf(
                'UPDATE item_definitions SET %s WHERE id = :item_id AND list_id = :list_id RETURNING *',
                implode(', ', $fields)
            );

            try {
                $statement = $this->pdo->prepare($sql);
                $statement->execute($parameters);
            } catch (PDOException $exception) {
                throw new RuntimeException('Failed to update item definition', 0, $exception);
            }

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if ($row === false) {
                throw new RuntimeException('Failed to fetch updated item definition');
            }
        }

        if (array_key_exists('tagIds', $changes)) {
            $tagIdsValue = $changes['tagIds'];

            if (!is_array($tagIdsValue)) {
                throw new RuntimeException('tagIds change must be an array of strings.');
            }

            /** @var string[] $tagIds */
            $tagIds = $tagIdsValue;
            $this->syncTags($itemId, $tagIds);
        }

        return $this->findByIdForList($itemId, $listId)
            ?? throw new RuntimeException('Updated item definition not found after update');
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

    /**
     * @param string[] $tagIds
     */
    private function syncTags(string $itemId, array $tagIds): void
    {
        try {
            $deleteStatement = $this->pdo->prepare(
                'DELETE FROM item_definition_tags WHERE item_definition_id = :item_id'
            );
            $deleteStatement->execute(['item_id' => $itemId]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to sync item tags', 0, $exception);
        }

        $tagIds = array_values(array_unique($tagIds));

        if ($tagIds === []) {
            return;
        }

        $placeholders = [];
        $parameters = ['item_id' => $itemId];

        foreach (array_values($tagIds) as $index => $tagId) {
            $placeholder = ':tag_' . $index;
            $placeholders[] = '(:item_id, ' . $placeholder . ')';
            $parameters[$placeholder] = $tagId;
        }

        $sql = 'INSERT INTO item_definition_tags (item_definition_id, tag_id) VALUES '
            . implode(',', $placeholders);

        try {
            $insertStatement = $this->pdo->prepare($sql);
            $insertStatement->execute($parameters);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to attach tags to item definition', 0, $exception);
        }
    }
}
