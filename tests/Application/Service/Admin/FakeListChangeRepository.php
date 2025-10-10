<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Admin;

use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
use RuntimeException;

/**
 * @internal
 */
final class FakeListChangeRepository implements ListChangeRepositoryInterface
{
    /** @var array<int, array{changeId: string, reviewerId: string}> */
    public array $markApprovedCalls = [];

    /** @var array<int, array{changeId: string, reviewerId: string}> */
    public array $markRejectedCalls = [];

    /** @var array<int, string|null> */
    public array $findByStatusCalls = [];

    /** @var array<int, string> */
    public array $findPendingCalls = [];

    /** @var ListChange[]|null */
    public ?array $findByStatusResult = null;

    public ?ListChange $pendingChange = null;

    public ?ListChange $approvedChange = null;

    public ?ListChange $rejectedChange = null;

    public function create(string $listId, string $actorAccountId, string $type, array $payload): ListChange
    {
        throw new RuntimeException('create not implemented in fake.');
    }

    public function findByStatus(?string $status = null): array
    {
        $this->findByStatusCalls[] = $status;

        return $this->findByStatusResult ?? [];
    }

    public function findById(string $changeId): ?ListChange
    {
        return null;
    }

    public function findPendingByIdForUpdate(string $changeId): ?ListChange
    {
        $this->findPendingCalls[] = $changeId;

        return $this->pendingChange;
    }

    public function markApproved(string $changeId, string $reviewerAccountId): ListChange
    {
        $this->markApprovedCalls[] = [
            'changeId' => $changeId,
            'reviewerId' => $reviewerAccountId,
        ];

        if ($this->approvedChange === null) {
            throw new RuntimeException('approvedChange not configured.');
        }

        return $this->approvedChange;
    }

    public function markRejected(string $changeId, string $reviewerAccountId): ListChange
    {
        $this->markRejectedCalls[] = [
            'changeId' => $changeId,
            'reviewerId' => $reviewerAccountId,
        ];

        if ($this->rejectedChange === null) {
            throw new RuntimeException('rejectedChange not configured.');
        }

        return $this->rejectedChange;
    }
}
