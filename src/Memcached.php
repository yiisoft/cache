<?php

namespace Yiisoft\Cache;

use Yiisoft\Cache\Exception\InvalidConfigException;
use Yiisoft\Cache\Serializer\SerializerInterface;

/**
 * Memcached implements a cache application component based on [memcached](http://pecl.php.net/package/memcached) PECL
 * extension.
 *
 * Memcached can be configured with a list of memcached servers by settings its {@see Memcached::$servers} property.
 * By default, MemCached assumes there is a memcached server running on localhost at port 11211.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that MemCached supports.
 *
 * Note, there is no security measure to protected data in memcached.
 * All data in memcached can be accessed by any process running in the system.
 *
 * You can configure more properties of each server, such as `persistent`, `weight`, `timeout`.
 * Please see {@see MemcachedServer} for available options.
 */
final class Memcached extends SimpleCache
{
    private const TTL_INFINITY = 0;

    /**
     * @var string an ID that identifies a Memcached instance.
     * By default the Memcached instances are destroyed at the end of the request. To create an instance that
     * persists between requests, you may specify a unique ID for the instance. All instances created with the
     * same ID will share the same connection.
     * @see http://ca2.php.net/manual/en/memcached.construct.php
     */
    private $persistentId;
    /**
     * @var array options for Memcached.
     * @see http://ca2.php.net/manual/en/memcached.setoptions.php
     */
    private $options;
    /**
     * @var string memcached sasl username.
     * @see http://php.net/manual/en/memcached.setsaslauthdata.php
     */
    private $username;
    /**
     * @var string memcached sasl password.
     * @see http://php.net/manual/en/memcached.setsaslauthdata.php
     */
    private $password;

    /**
     * @var \Memcached the Memcached instance
     */
    private $cache;
    /**
     * @var array list of memcached server configurations
     */
    private $servers;

    /**
     * @param SerializerInterface|null $serializer
     * @param MemcachedServer[] $servers list of memcached server configurations
     * @throws InvalidConfigException
     * @see setSerializer
     */
    public function __construct(?SerializerInterface $serializer = null, array $servers = [])
    {
        parent::__construct($serializer);

        if (empty($servers)) {
            $servers = [new MemcachedServer()];
        }

        $this->servers = $servers;

        $this->addServers($this->getMemcached(), $this->servers);
    }

    /**
     * Add servers to the server pool of the cache specified
     *
     * @param \Memcached $cache
     * @param MemcachedServer[] $servers
     */
    private function addServers(\Memcached $cache, array $servers): void
    {
        $existingServers = [];
        if ($this->persistentId !== null) {
            foreach ($cache->getServerList() as $s) {
                $existingServers[$s['host'] . ':' . $s['port']] = true;
            }
        }
        foreach ($servers as $server) {
            $serverAddress = $server->getHost() . ':' . $server->getPort();
            if (empty($existingServers) || !isset($existingServers[$serverAddress])) {
                $cache->addServer($server->getHost(), $server->getPort(), $server->getWeight());
            }
        }
    }

    /**
     * Returns the underlying memcached object.
     * @return \Memcached the memcached object used by this cache component.
     * @throws InvalidConfigException if memcached extension is not loaded
     */
    public function getMemcached(): \Memcached
    {
        if ($this->cache === null) {
            if (!\extension_loaded('memcached')) {
                throw new InvalidConfigException('MemCached requires PHP memcached extension to be loaded.');
            }

            $this->cache = $this->persistentId !== null ? new \Memcached($this->persistentId) : new \Memcached();
            if ($this->username !== null || $this->password !== null) {
                $this->cache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
                $this->cache->setSaslAuthData($this->username, $this->password);
            }
            if (!empty($this->options)) {
                $this->cache->setOptions($this->options);
            }
        }

        return $this->cache;
    }

    /**
     * Returns the memcached server configurations.
     * @return MemcachedServer[] list of memcached server configurations.
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * @param array $configs list of memcached server configurations. Each element must be an array
     * with the following keys: host, port, weight.
     * @see http://php.net/manual/en/memcached.addserver.php
     */
    public function setServers(array $configs): void
    {
        foreach ($configs as $config) {
            $this->servers[] = new MemcachedServer($config['host'], $config['port'], $config['weight']);
        }
    }

    /**
     * @param string $persistentId an ID that identifies a Memcached instance.
     * By default the Memcached instances are destroyed at the end of the request. To create an instance that
     * persists between requests, you may specify a unique ID for the instance. All instances created with the
     * same ID will share the same connection.
     * @see http://ca2.php.net/manual/en/memcached.construct.php
     */
    public function setPersistentId(string $persistentId): void
    {
        $this->persistentId = $persistentId;
    }

    /**
     * @param array $options options for Memcached.
     * @see http://ca2.php.net/manual/en/memcached.setoptions.php
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @param string $username memcached sasl username.
     * @see http://php.net/manual/en/memcached.setsaslauthdata.php
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @param string $password memcached sasl password.
     * @see http://php.net/manual/en/memcached.setsaslauthdata.php
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    protected function getValue(string $key, $default = null)
    {
        $value = $this->cache->get($key);

        if ($this->cache->getResultCode() === \Memcached::RES_SUCCESS) {
            return $value;
        }

        return $default;
    }

    protected function getValues(iterable $keys, $default = null): iterable
    {
        $values = $this->cache->getMulti($keys);

        if ($this->cache->getResultCode() === \Memcached::RES_SUCCESS) {
            return $values;
        }

        return array_fill_keys($keys, $default);
    }

    protected function setValue(string $key, $value, ?int $ttl): bool
    {
        if ($ttl === null) {
            $ttl = self::TTL_INFINITY;
        } else {
            $ttl += time();
        }
        return $this->cache->set($key, $value, $ttl);
    }

    protected function setValues(iterable $values, ?int $ttl): bool
    {
        if ($ttl === null) {
            $ttl = self::TTL_INFINITY;
        } else {
            $ttl += time();
        }
        return $this->cache->setMulti($values, $ttl);
    }

    protected function deleteValue(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->flush();
    }

    protected function hasValue(string $key): bool
    {
        $this->cache->get($key);
        return $this->cache->getResultCode() === \Memcached::RES_SUCCESS;
    }

    public function deleteValues(iterable $keys): bool
    {
        foreach ($this->cache->deleteMulti($keys) as $result) {
            if ($result === false) {
                return false;
            }
        }
        return true;
    }
}
