<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Application\Service\Lists;

use DateTimeImmutable;
use DomainException;
use GameItemsList\Application\Service\Lists\ListDetailFormatter;
use GameItemsList\Application\Service\Lists\ListServiceInterface;
use GameItemsList\Application\Service\Lists\ListShareService;
use GameItemsList\Domain\Game\Game;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use GameItemsList\Domain\Lists\ListShareToken;
use GameItemsList\Domain\Lists\ListShareTokenRepositoryInterface;
use GameItemsList\Domain\Lists\Tag;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ListShareServiceTest extends TestCase
{
    public function testGetShareForListRequiresOwnershipAndReturnsActiveShare(): void
    {
        $listService = $this->createMock(ListServiceInterface::class);
        $listService->expects(self::once())
            ->method('requireListOwnedByAccount')
            ->with('account-1', 'list-1', 'You are not allowed to access this list.')
            ->willReturn($this->createStub(GameList::class));

        $share = new ListShareToken(
            'share-1',
            'list-1',
            'token-1',
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
            null,
        );

        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('findActiveByList')
            ->with('list-1')
            ->willReturn($share);

        $service = $this->createService(
            listService: $listService,
            shareTokens: $shareTokens,
        );

        $result = $service->getShareForList('account-1', 'list-1');

        self::assertSame($share, $result);
    }

    public function testShareListReturnsExistingShareWhenRotateDisabled(): void
    {
        $list = $this->createPublishedList();

        $listService = $this->createMock(ListServiceInterface::class);
        $listService->expects(self::once())
            ->method('publishList')
            ->with('account-1', 'list-1')
            ->willReturn($list);

        $existingShare = new ListShareToken(
            'share-1',
            'list-1',
            'token-1',
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
            null,
        );

        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('findActiveByList')
            ->with('list-1')
            ->willReturn($existingShare);
        $shareTokens->expects(self::never())
            ->method('revokeAllForList');
        $shareTokens->expects(self::never())
            ->method('create');

        $service = $this->createService(
            listService: $listService,
            shareTokens: $shareTokens,
        );

        $result = $service->shareList('account-1', 'list-1', rotate: false);

        self::assertSame($existingShare, $result);
    }

    public function testShareListRotatesExistingTokenWhenRequested(): void
    {
        $list = $this->createPublishedList();

        $listService = $this->createMock(ListServiceInterface::class);
        $listService->expects(self::once())
            ->method('publishList')
            ->with('account-1', 'list-1')
            ->willReturn($list);

        $existingShare = new ListShareToken(
            'share-1',
            'list-1',
            'token-1',
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
            null,
        );

        $newShare = new ListShareToken(
            'share-2',
            'list-1',
            'token-2',
            new DateTimeImmutable('2024-02-01T00:00:00Z'),
            null,
        );

        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('findActiveByList')
            ->with('list-1')
            ->willReturn($existingShare);
        $shareTokens->expects(self::once())
            ->method('revokeAllForList')
            ->with('list-1');
        $shareTokens->expects(self::once())
            ->method('create')
            ->with(
                'list-1',
                self::callback(function (string $token): bool {
                    return \strlen($token) === 32 && ctype_xdigit($token);
                })
            )
            ->willReturn($newShare);

        $service = $this->createService(
            listService: $listService,
            shareTokens: $shareTokens,
        );

        $result = $service->shareList('account-1', 'list-1', rotate: true);

        self::assertSame($newShare, $result);
    }

    public function testShareListCreatesNewTokenWhenNoneExists(): void
    {
        $list = $this->createPublishedList();

        $listService = $this->createMock(ListServiceInterface::class);
        $listService->expects(self::once())
            ->method('publishList')
            ->with('account-1', 'list-1')
            ->willReturn($list);

        $newShare = new ListShareToken(
            'share-1',
            'list-1',
            'token-1',
            new DateTimeImmutable('2024-02-01T00:00:00Z'),
            null,
        );

        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('findActiveByList')
            ->with('list-1')
            ->willReturn(null);
        $shareTokens->expects(self::never())
            ->method('revokeAllForList');
        $shareTokens->expects(self::once())
            ->method('create')
            ->with(
                'list-1',
                self::callback(function (string $token): bool {
                    return \strlen($token) === 32 && ctype_xdigit($token);
                })
            )
            ->willReturn($newShare);

        $service = $this->createService(
            listService: $listService,
            shareTokens: $shareTokens,
        );

        $result = $service->shareList('account-1', 'list-1', rotate: false);

        self::assertSame($newShare, $result);
    }

    public function testRevokeShareRequiresOwnershipAndRevokesAllTokens(): void
    {
        $listService = $this->createMock(ListServiceInterface::class);
        $listService->expects(self::once())
            ->method('requireListOwnedByAccount')
            ->with('account-1', 'list-1', 'You are not allowed to modify this list.')
            ->willReturn($this->createStub(GameList::class));

        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('revokeAllForList')
            ->with('list-1');

        $service = $this->createService(
            listService: $listService,
            shareTokens: $shareTokens,
        );

        $service->revokeShare('account-1', 'list-1');
    }

    public function testGetSharedListReturnsFormattedDetailForActiveShare(): void
    {
        $share = new ListShareToken(
            'share-1',
            'list-1',
            'token-1',
            new DateTimeImmutable('2024-02-01T00:00:00Z'),
            null,
        );

        $game = new Game('game-1', 'Game Name');
        $list = new GameList(
            'list-1',
            'owner-1',
            $game,
            'Shared List',
            'Description',
            true,
            new DateTimeImmutable('2024-02-01T00:00:00Z'),
        );

        $tag = new Tag('tag-1', 'list-1', 'Favorites', '#ff0000');
        $item = new ItemDefinition(
            'item-1',
            'list-1',
            'Sword',
            'Sharp blade',
            null,
            ItemDefinition::STORAGE_BOOLEAN,
            [$tag],
        );

        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('findByToken')
            ->with('token-1')
            ->willReturn($share);

        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findById')
            ->with('list-1')
            ->willReturn($list);

        $tags = $this->createMock(TagRepositoryInterface::class);
        $tags->expects(self::once())
            ->method('findByList')
            ->with('list-1')
            ->willReturn([$tag]);

        $items = $this->createMock(ItemDefinitionRepositoryInterface::class);
        $items->expects(self::once())
            ->method('findByList')
            ->with('list-1')
            ->willReturn([$item]);

        $service = $this->createService(
            shareTokens: $shareTokens,
            lists: $lists,
            tags: $tags,
            items: $items,
        );

        $result = $service->getSharedList('token-1');

        self::assertSame('list-1', $result['id']);
        self::assertSame('owner-1', $result['ownerAccountId']);
        self::assertSame(
            [
                'id' => 'game-1',
                'name' => 'Game Name',
            ],
            $result['game']
        );
        self::assertSame('Shared List', $result['name']);
        self::assertSame('Description', $result['description']);
        self::assertTrue($result['isPublished']);
        self::assertSame([$this->formatTag($tag)], $result['tags']);
        self::assertSame([
            [
                'id' => 'item-1',
                'listId' => 'list-1',
                'name' => 'Sword',
                'description' => 'Sharp blade',
                'imageUrl' => null,
                'storageType' => ItemDefinition::STORAGE_BOOLEAN,
                'tags' => [$this->formatTag($tag)],
            ],
        ], $result['items']);
    }

    public function testGetSharedListThrowsWhenTokenMissing(): void
    {
        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('findByToken')
            ->with('token-1')
            ->willReturn(null);

        $service = $this->createService(
            shareTokens: $shareTokens,
        );

        $this->expectException(InvalidArgumentException::class);
        $service->getSharedList('token-1');
    }

    public function testGetSharedListThrowsWhenTokenRevoked(): void
    {
        $inactiveShare = new ListShareToken(
            'share-1',
            'list-1',
            'token-1',
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
            new DateTimeImmutable('2024-01-02T00:00:00Z'),
        );

        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('findByToken')
            ->with('token-1')
            ->willReturn($inactiveShare);

        $service = $this->createService(
            shareTokens: $shareTokens,
        );

        $this->expectException(InvalidArgumentException::class);
        $service->getSharedList('token-1');
    }

    public function testGetSharedListThrowsWhenListNotFound(): void
    {
        $share = new ListShareToken(
            'share-1',
            'list-1',
            'token-1',
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
            null,
        );

        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('findByToken')
            ->with('token-1')
            ->willReturn($share);

        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findById')
            ->with('list-1')
            ->willReturn(null);

        $service = $this->createService(
            shareTokens: $shareTokens,
            lists: $lists,
        );

        $this->expectException(InvalidArgumentException::class);
        $service->getSharedList('token-1');
    }

    public function testGetSharedListThrowsWhenListNotPublished(): void
    {
        $share = new ListShareToken(
            'share-1',
            'list-1',
            'token-1',
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
            null,
        );

        $game = new Game('game-1', 'Game Name');
        $list = new GameList(
            'list-1',
            'owner-1',
            $game,
            'Private List',
            null,
            false,
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
        );

        $shareTokens = $this->createMock(ListShareTokenRepositoryInterface::class);
        $shareTokens->expects(self::once())
            ->method('findByToken')
            ->with('token-1')
            ->willReturn($share);

        $lists = $this->createMock(ListRepositoryInterface::class);
        $lists->expects(self::once())
            ->method('findById')
            ->with('list-1')
            ->willReturn($list);

        $service = $this->createService(
            shareTokens: $shareTokens,
            lists: $lists,
        );

        $this->expectException(DomainException::class);
        $service->getSharedList('token-1');
    }

    private function createService(
        ?ListServiceInterface $listService = null,
        ?ListShareTokenRepositoryInterface $shareTokens = null,
        ?ListRepositoryInterface $lists = null,
        ?TagRepositoryInterface $tags = null,
        ?ItemDefinitionRepositoryInterface $items = null,
        ?ListDetailFormatter $formatter = null,
    ): ListShareService {
        return new ListShareService(
            $listService ?? $this->createStub(ListServiceInterface::class),
            $shareTokens ?? $this->createStub(ListShareTokenRepositoryInterface::class),
            $lists ?? $this->createStub(ListRepositoryInterface::class),
            $tags ?? $this->createStub(TagRepositoryInterface::class),
            $items ?? $this->createStub(ItemDefinitionRepositoryInterface::class),
            $formatter ?? new ListDetailFormatter(),
        );
    }

    private function createPublishedList(): GameList
    {
        return new GameList(
            'list-1',
            'account-1',
            new Game('game-1', 'Game Name'),
            'My List',
            'Description',
            true,
            new DateTimeImmutable('2024-01-01T00:00:00Z'),
        );
    }

    private function formatTag(Tag $tag): array
    {
        return [
            'id' => $tag->id(),
            'listId' => $tag->listId(),
            'name' => $tag->name(),
            'color' => $tag->color(),
        ];
    }
}
