<?php
namespace Yiisoft\Cache;

use Yiisoft\Cache\Serializer\SerializerInterface;
use Yiisoft\Cache\Exception\InvalidConfigException;

/**
 * MemCached implements a cache application component based on [memcached](http://pecl.php.net/package/memcached) PECL
 * extension.
 *
 * MemCached can be configured with a list of memcached servers by settings its {@see servers} property.
 * By default, MemCached assumes there is a memcached server running on localhost at port 11211.
 *
 * See {@see \Psr\SimpleCache\CacheInterface} for common cache operations that MemCached supports.
 *
 * Note, there is no security measure to protected data in memcached.
 * All data in memcached can be accessed by any process running in the system.
 *
 * To use MemCached as the cache application component, configure the application as follows,
 *
 * ```php
 * [
 *     'components' => [
 *         'cache' => [
 *             '__class' => \Yiisoft\Cache\Cache::class,
 *             'handler' => [
 *                 '__class' => \Yiisoft\Cache\MemCached::class,
 *                 'servers' => [
 *                     [
 *                         'host' => 'server1',
 *                         'port' => 11211,
 *                         'weight' => 60,
 *                     ],
 *                     [
 *                         'host' => 'server2',
 *                         'port' => 11211,
 *                         'weight' => 40,
 *                     ],
 *                 ],
 *             ],
 *         ],
 *     ],
 * ]
 * ```
 *
 * In the above, two memcached servers are used: server1 and server2. You can configure more properties of
 * each server, such as `persistent`, `weight`, `timeout`. Please see {@see MemCacheServer} for available options.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
final class MemCached extends SimpleCache
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
     * @param array $servers
     * @throws InvalidConfigException
     * @see setSerializer
     */
    public function __construct(?SerializerInterface $serializer = null, array $servers = [])
    {
        parent::__construct($serializer);

        if (empty($servers)) {
            $servers = [new MemCachedServer('127.0.0.1')];
        }

        $this->servers = $servers;

        $this->addServers($this->getMemcached(), $this->servers);
    }

    /**
     * Add servers to the server pool of the cache specified
     *
     * @param \Memcached $cache
     * @param MemCachedServer[] $servers
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
     * @return MemCachedServer[] list of memcached server configurations.
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * @param array $configs list of memcached server configurations. Each element must be an array
     * with the following keys: host, port, persistent, weight, timeout, retryInterval, status.
     * @see http://php.net/manual/en/memcached.addserver.php
     */
    public function setServers(array $configs): void
    {
        foreach ($configs as $config) {
            $this->servers[] = new MemCachedServer($config['host'], $config['port'], $config['weight']);
        }
    }

    /**
     * @param string $persistentId
     */
    public function setPersistentId(string $persistentId): void
    {
        $this->persistentId = $persistentId;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @param string $password
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

    protected function getValues(array $keys, $default = null): array
    {
        $values = $this->cache->getMulti($keys);

        if ($this->cache->getResultCode() === \Memcached::RES_SUCCESS) {
            // TODO: test that all fields are returned
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

    protected function setValues(array $values, ?int $ttl): bool
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
}
