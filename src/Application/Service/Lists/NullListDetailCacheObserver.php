<?php
declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

final class NullListDetailCacheObserver implements ListDetailCacheObserverInterface
{
    public function recordHit(string $accountId, string $listId): void
    {
    }

    public function recordMiss(string $accountId, string $listId): void
    {
    }

    public function recordStore(string $accountId, string $listId, int $ttl): void
    {
    }

    public function recordInvalidate(string $accountId, string $listId): void
    {
    }
}
