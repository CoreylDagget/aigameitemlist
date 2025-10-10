<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Admin;

use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use RuntimeException;

/**
 * @internal
 */
final class FakeListRepository implements ListRepositoryInterface
{
    public ?GameList $list = null;

    /** @var array<int, array{listId: string, changes: array}> */
    public array $metadataUpdates = [];

    public function findForAccount(string $accountId): array
    {
        throw new RuntimeException('findForAccount not implemented in fake.');
    }

    public function findById(string $listId): ?GameList
    {
        return $this->list;
    }

    public function findByIdWithOwner(string $listId): ?GameList
    {
        return $this->list;
    }

    public function findByIds(string $accountId, array $listIds): array
    {
        throw new RuntimeException('findByIds not implemented in fake.');
    }

    public function findBySlug(string $slug): ?GameList
    {
        throw new RuntimeException('findBySlug not implemented in fake.');
    }

    public function create(string $accountId, string $gameId, string $name, ?string $description): GameList
    {
        throw new RuntimeException('create not implemented in fake.');
    }

    public function updateMetadata(string $listId, array $changes): GameList
    {
        $this->metadataUpdates[] = [
            'listId' => $listId,
            'changes' => $changes,
        ];

        if ($this->list === null) {
            throw new RuntimeException('list not configured.');
        }

        return $this->list;
    }
}
