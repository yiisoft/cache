<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Metadata;

use Psr\SimpleCache\CacheInterface;

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

    /**
     * @param string $key
     * @param float $beta
     * @param CacheInterface $cache
     *
     * @return mixed|null
     */
    public function getValue(string $key, float $beta, CacheInterface $cache)
    {
        if (isset($this->items[$key]) && !$this->items[$key]->expired($beta, $cache)) {
            return $this->items[$key]->value();
        }

        return null;
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

    public function has(string $key): bool
    {
        return isset($this->items[$key]);
    }
}
