<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache;

use yii\serialize\SerializerInterface;
use Yiisoft\Cache\Exceptions\InvalidConfigException;

/**
 * MemCached implements a cache application component based on [memcached](http://pecl.php.net/package/memcached) PECL
 * extension.
 *
 * MemCached can be configured with a list of memcached servers by settings its [[servers]] property.
 * By default, MemCached assumes there is a memcached server running on localhost at port 11211.
 *
 * See [[\Psr\SimpleCache\CacheInterface]] for common cache operations that MemCached supports.
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
 * each server, such as `persistent`, `weight`, `timeout`. Please see [[MemCacheServer]] for available options.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 *
 * @property \Memcached $memcached The memcached object used by this cache component.
 * This property is read-only.
 * @property MemCachedServer[] $servers List of memcached server configurations. Note that the type of this
 * property differs in getter and setter. See [[getServers()]] and [[setServers()]] for details.
 */
class MemCached extends SimpleCache
{
    /**
     * @var string an ID that identifies a Memcached instance.
     * By default the Memcached instances are destroyed at the end of the request. To create an instance that
     * persists between requests, you may specify a unique ID for the instance. All instances created with the
     * same ID will share the same connection.
     * @see http://ca2.php.net/manual/en/memcached.construct.php
     */
    public $persistentId;
    /**
     * @var array options for Memcached.
     * @see http://ca2.php.net/manual/en/memcached.setoptions.php
     */
    public $options;
    /**
     * @var string memcached sasl username.
     * @see http://php.net/manual/en/memcached.setsaslauthdata.php
     */
    public $username;
    /**
     * @var string memcached sasl password.
     * @see http://php.net/manual/en/memcached.setsaslauthdata.php
     */
    public $password;

    /**
     * @var \Memcached the Memcached instance
     */
    private $_cache;
    /**
     * @var array list of memcached server configurations
     */
    private $_servers;

    /**
     * @param null $serializer
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

        $this->_servers = $servers;

        $this->addServers($this->getMemcached(), $this->_servers);
    }

    /**
     * Add servers to the server pool of the cache specified
     *
     * @param \Memcached $cache
     * @param MemCachedServer[] $servers
     */
    protected function addServers($cache, $servers)
    {
        $existingServers = [];
        if ($this->persistentId !== null) {
            foreach ($cache->getServerList() as $s) {
                $existingServers[$s['host'] . ':' . $s['port']] = true;
            }
        }
        foreach ($servers as $server) {
            if (empty($existingServers) || !isset($existingServers[$server->getHost() . ':' . $server->getPort()])) {
                $cache->addServer($server->getHost(), $server->getPort(), $server->getWeight());
            }
        }
    }

    /**
     * Returns the underlying memcached object.
     * @return \Memcached the memcached object used by this cache component.
     * @throws InvalidConfigException if memcached extension is not loaded
     */
    public function getMemcached()
    {
        if ($this->_cache === null) {
            if (!extension_loaded('memcached')) {
                throw new InvalidConfigException('MemCached requires PHP memcached extension to be loaded.');
            }

            $this->_cache = $this->persistentId !== null ? new \Memcached($this->persistentId) : new \Memcached;
            if ($this->username !== null || $this->password !== null) {
                $this->_cache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
                $this->_cache->setSaslAuthData($this->username, $this->password);
            }
            if (!empty($this->options)) {
                $this->_cache->setOptions($this->options);
            }
        }

        return $this->_cache;
    }

    /**
     * Returns the memcached server configurations.
     * @return MemCachedServer[] list of memcached server configurations.
     */
    public function getServers()
    {
        return $this->_servers;
    }

    /**
     * @param array $config list of memcached server configurations. Each element must be an array
     * with the following keys: host, port, persistent, weight, timeout, retryInterval, status.
     * @see http://php.net/manual/en/memcached.addserver.php
     */
    public function setServers($config)
    {
        foreach ($config as $c) {
            $this->_servers[] = new MemCachedServer($c);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue($key)
    {
        return $this->_cache->get($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function getValues($keys): array
    {
        return $this->_cache->getMulti($keys);
    }

    /**
     * {@inheritdoc}
     */
    protected function setValue($key, $value, $ttl): bool
    {
        // Use UNIX timestamp since it doesn't have any limitation
        // @see http://php.net/manual/en/memcached.expiration.php
        $expire = $ttl > 0 ? $ttl + time() : 0;

        return $this->_cache->set($key, $value, $expire);
    }

    /**
     * {@inheritdoc}
     */
    protected function setValues($values, $ttl): bool
    {
        // Use UNIX timestamp since it doesn't have any limitation
        // @see http://php.net/manual/en/memcached.expiration.php
        $expire = $ttl > 0 ? $ttl + time() : 0;

        return $this->_cache->setMulti($values, $expire);
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteValue($key): bool
    {
        return $this->_cache->delete($key, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->_cache->flush();
    }
}
