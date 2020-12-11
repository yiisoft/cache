<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Metadata;

final class CacheItems
{
    /**
     * @var array<string, CacheItem>
     */
    private array $items = [];

    public function expired(string $key, float $beta): bool
    {
        return isset($this->items[$key]) && $this->items[$key]->expired($beta);
    }

    public function set(string $key, ?int $expiry): void
    {
        if (isset($this->items[$key])) {
            $this->items[$key]->expiry($expiry);
            return;
        }

        $this->items[$key] = new CacheItem($expiry);
    }

    public function remove(string $key): void
    {
        if (isset($this->items[$key])) {
            unset($this->items[$key]);
        }
    }

    public function clear(): void
    {
        $this->items = [];
    }
}
