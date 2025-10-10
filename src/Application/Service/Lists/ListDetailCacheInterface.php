<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

interface ListDetailCacheInterface
{
    /**
     * @return array{
     *     id: string,
     *     ownerAccountId: string,
     *     game: array{id: string, name: string},
     *     name: string,
     *     description: ?string,
     *     isPublished: bool,
     *     createdAt: string,
     *     tags: array<int, array{id: string, listId: string, name: string, color: ?string}>,
     *     items: array<int, array{
     *         id: string,
     *         listId: string,
     *         name: string,
     *         description: ?string,
     *         imageUrl: ?string,
     *         storageType: string,
     *         tags: array<int, array{id: string, listId: string, name: string, color: ?string}>
     *     }>
     * }
     */
    public function getListDetail(string $accountId, string $listId): array;

    public function invalidateListDetail(string $accountId, string $listId): void;
}

