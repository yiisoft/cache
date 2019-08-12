<?php

namespace Yiisoft\Cache;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Exception\InvalidConfigException;

/**
 * Memcached implements a cache application component based on [memcached](http://pecl.php.net/package/memcached) PECL
 * extension.
 *
 * Memcached can be configured with a list of memcached servers passed to the constructor.
 * By default, Memcached assumes there is a memcached server running on localhost at port 11211.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that MemCached supports.
 *
 * Note, there is no security measure to protected data in memcached.
 * All data in memcached can be accessed by any process running in the system.
 */
final class Memcached implements CacheInterface
{
    private const EXPIRATION_INFINITY = 0;
    private const EXPIRATION_EXPIRED = -1;
    private const TTL_EXPIRED = -1;
    private const DEFAULT_SERVER_HOST = '127.0.0.1';
    private const DEFAULT_SERVER_PORT = 11211;
    private const DEFAULT_SERVER_WEIGHT = 1;

    /**
     * @var \Memcached the Memcached instance
     */
    private $cache;

    /**
     * @var string an ID that identifies a Memcached instance.
     * By default the Memcached instances are destroyed at the end of the request. To create an instance that
     * persists between requests, you may specify a unique ID for the instance. All instances created with the
     * same ID will share the same connection.
     * @see https://www.php.net/manual/en/memcached.construct.php
     */
    private $persistentId;

    /**
     * @param string $persistentId By default the Memcached instances are destroyed at the end of the request. To create an
     * instance that persists between requests, use persistent_id to specify a unique ID for the instance. All instances
     * created with the same persistent_id will share the same connection.
     * @param array $servers list of memcached servers that will be added to the server pool
     * @see https://www.php.net/manual/en/memcached.construct.php
     * @see https://www.php.net/manual/en/memcached.addservers.php
     */
    public function __construct($persistentId = '', array $servers = [])
    {
        $this->validateServers($servers);
        $this->persistentId = $persistentId;
        $this->initCache();
        $this->initServers($servers);
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

    public function getMultiple($keys, $default = null): iterable
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
     * Returns underlying \Memcached instance
     * @return \Memcached
     */
    public function getCache(): \Memcached
    {
        return $this->cache;
    }

    /**
     * Inits Memcached instance
     */
    private function initCache(): void
    {
        $this->cache = new \Memcached($this->persistentId);
    }

    /**
     * Converts TTL to expiration
     * @param int|DateInterval|null $ttl
     * @return int
     */
    private function ttlToExpiration($ttl): int
    {
        $ttl = $this->normalizeTtl($ttl);

        if ($ttl === null) {
            $expiration = static::EXPIRATION_INFINITY;
        } elseif ($ttl <= 0) {
            $expiration = static::EXPIRATION_EXPIRED;
        } else {
            $expiration = $ttl + time();
        }

        return $expiration;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection DateTime won't throw exception because constant string is passed as time
     *
     * Normalizes cache TTL handling `null` value and {@see DateInterval} objects.
     * @param int|DateInterval|null $ttl raw TTL.
     * @return int|null TTL value as UNIX timestamp or null meaning infinity
     */
    private function normalizeTtl($ttl): ?int
    {
        if ($ttl instanceof DateInterval) {
            return (new DateTime('@0'))->add($ttl)->getTimestamp();
        }

        return $ttl;
    }

    /**
     * Converts iterable to array
     * @param iterable $iterable
     * @return array
     */
    private function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable) : (array)$iterable;
    }

    /**
     * @param array $servers
     */
    private function initServers(array $servers): void
    {
        if ($servers === []) {
            $servers = [
                [self::DEFAULT_SERVER_HOST, self::DEFAULT_SERVER_PORT, self::DEFAULT_SERVER_WEIGHT],
            ];
        }

        if ($this->persistentId !== '') {
            $servers = $this->getNewServers($servers);
        }

        $success = $this->cache->addServers($servers);

        if (!$success) {
            throw new InvalidConfigException('An error occurred while adding servers to the server pool.');
        }
    }

    /**
     * Returns the list of the servers that are not in the pool.
     * @param array $servers
     * @return array
     */
    private function getNewServers(array $servers): array
    {
        $existingServers = [];
        foreach ($this->cache->getServerList() as $existingServer) {
            $existingServers[$existingServer['host'] . ':' . $existingServer['port']] = true;
        }

        $newServers = [];
        foreach ($servers as $server) {
            $serverAddress = $server[0] . ':' . $server[1];
            if (!array_key_exists($serverAddress, $existingServers)) {
                $newServers[] = $server;
            }
        }

        return $newServers;
    }

    /**
     * Validates servers format
     * @param array $servers
     */
    private function validateServers(array $servers): void
    {
        foreach ($servers as $server) {
            if (!is_array($server) || !isset($server[0], $server[1])) {
                throw new InvalidConfigException('Each entry in servers parameter is supposed to be an array containing hostname, port, and, optionally, weight of the server.');
            }
        }
    }
}
