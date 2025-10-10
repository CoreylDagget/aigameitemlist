<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Lists;

use GameItemsList\Application\Service\Lists\CachedListDetailService;
use GameItemsList\Application\Service\Lists\ListDetailCacheObserverInterface;
use GameItemsList\Application\Service\Lists\ListServiceInterface;
use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\Tag;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use GameItemsList\Tests\Fixtures\InMemoryCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CachedListDetailServiceTest extends TestCase
{
    public function testGetListDetailCachesTagsAndItems(): void
    {
        $list = new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Elden Ring'),
            'My List',
            'Hoard of rare items',
            true,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        /** @var MockObject&ListServiceInterface $listService */
        $listService = $this->createMock(ListServiceInterface::class);
        $listService->expects(self::exactly(2))
            ->method('getListForOwner')
            ->with('account-1', 'list-1')
            ->willReturn($list);

        $tag = new Tag('tag-1', 'list-1', 'Quest', '#FFAA00');
        $item = new ItemDefinition(
            'item-1',
            'list-1',
            'Lantern',
            'Lights the dark caverns',
            null,
            ItemDefinition::STORAGE_BOOLEAN,
            [$tag],
        );

        /** @var MockObject&TagRepositoryInterface $tags */
        $tags = $this->createMock(TagRepositoryInterface::class);
        $tags->expects(self::once())
            ->method('findByList')
            ->with('list-1')
            ->willReturn([$tag]);

        /** @var MockObject&ItemDefinitionRepositoryInterface $items */
        $items = $this->createMock(ItemDefinitionRepositoryInterface::class);
        $items->expects(self::once())
            ->method('findByList')
            ->with('list-1', null, null, null, null)
            ->willReturn([$item]);

        $cache = new InMemoryCache();

        $service = new CachedListDetailService($listService, $tags, $items, $cache);

        $first = $service->getListDetail('account-1', 'list-1');
        $second = $service->getListDetail('account-1', 'list-1');

        self::assertSame($first, $second);
        self::assertSame('My List', $first['name']);
        self::assertCount(1, $first['tags']);
        self::assertCount(1, $first['items']);
        self::assertSame('Quest', $first['tags'][0]['name']);
        self::assertSame('tag-1', $first['items'][0]['tags'][0]['id']);
    }

    public function testInvalidateListDetailClearsCachedEntry(): void
    {
        $list = new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Elden Ring'),
            'My List',
            null,
            false,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        /** @var MockObject&ListServiceInterface $listService */
        $listService = $this->createMock(ListServiceInterface::class);
        $listService->expects(self::exactly(2))
            ->method('getListForOwner')
            ->with('account-1', 'list-1')
            ->willReturn($list);

        $tag = new Tag('tag-1', 'list-1', 'Quest', null);
        $item = new ItemDefinition(
            'item-1',
            'list-1',
            'Lantern',
            null,
            null,
            ItemDefinition::STORAGE_BOOLEAN,
            [$tag],
        );

        /** @var MockObject&TagRepositoryInterface $tags */
        $tags = $this->createMock(TagRepositoryInterface::class);
        $tags->expects(self::exactly(2))
            ->method('findByList')
            ->with('list-1')
            ->willReturn([$tag]);

        /** @var MockObject&ItemDefinitionRepositoryInterface $items */
        $items = $this->createMock(ItemDefinitionRepositoryInterface::class);
        $items->expects(self::exactly(2))
            ->method('findByList')
            ->with('list-1', null, null, null, null)
            ->willReturn([$item]);

        $cache = new InMemoryCache();

        $service = new CachedListDetailService($listService, $tags, $items, $cache);

        $first = $service->getListDetail('account-1', 'list-1');
        self::assertSame('My List', $first['name']);

        $service->invalidateListDetail('account-1', 'list-1');

        $second = $service->getListDetail('account-1', 'list-1');
        self::assertSame('My List', $second['name']);
    }

    public function testObserverReceivesCacheSignals(): void
    {
        $list = new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Elden Ring'),
            'My List',
            'Hoard of rare items',
            true,
            new \DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        /** @var MockObject&ListServiceInterface $listService */
        $listService = $this->createMock(ListServiceInterface::class);
        $listService->expects(self::exactly(2))
            ->method('getListForOwner')
            ->with('account-1', 'list-1')
            ->willReturn($list);

        $tag = new Tag('tag-1', 'list-1', 'Quest', '#FFAA00');
        $item = new ItemDefinition(
            'item-1',
            'list-1',
            'Lantern',
            'Lights the dark caverns',
            null,
            ItemDefinition::STORAGE_BOOLEAN,
            [$tag],
        );

        /** @var MockObject&TagRepositoryInterface $tags */
        $tags = $this->createMock(TagRepositoryInterface::class);
        $tags->expects(self::once())
            ->method('findByList')
            ->with('list-1')
            ->willReturn([$tag]);

        /** @var MockObject&ItemDefinitionRepositoryInterface $items */
        $items = $this->createMock(ItemDefinitionRepositoryInterface::class);
        $items->expects(self::once())
            ->method('findByList')
            ->with('list-1', null, null, null, null)
            ->willReturn([$item]);

        /** @var MockObject&ListDetailCacheObserverInterface $observer */
        $observer = $this->createMock(ListDetailCacheObserverInterface::class);
        $observer->expects(self::once())
            ->method('recordMiss')
            ->with('account-1', 'list-1');
        $observer->expects(self::once())
            ->method('recordStore')
            ->with('account-1', 'list-1', 60);
        $observer->expects(self::once())
            ->method('recordHit')
            ->with('account-1', 'list-1');
        $observer->expects(self::once())
            ->method('recordInvalidate')
            ->with('account-1', 'list-1');

        $cache = new InMemoryCache();

        $service = new CachedListDetailService($listService, $tags, $items, $cache, 60, $observer);

        $service->getListDetail('account-1', 'list-1');
        $service->getListDetail('account-1', 'list-1');
        $service->invalidateListDetail('account-1', 'list-1');
    }
}

