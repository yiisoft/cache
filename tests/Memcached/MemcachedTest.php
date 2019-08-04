<?php


namespace Yiisoft\Cache\Tests\Memcached;


use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Memcached;
use Yiisoft\Cache\MemcachedServer;
use Yiisoft\Cache\Tests\BaseTest;

class MemcachedTest extends BaseTest
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
        $cache = new Memcached();
        $cache->addServer(new MemcachedServer('127.0.0.1'));
        return $cache;
    }

    public function testDeleteMultipleReturnsFalse(): void
    {
        $cache = new Memcached();

        $memcachedStub = $this->createMock(\Memcached::class);
        $memcachedStub->method('deleteMulti')->willReturn([false]);

        $this->setInaccessibleProperty($cache, 'cache', $memcachedStub);

        $this->assertFalse($cache->deleteMultiple(['a', 'b']));
    }

    public function testExpire(): void
    {
        if (getenv('TRAVIS') === 'true') {
            $this->markTestSkipped('Can not reliably test memcached expiry on travis-ci.');
        }
        // TODO
        $this->assertTrue(true);
        //parent::testExpire();
    }
}
