<?php
declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

interface ListChangeRepositoryInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(string $listId, string $actorAccountId, string $type, array $payload): ListChange;

    /**
     * @return ListChange[]
     */
    public function findByStatus(?string $status = null): array;

    public function findById(string $changeId): ?ListChange;

    public function findPendingByIdForUpdate(string $changeId): ?ListChange;

    public function markApproved(string $changeId, string $reviewerAccountId): ListChange;

    public function markRejected(string $changeId, string $reviewerAccountId): ListChange;
}
