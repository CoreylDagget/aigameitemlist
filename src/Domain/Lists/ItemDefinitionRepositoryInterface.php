<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

interface ItemDefinitionRepositoryInterface
{
    /**
     * @return ItemDefinition[]
     */
    public function findByList(
        string $listId,
        ?string $accountId = null,
        ?string $tagId = null,
        ?bool $owned = null,
        ?string $search = null,
    ): array;

    public function findByIdForList(string $itemId, string $listId): ?ItemDefinition;

    /**
     * @param string[] $tagIds
     */
    public function create(
        string $listId,
        string $name,
        ?string $description,
        ?string $imageUrl,
        string $storageType,
        array $tagIds
    ): ItemDefinition;

    /**
     * @param array{
     *     name?: string,
     *     description?: string|null,
     *     imageUrl?: string|null,
     *     storageType?: string,
     *     tagIds?: string[],
     * } $changes
     */
    public function update(string $itemId, string $listId, array $changes): ItemDefinition;
}
