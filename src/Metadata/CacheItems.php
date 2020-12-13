<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Metadata;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;

/**
 * CacheItems store the metadata of each cache item.
 *
 * @internal
 */
final class CacheItems
{
    /**
     * @var array<string, CacheItem>
     */
    private array $items = [];

    public function expired(string $key, float $beta, CacheInterface $cache): bool
    {
        return isset($this->items[$key]) && $this->items[$key]->expired($beta, $cache);
    }

    public function dependency(string $key): ?Dependency
    {
        if (isset($this->items[$key])) {
            return $this->items[$key]->dependency();
        }

        return null;
    }

    public function get(string $key): ?CacheItem
    {
        return $this->items[$key] ?? null;
    }

    public function set(CacheItem $item): void
    {
        $key = $item->key();

        if (!isset($this->items[$key])) {
            $this->items[$key] = $item;
            return;
        }

        $this->items[$key]->update($item->value(), $item->expiry(), $item->dependency());
    }

    public function remove(string $key): void
    {
        if (isset($this->items[$key])) {
            unset($this->items[$key]);
        }
    }
}
