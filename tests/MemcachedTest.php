<?php

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Memcached;
use Yiisoft\Cache\MemcachedServer;

/**
 * Class for testing memcached cache backend.
 * If memcached server is not running on localhost at port 11211 set environment variables MEMCACHED_HOST and
 * MEMCACHED_PORT to use different host and port of the memcached server in the tests
 * @group memcached
 * @group caching
 */
class MemcachedTest extends CacheTest
{
    public static $MEMCACHED_HOST;

    public static $MEMCACHED_PORT;

    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('memcached')) {
            self::markTestSkipped('Required extension "memcached" is not loaded');
        }

        // check whether memcached is running and skip tests if not.
        static::$MEMCACHED_HOST = getenv('MEMCACHED_HOST') ?: MemcachedServer::DEFAULT_HOST;
        static::$MEMCACHED_PORT = getenv('MEMCACHED_PORT') ?: MemcachedServer::DEFAULT_PORT;
        if (!@stream_socket_client(static::$MEMCACHED_HOST . ':' . static::$MEMCACHED_PORT, $errorNumber, $errorDescription, 0.5)) {
            self::markTestSkipped('No memcached server running at ' . static::$MEMCACHED_HOST . ':' . static::$MEMCACHED_PORT . ' : ' . $errorNumber . ' - ' . $errorDescription);
        }
    }

    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new Memcached(null, [
            new MemcachedServer(static::$MEMCACHED_HOST, static::$MEMCACHED_PORT),
        ]));
    }

    public function testExpire(): void
    {
        if (getenv('TRAVIS') === 'true') {
            $this->markTestSkipped('Can not reliably test memcached expiry on travis-ci.');
        }
        parent::testExpire();
    }

    public function testExpireAdd(): void
    {
        if (getenv('TRAVIS') === 'true') {
            $this->markTestSkipped('Can not reliably test memcached expiry on travis-ci.');
        }
        parent::testExpireAdd();
    }
}
