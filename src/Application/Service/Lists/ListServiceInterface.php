<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ListChange;

interface ListServiceInterface
{
    /**
     * @return GameList[]
     */
    public function listsForOwner(string $accountId): array;

    public function getListForOwner(string $accountId, string $listId): GameList;

    public function createList(
        string $accountId,
        string $gameId,
        string $name,
        ?string $description,
        bool $isPublished
    ): GameList;

    /**
     * @param array<string, mixed> $changes
     */
    public function proposeMetadataUpdate(string $accountId, string $listId, array $changes): ListChange;

    public function publishList(string $accountId, string $listId): GameList;

    public function requireListOwnedByAccount(
        string $accountId,
        string $listId,
        string $unauthorizedMessage
    ): GameList;
}
