<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Fixtures;

use DateInterval;
use DateTimeImmutable;
use GameItemsList\Application\Clock\ClockInterface;

final class FrozenClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advanceSeconds(int $seconds): void
    {
        $this->now = $this->now->add(new DateInterval(sprintf('PT%dS', $seconds)));
    }
}
