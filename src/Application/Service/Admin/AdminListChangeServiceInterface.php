<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Admin;

use GameItemsList\Domain\Lists\ListChange;

/**
 * @psalm-type ListChangeStatus = ListChange::STATUS_PENDING|ListChange::STATUS_APPROVED|ListChange::STATUS_REJECTED
 */
interface AdminListChangeServiceInterface
{
    /**
     * @return ListChange[]
     */
    public function listChanges(?string $status): array;

    public function approveChange(string $changeId, string $reviewerAccountId): ListChange;

    public function rejectChange(string $changeId, string $reviewerAccountId): ListChange;
}
