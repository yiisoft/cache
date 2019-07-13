<?php
namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Memcached;

/**
 * Class for testing memcached cache backend.
 * @group memcached
 * @group caching
 */
class MemcachedTest extends CacheTest
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('memcached')) {
            self::markTestSkipped('Required extension "memcached" is not loaded');
        }

        // check whether memcached is running and skip tests if not.
        if (!@stream_socket_client('127.0.0.1:11211', $errorNumber, $errorDescription, 0.5)) {
            self::markTestSkipped('No memcached server running at ' . '127.0.0.1:11211' . ' : ' . $errorNumber . ' - ' . $errorDescription);
        }
    }

    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new Memcached());
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
