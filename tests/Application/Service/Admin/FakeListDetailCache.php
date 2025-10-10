<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Admin;

use GameItemsList\Application\Service\Lists\ListDetailCacheInterface;
use RuntimeException;

/**
 * @internal
 */
final class FakeListDetailCache implements ListDetailCacheInterface
{
    /** @var array<int, array{accountId: string, listId: string}> */
    public array $invalidations = [];

    public function getListDetail(string $accountId, string $listId): array
    {
        throw new RuntimeException('getListDetail not implemented in fake.');
    }

    public function invalidateListDetail(string $accountId, string $listId): void
    {
        $this->invalidations[] = [
            'accountId' => $accountId,
            'listId' => $listId,
        ];
    }
}
