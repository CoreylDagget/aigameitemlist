<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

use GameItemsList\Domain\Game\Game;

final class GameList
{
    public function __construct(
        private readonly string $id,
        private readonly string $ownerAccountId,
        private readonly Game $game,
        private readonly string $name,
        private readonly ?string $description,
        private readonly bool $isPublished,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function ownerAccountId(): string
    {
        return $this->ownerAccountId;
    }

    public function game(): Game
    {
        return $this->game;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
