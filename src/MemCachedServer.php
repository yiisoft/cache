<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache;

/**
 * MemCachedServer represents the configuration data for a single memcached server.
 *
 * See [PHP manual](http://php.net/manual/en/memcached.addserver.php) for detailed explanation
 * of each configuration property.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 */
class MemCachedServer
{
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
    public function __construct(string $host, int $port = 11211, int $weight = 1)
    {
        $this->host = $host;
        $this->port = $port;
        $this->weight = $weight;
    }

    /**
     * @return memcached server hostname or IP address
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
