<?php

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Exception;
use Psr\SimpleCache\CacheInterface;

/**
 * Memcached implements a cache application component based on [memcached](http://pecl.php.net/package/memcached) PECL
 * extension.
 *
 * Memcached can be configured with a list of memcached servers by settings its {@see Memcached::$servers} property.
 * By default, Memcached assumes there is a memcached server running on localhost at port 11211.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that MemCached supports.
 *
 * Note, there is no security measure to protected data in memcached.
 * All data in memcached can be accessed by any process running in the system.
 */
final class Memcached implements CacheInterface
{
    public const EXPIRATION_INFINITY = 0;

    /**
     * @var \Memcached the Memcached instance
     */
    private $cache;

    public function __construct()
    {
        $this->initCache();
    }

    public function get($key, $default = null)
    {
        $value = $this->cache->get($key);

        if ($this->cache->getResultCode() === \Memcached::RES_SUCCESS) {
            return $value;
        }

        return $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $expiration = $this->ttlToExpiration($ttl);
        if ($expiration < 0) {
            return $this->delete($key);
        }
        return $this->cache->set($key, $value, $expiration);
    }

    public function delete($key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->flush();
    }

    public function getMultiple($keys, $default = null)
    {
        $values = $this->cache->getMulti($this->iterableToArray($keys));
        return array_merge(array_fill_keys($this->iterableToArray($keys), $default), $values);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $expiration = $this->ttlToExpiration($ttl);
        return $this->cache->setMulti($this->iterableToArray($values), $expiration);
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($this->cache->deleteMulti($this->iterableToArray($keys)) as $result) {
            if ($result === false) {
                return false;
            }
        }
        return true;
    }

    public function has($key): bool
    {
        $this->cache->get($key);
        return $this->cache->getResultCode() === \Memcached::RES_SUCCESS;
    }

    /**
     * Inits Memcached instance
     */
    private function initCache(): void
    {
        $this->cache = new \Memcached();
    }

    /**
     * Adds a server to the Memcached server pool
     * @param MemcachedServer $server
     */
    public function addServer(MemcachedServer $server): void
    {
        $this->cache->addServer($server->getHost(), $server->getPort(), $server->getWeight());
    }

    /**
     * Converts TTL to expiration
     * @param $ttl
     * @return int
     */
    protected function ttlToExpiration($ttl): int
    {
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            $expiration = static::EXPIRATION_INFINITY;
        } elseif ($ttl <= 0) {
            $expiration = -1;
        } else {
            $expiration = $ttl + time();
        }

        return $expiration;
    }

    /**
     * Normalizes cache TTL handling `null` value and {@see DateInterval} objects.
     * @param int|DateInterval|null $ttl raw TTL.
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    protected function normalizeTtl($ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            try {
                return (new DateTime('@0'))->add($ttl)->getTimestamp();
            } catch (Exception $e) {
                return null;
            }
        }

        return $ttl;
    }

    /**
     * Converts iterable to array
     * @param iterable $iterable
     * @return array
     */
    protected function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable) : (array)$iterable;
    }
}
