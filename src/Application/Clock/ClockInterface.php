<?php

declare(strict_types=1);

namespace GameItemsList\Application\Clock;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
