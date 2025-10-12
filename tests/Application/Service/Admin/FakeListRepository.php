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

    /**
     * @var array<int, array{
     *     listId: string,
     *     changes: array<string, mixed>,
     * }>
     */
    public array $metadataUpdates = [];

    public function findByOwnerAccount(string $accountId): array
    {
        throw new RuntimeException('findForAccount not implemented in fake.');
    }

    public function findById(string $listId): ?GameList
    {
        return $this->list;
    }

    public function findByIdForOwner(string $listId, string $ownerAccountId): ?GameList
    {
        return $this->list;
    }

    /**
     * @param string[] $listIds
     * @return GameList[]
     */
    public function findByIds(string $accountId, array $listIds): array
    {
        throw new RuntimeException('findByIds not implemented in fake.');
    }

    public function findBySlug(string $slug): ?GameList
    {
        throw new RuntimeException('findBySlug not implemented in fake.');
    }

    public function create(
        string $accountId,
        string $gameId,
        string $name,
        ?string $description,
        bool $isPublished
    ): GameList {
        throw new RuntimeException('create not implemented in fake.');
    }

    public function publish(string $listId, string $ownerAccountId): ?GameList
    {
        throw new RuntimeException('publish not implemented in fake.');
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
