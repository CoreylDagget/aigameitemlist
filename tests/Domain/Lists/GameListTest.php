<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Domain\Lists;

use DateTimeImmutable;
use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Lists\GameList;
use PHPUnit\Framework\TestCase;

final class GameListTest extends TestCase
{
    public function testAccessorsExposeState(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $game = new Game('game-1', 'Halo');
        $list = new GameList('list-1', 'owner-1', $game, 'Favorites', 'Top picks', true, $createdAt);

        self::assertSame('list-1', $list->id());
        self::assertSame('owner-1', $list->ownerAccountId());
        self::assertSame($game, $list->game());
        self::assertSame('Favorites', $list->name());
        self::assertSame('Top picks', $list->description());
        self::assertTrue($list->isPublished());
        self::assertSame($createdAt, $list->createdAt());
    }
}
