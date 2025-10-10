<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

use GameItemsList\Application\Cache\CacheInterface;
use GameItemsList\Domain\Lists\ItemDefinition;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\Tag;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use JsonException;
use Throwable;

final class CachedListDetailService implements ListDetailCacheInterface
{
    private const CACHE_PREFIX = 'gil:list-detail:v1:';
    private const DEFAULT_TTL = 60;

    private ListDetailCacheObserverInterface $observer;

    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly TagRepositoryInterface $tags,
        private readonly ItemDefinitionRepositoryInterface $items,
        private readonly CacheInterface $cache,
        private readonly int $ttlSeconds = self::DEFAULT_TTL,
        ?ListDetailCacheObserverInterface $observer = null,
    ) {
        $this->observer = $observer ?? new NullListDetailCacheObserver();
    }

    /**
     * @return array{
     *     id: string,
     *     ownerAccountId: string,
     *     game: array{id: string, name: string},
     *     name: string,
     *     description: ?string,
     *     isPublished: bool,
     *     createdAt: string,
     *     tags: array<int, array{id: string, listId: string, name: string, color: ?string}>,
     *     items: array<int, array{
     *         id: string,
     *         listId: string,
     *         name: string,
     *         description: ?string,
     *         imageUrl: ?string,
     *         storageType: string,
     *         tags: array<int, array{id: string, listId: string, name: string, color: ?string}>
     *     }>
     * }
     */
    public function getListDetail(string $accountId, string $listId): array
    {
        $list = $this->listService->getListForOwner($accountId, $listId);

        $cacheKey = $this->cacheKey($accountId, $listId);
        $cachedPayload = $this->loadFromCache($cacheKey);

        if ($cachedPayload === null) {
            $this->observer->recordMiss($accountId, $listId);

            $tags = $this->tags->findByList($listId);
            $items = $this->items->findByList($listId);

            $payload = [
                'tags' => $this->formatTags($tags),
                'items' => $this->formatItems($items),
            ];

            if ($this->storeInCache($cacheKey, $payload)) {
                $this->observer->recordStore($accountId, $listId, $this->ttlSeconds);
            }
        } else {
            $this->observer->recordHit($accountId, $listId);

            $payload = [
                'tags' => is_array($cachedPayload['tags'] ?? null) ? $cachedPayload['tags'] : [],
                'items' => is_array($cachedPayload['items'] ?? null) ? $cachedPayload['items'] : [],
            ];
        }

        return [
            'id' => $list->id(),
            'ownerAccountId' => $list->ownerAccountId(),
            'game' => [
                'id' => $list->game()->id(),
                'name' => $list->game()->name(),
            ],
            'name' => $list->name(),
            'description' => $list->description(),
            'isPublished' => $list->isPublished(),
            'createdAt' => $list->createdAt()->format(DATE_ATOM),
            'tags' => $payload['tags'],
            'items' => $payload['items'],
        ];
    }

    public function invalidateListDetail(string $accountId, string $listId): void
    {
        $cacheKey = $this->cacheKey($accountId, $listId);

        try {
            $this->cache->delete($cacheKey);
        } catch (Throwable) {
            // Ignore cache failures; fall back to cold fetches.
        }

        $this->observer->recordInvalidate($accountId, $listId);
    }

    /**
     * @param Tag[] $tags
     * @return array<int, array{id: string, listId: string, name: string, color: ?string}>
     */
    private function formatTags(array $tags): array
    {
        return array_map(
            static fn(Tag $tag): array => [
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
    private function formatItems(array $items): array
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

    private function cacheKey(string $accountId, string $listId): string
    {
        return self::CACHE_PREFIX . $accountId . ':' . $listId;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadFromCache(string $key): ?array
    {
        try {
            $cached = $this->cache->get($key);
        } catch (Throwable) {
            return null;
        }

        if ($cached === null) {
            return null;
        }

        try {
            $decoded = json_decode($cached, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function storeInCache(string $key, array $payload): bool
    {
        if ($this->ttlSeconds <= 0) {
            return false;
        }

        try {
            $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
            $this->cache->set($key, $encoded, $this->ttlSeconds);
        } catch (Throwable) {
            // Ignore cache failures; fall back to cold fetches.

            return false;
        }

        return true;
    }
}

