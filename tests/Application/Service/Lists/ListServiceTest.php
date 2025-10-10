<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Lists;

use DomainException;
use GameItemsList\Application\Service\Lists\ListService;
use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Game\GameRepositoryInterface;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
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
        $listChanges = $this->createStub(ListChangeRepositoryInterface::class);

        $service = new ListService($lists, $games, $listChanges);

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
        $listChanges = $this->createStub(ListChangeRepositoryInterface::class);

        $service = new ListService($lists, $games, $listChanges);

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
        $listChanges = $this->createStub(ListChangeRepositoryInterface::class);

        $service = new ListService($lists, $games, $listChanges);

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
        $listChanges = $this->createStub(ListChangeRepositoryInterface::class);

        $service = new ListService($lists, $games, $listChanges);

        $this->expectException(InvalidArgumentException::class);

        $service->publishList('account-1', 'list-1');
    }

    public function testProposeMetadataUpdateCreatesPendingChange(): void
    {
        $existingList = new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Game Name'),
            'Original Name',
            'Original description',
            false,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        $expectedChange = new ListChange(
            'change-1',
            'list-1',
            'account-1',
            ListChange::TYPE_LIST_METADATA,
            ['name' => 'Updated Name'],
            ListChange::STATUS_PENDING,
            new \DateTimeImmutable('2024-01-02T00:00:00Z'),
        );

        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findByIdForOwner')
            ->with('list-1', 'account-1')
            ->willReturn($existingList);

        $listChanges = $this->createMock(ListChangeRepositoryInterface::class);
        $listChanges->expects(self::once())
            ->method('create')
            ->with('list-1', 'account-1', ListChange::TYPE_LIST_METADATA, ['name' => 'Updated Name'])
            ->willReturn($expectedChange);

        $games = $this->createStub(GameRepositoryInterface::class);

        $service = new ListService($lists, $games, $listChanges);

        $result = $service->proposeMetadataUpdate('account-1', 'list-1', ['name' => 'Updated Name']);

        self::assertSame($expectedChange, $result);
    }

    public function testProposeMetadataUpdateThrowsWhenListNotFound(): void
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

        $listChanges = $this->createStub(ListChangeRepositoryInterface::class);
        $games = $this->createStub(GameRepositoryInterface::class);

        $service = new ListService($lists, $games, $listChanges);

        $this->expectException(InvalidArgumentException::class);

        $service->proposeMetadataUpdate('account-1', 'list-1', ['name' => 'Updated Name']);
    }

    public function testProposeMetadataUpdateThrowsWhenListOwnedByAnotherAccount(): void
    {
        $otherList = new GameList(
            'list-1',
            'someone-else',
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

        $listChanges = $this->createStub(ListChangeRepositoryInterface::class);
        $games = $this->createStub(GameRepositoryInterface::class);

        $service = new ListService($lists, $games, $listChanges);

        $this->expectException(DomainException::class);

        $service->proposeMetadataUpdate('account-1', 'list-1', ['name' => 'Updated Name']);
    }

    public function testProposeMetadataUpdateThrowsWhenNoChangesProvided(): void
    {
        $existingList = new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Game Name'),
            'Original Name',
            null,
            false,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findByIdForOwner')
            ->with('list-1', 'account-1')
            ->willReturn($existingList);

        $listChanges = $this->createStub(ListChangeRepositoryInterface::class);
        $games = $this->createStub(GameRepositoryInterface::class);

        $service = new ListService($lists, $games, $listChanges);

        $this->expectException(InvalidArgumentException::class);

        $service->proposeMetadataUpdate('account-1', 'list-1', []);
    }

    public function testProposeMetadataUpdateThrowsWhenValuesDoNotChange(): void
    {
        $existingList = new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Game Name'),
            'Original Name',
            'Original description',
            false,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findByIdForOwner')
            ->with('list-1', 'account-1')
            ->willReturn($existingList);

        $listChanges = $this->createMock(ListChangeRepositoryInterface::class);
        $listChanges->expects(self::never())->method('create');

        $games = $this->createStub(GameRepositoryInterface::class);

        $service = new ListService($lists, $games, $listChanges);

        $this->expectException(InvalidArgumentException::class);

        $service->proposeMetadataUpdate('account-1', 'list-1', [
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);
    }
}
