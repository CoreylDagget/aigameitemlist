<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

use DomainException;
use GameItemsList\Domain\Game\GameRepositoryInterface;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

final class ListService
{
    public function __construct(
        private readonly ListRepositoryInterface $lists,
        private readonly GameRepositoryInterface $games,
        private readonly ListChangeRepositoryInterface $listChanges
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
        return $this->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to access this list.');
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

    /**
     * @param array<string, mixed> $changes
     */
    public function proposeMetadataUpdate(string $accountId, string $listId, array $changes): ListChange
    {
        $list = $this->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to modify this list.');

        if (!array_key_exists('name', $changes) && !array_key_exists('description', $changes)) {
            throw new InvalidArgumentException('No changes provided.');
        }

        $payload = [];

        if (array_key_exists('name', $changes)) {
            $name = trim((string) $changes['name']);

            if ($name === '') {
                throw new InvalidArgumentException('Name must not be empty.');
            }

            if ($name !== $list->name()) {
                $payload['name'] = $name;
            }
        }

        if (array_key_exists('description', $changes)) {
            $descriptionValue = $changes['description'];

            if ($descriptionValue !== null && !is_string($descriptionValue)) {
                throw new InvalidArgumentException('Description must be a string or null.');
            }

            $normalizedDescription = $descriptionValue === null ? null : (string) $descriptionValue;

            if ($normalizedDescription !== $list->description()) {
                $payload['description'] = $normalizedDescription;
            }
        }

        if ($payload === []) {
            throw new InvalidArgumentException('No valid changes provided.');
        }

        return $this->listChanges->create(
            $listId,
            $accountId,
            ListChange::TYPE_LIST_METADATA,
            $payload,
        );
    }

    public function publishList(string $accountId, string $listId): GameList
    {
        $list = $this->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to publish this list.');

        if ($list->isPublished()) {
            return $list;
        }

        $updated = $this->lists->publish($listId, $accountId);

        if ($updated === null) {
            throw new RuntimeException('Failed to publish list');
        }

        return $updated;
    }

    public function requireListOwnedByAccount(string $accountId, string $listId, string $unauthorizedMessage): GameList
    {
        $list = $this->lists->findByIdForOwner($listId, $accountId);

        if ($list !== null) {
            return $list;
        }

        $existing = $this->lists->findById($listId);

        if ($existing === null) {
            throw new InvalidArgumentException('List not found');
        }

        throw new DomainException($unauthorizedMessage);
    }
}
