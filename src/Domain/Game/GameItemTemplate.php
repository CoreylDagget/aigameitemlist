<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Game;

final class GameItemTemplate
{
    public function __construct(
        private readonly string $id,
        private readonly string $gameId,
        private readonly string $name,
        private readonly ?string $description,
        private readonly ?string $imageUrl,
        private readonly string $storageType,
    ) {
    }

    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['game_id'],
            $row['name'],
            $row['description'] ?? null,
            $row['image_url'] ?? null,
            $row['storage_type'],
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function gameId(): string
    {
        return $this->gameId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function imageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function storageType(): string
    {
        return $this->storageType;
    }
}
