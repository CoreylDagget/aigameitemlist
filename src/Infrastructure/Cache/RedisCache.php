<?php
declare(strict_types=1);

namespace GameItemsList\Infrastructure\Cache;

use GameItemsList\Application\Cache\CacheInterface;
use Predis\ClientInterface;
use Throwable;

final class RedisCache implements CacheInterface
{
    public function __construct(private readonly ClientInterface $client)
    {
    }

    public function get(string $key): ?string
    {
        try {
            $value = $this->client->get($key);
        } catch (Throwable) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        return $value;
    }

    public function set(string $key, string $value, int $ttl): void
    {
        try {
            if ($ttl > 0) {
                $this->client->setex($key, $ttl, $value);
            } else {
                $this->client->set($key, $value);
            }
        } catch (Throwable) {
            // Ignore cache failures to keep the application responsive.
        }
    }

    public function delete(string $key): void
    {
        try {
            $this->client->del([$key]);
        } catch (Throwable) {
            // Ignore cache failures to keep the application responsive.
        }
    }
}
