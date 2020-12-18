<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use Psr\SimpleCache\CacheInterface as PsrSimpleCacheInterface;
use Yiisoft\Cache\Metadata\CacheItem;

use function is_array;

/**
 * Cache provides support for the data caching, including dependencies.
 * The actual data caching is performed via {@see DependencyAwareCache::handler()}.
 *
 * @internal
 */
final class DependencyAwareCache implements PsrSimpleCacheInterface
{
    /**
     * @var CacheInterface The actual cache.
     */
    private CacheInterface $cache;

    /**
     * @var PsrSimpleCacheInterface The actual cache handler.
     */
    private PsrSimpleCacheInterface $handler;

    /**
     * @param CacheInterface $cache The actual cache handler.
     * @param PsrSimpleCacheInterface $handler The actual cache handler.
     */
    public function __construct(CacheInterface $cache, PsrSimpleCacheInterface $handler)
    {
        $this->cache = $cache;
        $this->handler = $handler;
    }

    public function get($key, $default = null)
    {
        $value = $this->handler->get($key, $default);
        return $this->checkAndGetValue($key, $value, $default);
    }

    public function set($key, $value, $ttl = null): bool
    {
        return $this->handler->set($key, $value, $ttl);
    }

    public function delete($key): bool
    {
        return $this->handler->delete($key);
    }

    public function clear(): bool
    {
        return $this->handler->clear();
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $values = [];

        foreach ($this->handler->getMultiple($keys, $default) as $key => $value) {
            $values[$key] = $this->checkAndGetValue($key, $value, $default);
        }

        return $values;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return $this->handler->setMultiple($values, $ttl);
    }

    public function deleteMultiple($keys): bool
    {
        return $this->handler->deleteMultiple($keys);
    }

    public function has($key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Checks if the cache dependency has expired and returns a value
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $value The value of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed|null The cache value or `null` if the cache is outdated or a dependency has been changed.
     */
    private function checkAndGetValue(string $key, $value, $default = null)
    {
        if (is_array($value) && isset($value[1]) && $value[1] instanceof CacheItem) {
            [$value, $item] = $value;
            $dependency = $item->dependency();

            if ($item->key() !== $key || ($dependency !== null && $dependency->isChanged($this->cache))) {
                return $default;
            }
        }

        return $value;
    }
}
