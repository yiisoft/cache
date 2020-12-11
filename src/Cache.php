<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Cache\Exception\SetCacheException;
use Yiisoft\Cache\Metadata\CacheItems;

use function gettype;
use function is_array;
use function is_int;

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
    private CacheKeyNormalizer $keyNormalizer;

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
        $this->keyNormalizer = new CacheKeyNormalizer();
        $this->keyPrefix = $keyPrefix;
        $this->defaultTtl = $this->normalizeTtl($defaultTtl);
    }

    public function getOrSet($key, callable $callable, $ttl = null, Dependency $dependency = null, float $beta = 1.0)
    {
        $key = $this->buildKey($key);

        if (!$this->metadata->expired($key, $beta)) {
            $value = $this->getValueOrDefaultIfDependencyChanged($this->handler->get($key));

            if ($value !== null) {
                return $value;
            }
        }

        $ttl = ($ttl = $this->normalizeTtl($ttl)) ?? $this->defaultTtl;
        $value = $this->addDependencyToValue($callable, $dependency);

        if (!$this->handler->set($key, $value, $ttl)) {
            throw new SetCacheException($key, $value, $this);
        }

        $this->metadata->set($key, $ttl);
        return $value;
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

    public function clear(): bool
    {
        if ($this->handler->clear()) {
            $this->metadata->clear();
            return true;
        }

        return false;
    }

    /**
     * Returns array of value and dependency or just value if dependency is null.
     *
     * @param callable $callable
     * @param Dependency|null $dependency
     *
     * @return mixed
     */
    private function addDependencyToValue(callable $callable, ?Dependency $dependency)
    {
        $value = $callable($this);

        if ($dependency === null) {
            return $value;
        }

        $dependency->evaluateDependency($this);
        return [$value, $dependency];
    }

    /**
     * Returns value if there is no dependency or it has not been changed and default value otherwise.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function getValueOrDefaultIfDependencyChanged($value)
    {
        if (is_array($value) && isset($value[1]) && $value[1] instanceof Dependency) {
            /** @var Dependency $dependency */
            [$value, $dependency] = $value;

            if ($dependency->isChanged($this)) {
                return null;
            }
        }

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
        return $this->keyPrefix . $this->keyNormalizer->normalize($key);
    }

    /**
     * Normalizes cache TTL handling `null` value and {@see DateInterval} objects.
     *
     * @param DateInterval|int|null $ttl raw TTL.
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
