<?php

declare(strict_types=1);

namespace GameItemsList\Application\Cache;

interface CacheInterface
{
    public function get(string $key): ?string;

    public function set(string $key, string $value, int $ttl): void;

    public function delete(string $key): void;
}
