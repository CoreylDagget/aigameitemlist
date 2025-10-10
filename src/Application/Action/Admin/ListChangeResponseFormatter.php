<?php
declare(strict_types=1);

namespace GameItemsList\Application\Action\Admin;

use GameItemsList\Domain\Lists\ListChange;

trait ListChangeResponseFormatter
{
    /**
     * @return array{
     *     id: string,
     *     listId: string,
     *     actorAccountId: string,
     *     type: string,
     *     payload: array<string, mixed>,
     *     status: string,
     *     reviewedBy: ?string,
     *     reviewedAt: ?string,
     *     createdAt: string
     * }
     */
    private function formatChange(ListChange $change): array
    {
        return [
            'id' => $change->id(),
            'listId' => $change->listId(),
            'actorAccountId' => $change->actorAccountId(),
            'type' => $change->type(),
            'payload' => $change->payload(),
            'status' => $change->status(),
            'reviewedBy' => $change->reviewedBy(),
            'reviewedAt' => $change->reviewedAt()?->format(DATE_ATOM),
            'createdAt' => $change->createdAt()->format(DATE_ATOM),
        ];
    }
}
