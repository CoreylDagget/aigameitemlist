<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Admin;

use GameItemsList\Domain\Lists\Tag;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use RuntimeException;

/**
 * @internal
 */
final class FakeTagRepository implements TagRepositoryInterface
{
    /** @var array<int, array{listId: string, name: string, color: ?string}> */
    public array $createdTags = [];

    public function findByList(string $listId): array
    {
        throw new RuntimeException('findByList not implemented in fake.');
    }

    public function findByIds(string $listId, array $tagIds): array
    {
        throw new RuntimeException('findByIds not implemented in fake.');
    }

    public function create(string $listId, string $name, ?string $color): Tag
    {
        $this->createdTags[] = [
            'listId' => $listId,
            'name' => $name,
            'color' => $color,
        ];

        return new Tag('tag-' . count($this->createdTags), $listId, $name, $color);
    }
}
