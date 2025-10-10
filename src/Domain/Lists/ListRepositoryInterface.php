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

    public function findById(string $listId): ?GameList;

    public function create(
        string $accountId,
        string $gameId,
        string $name,
        ?string $description,
        bool $isPublished
    ): GameList;

    public function publish(string $listId, string $ownerAccountId): ?GameList;

    /**
     * @param array{name?: string, description?: string|null} $changes
     */
    public function updateMetadata(string $listId, array $changes): GameList;
}
