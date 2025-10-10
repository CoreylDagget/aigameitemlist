<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Lists;

use DomainException;
use GameItemsList\Application\Service\Lists\ListService;
use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Game\GameRepositoryInterface;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ListServiceTest extends TestCase
{
    public function testPublishListPublishesAndReturnsUpdatedList(): void
    {
        $existingList = new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Game Name'),
            'My List',
            null,
            false,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );
        $publishedList = new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Game Name'),
            'My List',
            null,
            true,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findByIdForOwner')
            ->with('list-1', 'account-1')
            ->willReturn($existingList);
        $lists->expects(self::once())
            ->method('publish')
            ->with('list-1', 'account-1')
            ->willReturn($publishedList);

        $games = $this->createStub(GameRepositoryInterface::class);

        $service = new ListService($lists, $games);

        $result = $service->publishList('account-1', 'list-1');

        self::assertSame($publishedList, $result);
    }

    public function testPublishListReturnsExistingWhenAlreadyPublished(): void
    {
        $publishedList = new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Game Name'),
            'My List',
            'Desc',
            true,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findByIdForOwner')
            ->with('list-1', 'account-1')
            ->willReturn($publishedList);
        $lists->expects(self::never())
            ->method('publish');

        $games = $this->createStub(GameRepositoryInterface::class);

        $service = new ListService($lists, $games);

        $result = $service->publishList('account-1', 'list-1');

        self::assertSame($publishedList, $result);
    }

    public function testPublishListThrowsDomainExceptionWhenListOwnedByAnotherAccount(): void
    {
        $otherList = new GameList(
            'list-1',
            'another-account',
            new Game('game-1', 'Game Name'),
            'Their List',
            null,
            false,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findByIdForOwner')
            ->with('list-1', 'account-1')
            ->willReturn(null);
        $lists->expects(self::once())
            ->method('findById')
            ->with('list-1')
            ->willReturn($otherList);
        $lists->expects(self::never())
            ->method('publish');

        $games = $this->createStub(GameRepositoryInterface::class);

        $service = new ListService($lists, $games);

        $this->expectException(DomainException::class);

        $service->publishList('account-1', 'list-1');
    }

    public function testPublishListThrowsInvalidArgumentExceptionWhenListDoesNotExist(): void
    {
        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findByIdForOwner')
            ->with('list-1', 'account-1')
            ->willReturn(null);
        $lists->expects(self::once())
            ->method('findById')
            ->with('list-1')
            ->willReturn(null);
        $lists->expects(self::never())
            ->method('publish');

        $games = $this->createStub(GameRepositoryInterface::class);

        $service = new ListService($lists, $games);

        $this->expectException(InvalidArgumentException::class);

        $service->publishList('account-1', 'list-1');
    }
}
