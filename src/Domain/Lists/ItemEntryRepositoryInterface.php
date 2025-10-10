<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

interface ItemEntryRepositoryInterface
{
    /**
     * @return ItemEntry[]
     */
    public function findByListAndAccount(string $listId, string $accountId): array;

    public function upsert(
        string $listId,
        string $itemId,
        string $accountId,
        bool|int|string $value,
        string $storageType,
    ): ItemEntry;
}

