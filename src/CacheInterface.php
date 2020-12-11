<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use Yiisoft\Cache\Dependency\Dependency;

/**
 * CacheInterface defines the common interface to be implemented by cache classes.
 * It extends {@see \Psr\SimpleCache\CacheInterface} adding ability for cache dependency specification.
 *
 * A value can be stored in the cache by calling {@see CacheInterface::set()} and be retrieved back
 * later (in the same or different request) by {@see CacheInterface::get()}. In both operations,
 * a key identifying the value is required. An expiration time and/or a {@see Dependency}
 * can also be specified when calling {@see CacheInterface::set()}. If the value expires or the dependency
 * changes at the time of calling {@see CacheInterface::get()}, the cache will return no data.
 *
 * A typical usage pattern of cache is like the following:
 *
 * ```php
 * $key = 'demo';
 * $data = $cache->get($key);
 * if ($data === null) {
 *     // ...generate $data here...
 *     $cache->set($key, $data, $duration, $dependency);
 * }
 * ```
 *
 * For more details and usage information on Cache, see
 * [PSR-16 specification](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-16-simple-cache.md).
 *
 * @see \Psr\SimpleCache\CacheInterface
 */
interface CacheInterface
{
    /**
     * Method combines both {@see CacheInterface::set()} and {@see CacheInterface::get()} methods to retrieve value identified by a $key,
     * or to store the result of $callable execution if there is no cache available for the $key.
     *
     * Usage example:
     *
     * ```php
     * public function getTopProducts($count = 10) {
     *     $cache = $this->cache;
     *     return $this->cache->getOrSet(['top-n-products', 'n' => $count], function ($cache) use ($count) {
     *         return $this->getTopNProductsFromDatabase($count);
     *     }, 1000);
     * }
     * ```
     *
     * @param mixed $key a key identifying the value to be cached.
     * @param callable $callable the callable or closure that will be used to generate a value to be cached.
     * In case $callable returns `false`, the value will not be cached.
     * @param \DateInterval|int|null $ttl the TTL of this value. If not set, default value is used.
     * @param Dependency|null $dependency dependency of the value. If the dependency changes,
     * the corresponding value in the cache will be invalidated when it is fetched via {@see get()}.
     *
     * @return mixed Result of $callable execution
     */
    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null, float $beta = 1.0);

    /**
     * Removes a value with the specified key from cache.
     *
     * @param mixed $key a key identifying the value to be deleted from cache.
     */
    public function remove($key): void;
}
