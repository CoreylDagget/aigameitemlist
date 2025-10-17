<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\Tag;

final class ListDetailFormatter
{
    /**
     * @param Tag[] $tags
     * @return array<int, array{id: string, listId: string, name: string, color: ?string}>
     */
    public function formatTags(array $tags): array
    {
        return array_map(
            static fn (Tag $tag): array => [
                'id' => $tag->id(),
                'listId' => $tag->listId(),
                'name' => $tag->name(),
                'color' => $tag->color(),
            ],
            $tags
        );
    }

    /**
     * @param ItemDefinition[] $items
     * @return array<int, array{
     *     id: string,
     *     listId: string,
     *     name: string,
     *     description: ?string,
     *     imageUrl: ?string,
     *     storageType: string,
     *     tags: array<int, array{id: string, listId: string, name: string, color: ?string}>
     * }>
     */
    public function formatItems(array $items): array
    {
        return array_map(
            function (ItemDefinition $item): array {
                return [
                    'id' => $item->id(),
                    'listId' => $item->listId(),
                    'name' => $item->name(),
                    'description' => $item->description(),
                    'imageUrl' => $item->imageUrl(),
                    'storageType' => $item->storageType(),
                    'tags' => $this->formatTags($item->tags()),
                ];
            },
            $items
        );
    }
}
