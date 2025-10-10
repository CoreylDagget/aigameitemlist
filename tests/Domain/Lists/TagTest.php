<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Domain\Lists;

use GameItemsList\Domain\Lists\Tag;
use PHPUnit\Framework\TestCase;

final class TagTest extends TestCase
{
    public function testAccessors(): void
    {
        $tag = new Tag('tag-1', 'list-1', 'Primary', '#ffffff');

        self::assertSame('tag-1', $tag->id());
        self::assertSame('list-1', $tag->listId());
        self::assertSame('Primary', $tag->name());
        self::assertSame('#ffffff', $tag->color());
    }

    public function testFromDatabaseRowAllowsMissingColor(): void
    {
        $tag = Tag::fromDatabaseRow([
            'id' => 'tag-2',
            'list_id' => 'list-2',
            'name' => 'Secondary',
            'color' => null,
        ]);

        self::assertSame('tag-2', $tag->id());
        self::assertNull($tag->color());
    }
}
