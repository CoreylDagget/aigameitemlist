<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

use DomainException;
use GameItemsList\Domain\Lists\GameList;
use GameItemsList\Domain\Lists\ItemDefinitionRepositoryInterface;
use GameItemsList\Domain\Lists\ListRepositoryInterface;
use GameItemsList\Domain\Lists\ListShareToken;
use GameItemsList\Domain\Lists\ListShareTokenRepositoryInterface;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use InvalidArgumentException;

final class ListShareService
{
    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly ListShareTokenRepositoryInterface $shareTokens,
        private readonly ListRepositoryInterface $lists,
        private readonly TagRepositoryInterface $tags,
        private readonly ItemDefinitionRepositoryInterface $items,
        private readonly ListDetailFormatter $formatter,
    ) {
    }

    public function getShareForList(string $accountId, string $listId): ?ListShareToken
    {
        $this->listService->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to access this list.');

        return $this->shareTokens->findActiveByList($listId);
    }

    public function shareList(string $accountId, string $listId, bool $rotate): ListShareToken
    {
        $list = $this->listService->publishList($accountId, $listId);

        $existing = $this->shareTokens->findActiveByList($listId);

        if ($existing !== null && !$rotate) {
            return $existing;
        }

        if ($existing !== null) {
            $this->shareTokens->revokeAllForList($listId);
        }

        $token = bin2hex(random_bytes(16));

        return $this->shareTokens->create($listId, $token);
    }

    public function revokeShare(string $accountId, string $listId): void
    {
        $this->listService->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to modify this list.');

        $this->shareTokens->revokeAllForList($listId);
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
    public function getSharedList(string $token): array
    {
        $shareToken = $this->shareTokens->findByToken($token);

        if ($shareToken === null || !$shareToken->isActive()) {
            throw new InvalidArgumentException('Share link not found.');
        }

        $list = $this->lists->findById($shareToken->listId());

        if ($list === null) {
            throw new InvalidArgumentException('List not found for share link.');
        }

        if (!$list->isPublished()) {
            throw new DomainException('This list is no longer shared.');
        }

        return $this->buildDetail($list);
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
    private function buildDetail(GameList $list): array
    {
        $tags = $this->tags->findByList($list->id());
        $items = $this->items->findByList($list->id());

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
            'tags' => $this->formatter->formatTags($tags),
            'items' => $this->formatter->formatItems($items),
        ];
    }
}
