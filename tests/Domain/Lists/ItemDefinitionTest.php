<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Domain\Lists;

use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\Tag;
use PHPUnit\Framework\TestCase;

final class ItemDefinitionTest extends TestCase
{
    public function testAccessorsExposeData(): void
    {
        $tags = [new Tag('tag-1', 'list-1', 'Primary', '#ffffff')];
        $item = new ItemDefinition(
            'item-1',
            'list-1',
            'Potion',
            'Restores health',
            'https://example.com/potion.png',
            ItemDefinition::STORAGE_COUNT,
            $tags,
        );

        self::assertSame('item-1', $item->id());
        self::assertSame('list-1', $item->listId());
        self::assertSame('Potion', $item->name());
        self::assertSame('Restores health', $item->description());
        self::assertSame('https://example.com/potion.png', $item->imageUrl());
        self::assertSame(ItemDefinition::STORAGE_COUNT, $item->storageType());
        self::assertSame($tags, $item->tags());
    }

    public function testFromDatabaseRowHydratesOptionalFields(): void
    {
        $tag = new Tag('tag-2', 'list-1', 'Secondary', null);
        $item = ItemDefinition::fromDatabaseRow([
            'id' => 'item-2',
            'list_id' => 'list-1',
            'name' => 'Elixir',
            'description' => null,
            'image_url' => null,
            'storage_type' => ItemDefinition::STORAGE_TEXT,
        ], [$tag]);

        self::assertSame('item-2', $item->id());
        self::assertSame('list-1', $item->listId());
        self::assertNull($item->description());
        self::assertNull($item->imageUrl());
        self::assertSame(ItemDefinition::STORAGE_TEXT, $item->storageType());
        self::assertSame([$tag], $item->tags());
    }
}
