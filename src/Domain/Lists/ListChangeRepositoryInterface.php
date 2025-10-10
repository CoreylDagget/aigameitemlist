<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

interface ListChangeRepositoryInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(string $listId, string $actorAccountId, string $type, array $payload): ListChange;
}

