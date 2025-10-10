<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

interface TagRepositoryInterface
{
    /**
     * @return Tag[]
     */
    public function findByList(string $listId): array;

    /**
     * @param string[] $tagIds
     * @return Tag[]
     */
    public function findByIds(string $listId, array $tagIds): array;
}

