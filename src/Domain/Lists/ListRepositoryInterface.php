<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

interface ListRepositoryInterface
{
    /**
     * @return GameList[]
     */
    public function findByOwnerAccount(string $accountId): array;

    public function findByIdForOwner(string $listId, string $ownerAccountId): ?GameList;

    public function create(
        string $accountId,
        string $gameId,
        string $name,
        ?string $description,
        bool $isPublished
    ): GameList;
}
