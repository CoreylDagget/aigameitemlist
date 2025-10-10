<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Fixtures;

use GameItemsList\Application\Cache\CacheInterface;

final class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, array{value: string, expiresAt: ?int}>
     */
    private array $store = [];

    public function get(string $key): ?string
    {
        if (!array_key_exists($key, $this->store)) {
            return null;
        }

        $entry = $this->store[$key];

        if ($entry['expiresAt'] !== null && $entry['expiresAt'] <= time()) {
            unset($this->store[$key]);

            return null;
        }

        return $entry['value'];
    }

    public function set(string $key, string $value, int $ttl): void
    {
        $expiresAt = $ttl > 0 ? time() + $ttl : null;

        $this->store[$key] = [
            'value' => $value,
            'expiresAt' => $expiresAt,
        ];
    }

    public function delete(string $key): void
    {
        unset($this->store[$key]);
    }
}

