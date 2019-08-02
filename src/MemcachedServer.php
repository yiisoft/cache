<?php

namespace Yiisoft\Cache;

/**
 * MemcachedServer represents the configuration data for a single memcached server.
 *
 * See [PHP manual](http://php.net/manual/en/memcached.addserver.php) for detailed explanation
 * of each configuration property.
 */
final class MemcachedServer
{
    /**
     * Default memcached server IP address
     */
    public const DEFAULT_HOST = '127.0.0.1';

    /**
     * Default memcached server port
     */
    public const DEFAULT_PORT = 11211;

    /**
     * @var string memcached server hostname or IP address
     */
    private $host;

    /**
     * @var int memcached server port
     */
    private $port;

    /**
     * @var int probability of using this server among all servers
     */
    private $weight;

    /**
     * @param string $host memcached server hostname or IP address
     * @param int $port memcached server port
     * @param int $weight probability of using this server among all servers
     */
    public function __construct(string $host = self::DEFAULT_HOST, int $port = self::DEFAULT_PORT, int $weight = 1)
    {
        $this->host = $host;
        $this->port = $port;
        $this->weight = $weight;
    }

    /**
     * @return string memcached server hostname or IP address
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int memcached server port
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return int probability of using this server among all servers
     */
    public function getWeight(): int
    {
        return $this->weight;
    }
}
