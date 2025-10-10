<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Domain\Game;

use GameItemsList\Domain\Game\Game;
use PHPUnit\Framework\TestCase;

final class GameTest extends TestCase
{
    public function testAccessors(): void
    {
        $game = new Game('game-1', 'Halo');

        self::assertSame('game-1', $game->id());
        self::assertSame('Halo', $game->name());
    }

    public function testFromDatabaseRow(): void
    {
        $game = Game::fromDatabaseRow([
            'id' => 'game-2',
            'name' => 'Portal',
        ]);

        self::assertSame('game-2', $game->id());
        self::assertSame('Portal', $game->name());
    }
}
