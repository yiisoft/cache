<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Metadata;

use Yiisoft\Cache\Dependency\Dependency;

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

    public function dependency(string $key): ?Dependency
    {
        if (isset($this->items[$key])) {
            return $this->items[$key]->dependency();
        }

        return null;
    }

    public function set(string $key, ?int $expiry, ?Dependency $dependency): void
    {
        if (isset($this->items[$key])) {
            $this->items[$key]->update($expiry, $dependency);
            return;
        }

        $this->items[$key] = new CacheItem($expiry, $dependency);
    }

    public function remove(string $key): void
    {
        if (isset($this->items[$key])) {
            unset($this->items[$key]);
        }
    }
}
