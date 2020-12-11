<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Exception\SetCacheException;
use Yiisoft\Cache\Metadata\CacheItems;

use function ctype_alnum;
use function gettype;
use function is_int;
use function is_string;
use function json_encode;
use function json_last_error_msg;
use function mb_strlen;
use function md5;

/**
 * Cache provides support for the data caching, including cache key composition and dependencies.
 * The actual data caching is performed via {@see Cache::$handler}, which should be configured
 * to be {@see \Psr\SimpleCache\CacheInterface} instance.
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
 *     $cache->set($key, $data, $ttl, $dependency);
 * }
 * ```
 *
 * For more details and usage information on Cache, see
 * [PSR-16 specification](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-16-simple-cache.md).
 */
final class Cache implements CacheInterface
{
    /**
     * @var \Psr\SimpleCache\CacheInterface actual cache handler.
     */
    private \Psr\SimpleCache\CacheInterface $handler;

    private CacheItems $metadata;

    /**
     * @var string a string prefixed to every cache key so that it is unique globally in the whole cache storage.
     * It is recommended that you set a unique cache key prefix for each application if the same cache
     * storage is being used by different applications.
     */
    private string $keyPrefix;

    /**
     * @var int|null default TTL for a cache entry. null meaning infinity, negative or zero results in cache key deletion.
     * This value is used by {@see set()} and {@see setMultiple()}, if the duration is not explicitly given.
     */
    private ?int $defaultTtl;

    /**
     * @param \Psr\SimpleCache\CacheInterface $handler
     * @param DateInterval|int|null $defaultTtl
     * @param string $keyPrefix
     */
    public function __construct(\Psr\SimpleCache\CacheInterface $handler, $defaultTtl = null, string $keyPrefix = '')
    {
        $this->handler = $handler;
        $this->metadata = new CacheItems();
        $this->defaultTtl = $this->normalizeTtl($defaultTtl);
        $this->keyPrefix = $keyPrefix;
    }

    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null, float $beta = 1.0)
    {
        $key = $this->buildKey($key);
        $value = $this->getValue($key, $beta);

        if ($value !== null) {
            return $value;
        }

        return $this->setAndGet($key, $callable, $ttl, $dependency);
    }

    public function remove($key): bool
    {
        $key = $this->buildKey($key);

        if ($this->handler->delete($key)) {
            $this->metadata->remove($key);
            return true;
        }

        return false;
    }

    private function getValue(string $key, float $beta)
    {
        if ($this->metadata->expired($key, $beta)) {
            return null;
        }

        $dependency = $this->metadata->dependency($key);

        if ($dependency !== null && $dependency->isChanged($this)) {
            return null;
        }

        return $this->handler->get($key);
    }

    private function setAndGet(string $key, callable $callable, $ttl, ?Dependency $dependency)
    {
        $ttl = $this->normalizeTtl($ttl);
        $ttl ??= $this->defaultTtl;
        $value = $callable($this);

        if ($dependency !== null) {
            $dependency->evaluateDependency($this);
        }

        if (!$this->handler->set($key, $value, $ttl)) {
            throw new SetCacheException($key, $value, $this);
        }

        $this->metadata->set($key, $ttl, $dependency);
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
