<?php

declare(strict_types=1);

namespace GameItemsList\Application\Service\Lists;

use GameItemsList\Domain\Lists\ListChange;
use GameItemsList\Domain\Lists\ListChangeRepositoryInterface;
use GameItemsList\Domain\Lists\Tag;
use GameItemsList\Domain\Lists\TagRepositoryInterface;
use InvalidArgumentException;

final class TagService
{
    public function __construct(
        private readonly ListServiceInterface $listService,
        private readonly TagRepositoryInterface $tags,
        private readonly ListChangeRepositoryInterface $listChanges,
    ) {
    }

    /**
     * @return Tag[]
     */
    public function listTags(string $accountId, string $listId): array
    {
        $this->listService->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to view this list.');

        return $this->tags->findByList($listId);
    }

    public function proposeCreateTag(string $accountId, string $listId, string $name, ?string $color): ListChange
    {
        $this->listService->requireListOwnedByAccount($accountId, $listId, 'You are not allowed to modify this list.');

        $normalizedName = trim($name);

        if ($normalizedName === '') {
            throw new InvalidArgumentException('Tag name must be provided.');
        }

        $normalizedColor = null;

        if ($color !== null) {
            $color = strtoupper(trim($color));

            if ($color === '') {
                $color = null;
            } elseif (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
                throw new InvalidArgumentException('Tag color must match #RRGGBB.');
            }

            $normalizedColor = $color;
        }

        return $this->listChanges->create(
            $listId,
            $accountId,
            ListChange::TYPE_ADD_TAG,
            [
                'name' => $normalizedName,
                'color' => $normalizedColor,
            ]
        );
    }
}
