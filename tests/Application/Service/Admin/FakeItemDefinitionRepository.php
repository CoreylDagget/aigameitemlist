<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Admin;

use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use RuntimeException;

/**
 * @internal
 */
final class FakeItemDefinitionRepository implements ItemDefinitionRepositoryInterface
{
    /**
     * @var array<int, array{
     *     listId: string,
     *     name: string,
     *     description: ?string,
     *     imageUrl: ?string,
     *     storageType: string,
     *     tagIds: string[],
     * }>
     */
    public array $createdItems = [];

    /**
     * @var array<int, array{
     *     itemId: string,
     *     listId: string,
     *     changes: array<string, mixed>,
     * }>
     */
    public array $updatedItems = [];

    public function findByList(
        string $listId,
        ?string $accountId = null,
        ?string $tagId = null,
        ?bool $owned = null,
        ?string $search = null,
    ): array {
        throw new RuntimeException('findByList not implemented in fake.');
    }

    public function findByIdForList(string $itemId, string $listId): ?ItemDefinition
    {
        throw new RuntimeException('findByIdForList not implemented in fake.');
    }

    public function create(
        string $listId,
        string $name,
        ?string $description,
        ?string $imageUrl,
        string $storageType,
        array $tagIds,
    ): ItemDefinition {
        $this->createdItems[] = [
            'listId' => $listId,
            'name' => $name,
            'description' => $description,
            'imageUrl' => $imageUrl,
            'storageType' => $storageType,
            'tagIds' => $tagIds,
        ];

        return new ItemDefinition(
            'item-' . count($this->createdItems),
            $listId,
            $name,
            $description,
            $imageUrl,
            $storageType,
            [],
        );
    }

    public function update(string $itemId, string $listId, array $changes): ItemDefinition
    {
        $this->updatedItems[] = [
            'itemId' => $itemId,
            'listId' => $listId,
            'changes' => $changes,
        ];

        $name = $changes['name'] ?? 'Item';
        $description = $changes['description'] ?? null;
        $imageUrl = $changes['imageUrl'] ?? null;
        $storageType = $changes['storageType'] ?? ItemDefinition::STORAGE_TEXT;

        return new ItemDefinition($itemId, $listId, $name, $description, $imageUrl, $storageType, []);
    }
}
