<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

use GameItemsList\Domain\Game\GameRepositoryInterface;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use InvalidArgumentException;

final class ListService
{
    public function __construct(
        private readonly ListRepositoryInterface $lists,
        private readonly GameRepositoryInterface $games
    ) {
    }

    /**
     * @return GameList[]
     */
    public function listsForOwner(string $accountId): array
    {
        return $this->lists->findByOwnerAccount($accountId);
    }

    public function getListForOwner(string $accountId, string $listId): GameList
    {
        $list = $this->lists->findByIdForOwner($listId, $accountId);

        if ($list === null) {
            throw new InvalidArgumentException('List not found');
        }

        return $list;
    }

    public function createList(
        string $accountId,
        string $gameId,
        string $name,
        ?string $description,
        bool $isPublished
    ): GameList {
        $game = $this->games->findById($gameId);

        if ($game === null) {
            throw new InvalidArgumentException('Game not found');
        }

        return $this->lists->create($accountId, $gameId, $name, $description, $isPublished);
    }
}
