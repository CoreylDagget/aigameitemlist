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
}

