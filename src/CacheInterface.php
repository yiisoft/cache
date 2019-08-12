<?php

namespace Yiisoft\Cache;

use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Serializer\SerializerInterface;

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
interface CacheInterface extends \Psr\SimpleCache\CacheInterface
{
    /**
     * Stores a value identified by a key into cache.
     * If the cache already contains such a key, the existing value and
     * expiration time will be replaced with the new ones, respectively.
     *
     * @param mixed $key a key identifying the value to be cached. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @param mixed $value the value to be cached
     * @param null|int|\DateInterval $ttl the TTL of this value. If not set, default value is used.
     * @param Dependency $dependency dependency of the value. If the dependency changes,
     * the corresponding value in the cache will be invalidated when it is fetched via {@see CacheInterface::get()}.
     * @return bool whether the value is successfully stored into cache
     */
    public function set($key, $value, $ttl = null, Dependency $dependency = null): bool;

    /**
     * Stores multiple values in cache. Each value is identified by a key.
     * If the cache already contains such a key, the existing value and
     * expiration time will be replaced with the new ones, respectively.
     *
     * @param array $values the values to be cached, as key-value pairs.
     * @param null|int|\DateInterval $ttl the TTL of this value. If not set, default value is used.
     * @param Dependency $dependency dependency of the cached values. If the dependency changes,
     * the corresponding values in the cache will be invalidated when it is fetched via {@see CacheInterface::get()}.
     * @return bool
     */
    public function setMultiple($values, $ttl = null, Dependency $dependency = null): bool;

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * Nothing will be done if the cache already contains the key.
     * @param mixed $key a key identifying the value to be cached. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @param mixed $value the value to be cached
     * @param null|int|\DateInterval $ttl the TTL of this value. If not set, default value is used.
     * @param Dependency $dependency dependency of the value. If the dependency changes,
     * the corresponding value in the cache will be invalidated when it is fetched via {@see CacheInterface::get()}.
     * @return bool whether the value is successfully stored into cache
     */
    public function add($key, $value, $ttl = 0, Dependency $dependency = null): bool;

    /**
     * Stores multiple values in cache. Each value is identified by a key.
     * If the cache already contains such a key, the existing value and expiration time will be preserved.
     *
     * @param array $values the values to be cached, as key-value pairs.
     * @param null|int|\DateInterval $ttl the TTL of this value. If not set, default value is used.
     * @param Dependency $dependency dependency of the cached values. If the dependency changes,
     * the corresponding values in the cache will be invalidated when it is fetched via {@see CacheInterface::get()}.
     * @return bool
     */
    public function addMultiple(array $values, $ttl = null, Dependency $dependency = null): bool;

    /**
     * Method combines both {@see CacheInterface::set()} and {@see CacheInterface::get()} methods to retrieve value identified by a $key,
     * or to store the result of $callable execution if there is no cache available for the $key.
     *
     * Usage example:
     *
     * ```php
     * public function getTopProducts($count = 10) {
     *     $cache = $this->cache; // Could be Yii::getApp()->cache
     *     return $cache->getOrSet(['top-n-products', 'n' => $count], function ($cache) use ($count) {
     *         return $this->getTopNProductsFromDatabase($count);
     *     }, 1000);
     * }
     * ```
     *
     * @param mixed $key a key identifying the value to be cached. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @param callable $callable the callable or closure that will be used to generate a value to be cached.
     * In case $callable returns `false`, the value will not be cached.
     * @param null|int|\DateInterval $ttl the TTL of this value. If not set, default value is used.
     * @param Dependency $dependency dependency of the value. If the dependency changes,
     * the corresponding value in the cache will be invalidated when it is fetched via {@see get()}.
     * @return mixed result of $callable execution
     */
    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null);

    public function enableKeyNormalization(): void;

    public function disableKeyNormalization(): void;

    public function setKeyPrefix(string $keyPrefix): void;
}
