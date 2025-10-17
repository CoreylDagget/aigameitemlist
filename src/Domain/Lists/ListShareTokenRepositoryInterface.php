<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

interface ListShareTokenRepositoryInterface
{
    public function findActiveByList(string $listId): ?ListShareToken;

    public function create(string $listId, string $token): ListShareToken;

    public function revokeAllForList(string $listId): void;

    public function findByToken(string $token): ?ListShareToken;
}
