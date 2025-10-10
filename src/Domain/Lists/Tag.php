<?php

declare(strict_types=1);

namespace GameItemsList\Domain\Lists;

final class Tag
{
    public function __construct(
        private readonly string $id,
        private readonly string $listId,
        private readonly string $name,
        private readonly ?string $color,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function listId(): string
    {
        return $this->listId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function color(): ?string
    {
        return $this->color;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            $row['id'],
            $row['list_id'],
            $row['name'],
            $row['color'] ?? null,
        );
    }
}

