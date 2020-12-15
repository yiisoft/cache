<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Exception\RemoveCacheException;
use Yiisoft\Cache\Exception\SetCacheException;
use Yiisoft\Cache\Metadata\CacheItem;
use Yiisoft\Cache\Metadata\CacheItems;

use function ctype_alnum;
use function gettype;
use function is_array;
use function is_int;
use function is_string;
use function json_encode;
use function json_last_error_msg;
use function mb_strlen;
use function md5;

/**
 * Cache provides support for the data caching, including cache key composition and dependencies, and uses
 * "Probably early expiration" for cache stampede prevention. The actual data caching is performed via
 * {@see Cache::$handler}, which should be configured to be {@see \Psr\SimpleCache\CacheInterface} instance.
 *
 * @see \Yiisoft\Cache\CacheInterface
 */
final class Cache implements CacheInterface
{
    /**
     * @var \Psr\SimpleCache\CacheInterface The actual cache handler.
     */
    private \Psr\SimpleCache\CacheInterface $handler;

    /**
     * @var CacheItems The items that store the metadata of each cache.
     */
    private CacheItems $items;

    /**
     * @var int|null The default TTL for a cache entry. null meaning infinity, negative or zero results in the
     * cache key deletion. This value is used by {@see getOrSet()}, if the duration is not explicitly given.
     */
    private ?int $defaultTtl;

    /**
     * @var string The string prefixed to every cache key so that it is unique globally in the whole cache storage.
     * It is recommended that you set a unique cache key prefix for each application if the same cache
     * storage is being used by different applications.
     */
    private string $keyPrefix;

    /**
     * @param \Psr\SimpleCache\CacheInterface $handler The actual cache handler.
     * @param DateInterval|int|null $defaultTtl The default TTL for a cache entry.
     * null meaning infinity, negative orzero results in the cache key deletion.
     * This value is used by {@see getOrSet()}, if the duration is not explicitly given.
     * @param string $keyPrefix The string prefixed to every cache key so that it is unique globally
     * in the whole cache storage. It is recommended that you set a unique cache key prefix for each
     * application if the same cache storage is being used by different applications.
     */
    public function __construct(\Psr\SimpleCache\CacheInterface $handler, $defaultTtl = null, string $keyPrefix = '')
    {
        $this->handler = $handler;
        $this->items = new CacheItems();
        $this->defaultTtl = $this->normalizeTtl($defaultTtl);
        $this->keyPrefix = $keyPrefix;
    }

    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null, float $beta = 1.0)
    {
        $key = $this->buildKey($key);
        $value = $this->getValue($key, $beta);

        return $value ?? $this->setAndGet($key, $callable, $ttl, $dependency);
    }

    public function remove($key): void
    {
        $key = $this->buildKey($key);

        if (!$this->handler->delete($key)) {
            throw new RemoveCacheException($key);
        }

        $this->items->remove($key);
    }

    /**
     * Gets the cache value.
     *
     * @param string $key The unique key of this item in the cache.
     * @param float $beta The value for calculating the range that is used for "Probably early expiration" algorithm.
     *
     * @return mixed|null The cache value or `null` if the cache is outdated or a dependency has been changed.
     */
    private function getValue(string $key, float $beta)
    {
        if ($this->items->expired($key, $beta, $this->handler)) {
            return null;
        }

        $value = $this->handler->get($key);

        if (is_array($value) && isset($value[1]) && $value[1] instanceof CacheItem) {
            [$value, $item] = $value;

            if ($item->key() !== $key || $item->expired($beta, $this->handler)) {
                return null;
            }

            $this->items->set($item);
        }

        return $value;
    }

    /**
     * Sets the cache value and metadata, and returns the cache value.
     *
     * @param string $key The unique key of this item in the cache.
     * @param callable $callable The callable or closure that will be used to generate a value to be cached.
     * @param DateInterval|int|null $ttl The TTL of this value. If not set, default value is used.
     * @param Dependency|null $dependency The dependency of the cache value.
     *
     * @throws InvalidArgumentException Must be thrown if the `$key` or `$ttl` is not a legal value.
     * @throws SetCacheException Must be thrown if the data could not be set in the cache.
     *
     * @return mixed|null The cache value.
     */
    private function setAndGet(string $key, callable $callable, $ttl, ?Dependency $dependency)
    {
        $ttl = $this->normalizeTtl($ttl);
        $ttl ??= $this->defaultTtl;
        $value = $callable($this->handler);

        if ($dependency !== null) {
            $dependency->evaluateDependency($this->handler);
        }

        $item = new CacheItem($key, $ttl, $dependency);

        if (!$this->handler->set($key, [$value, $item], $ttl)) {
            throw new SetCacheException($key, $value, $item);
        }

        $this->items->set($item);
        return $value;
    }

    /**
     * Builds a normalized cache key from a given key by appending key prefix.
     *
     * @param mixed $key The key to be normalized.
     *
     * @return string The generated cache key.
     */
    private function buildKey($key): string
    {
        return $this->keyPrefix . $this->normalizeKey($key);
    }

    /**
     * Normalizes the cache key from a given key.
     *
     * If the given key is a string containing alphanumeric characters only and no more than 32 characters,
     * then the key will be returned back as it is, integers will be converted to strings. Otherwise,
     * a normalized key is generated by serializing the given key and applying MD5 hashing.
     *
     * @param mixed $key The key to be normalized.
     *
     * @throws InvalidArgumentException For invalid key.
     *
     * @return string The normalized cache key.
     */
    private function normalizeKey($key): string
    {
        if (is_string($key) || is_int($key)) {
            $key = (string) $key;
            return ctype_alnum($key) && mb_strlen($key, '8bit') <= 32 ? $key : md5($key);
        }

        if (($key = json_encode($key)) === false) {
            throw new InvalidArgumentException('Invalid key. ' . json_last_error_msg());
        }

        return md5($key);
    }

    /**
     * Normalizes cache TTL handling `null` value and {@see DateInterval} objects.
     *
     * @param mixed $ttl raw TTL.
     *
     * @throws InvalidArgumentException For invalid TTL.
     *
     * @return int|null TTL value as UNIX timestamp or null meaning infinity.
     */
    private function normalizeTtl($ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            return (new DateTime('@0'))->add($ttl)->getTimestamp();
        }

        if (is_int($ttl)) {
            return $ttl;
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid TTL "%s" specified. It must be a \DateInterval instance, an integer, or null.',
            gettype($ttl),
        ));
    }
}
