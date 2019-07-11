<?php
namespace Yiisoft\Cache\Tests;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;

/**
 * Class for testing file cache backend.
 * @group caching
 */
class ArrayCacheTest extends CacheTestCase
{
    private $_cacheInstance = null;

    /**
     * @return Cache
     */
    protected function getCacheInstance()
    {
        if ($this->_cacheInstance === null) {
            $this->_cacheInstance = new Cache(new ArrayCache());
        }

        return $this->_cacheInstance;
    }

    /**
     * @dataProvider ordinalCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testExpire(PsrCacheInterface $cache)
    {
        static::$microtime = \microtime(true);
        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));
        static::$microtime++;
        $this->assertEquals('expire_test', $cache->get('expire_test'));
        static::$microtime++;
        $this->assertNull($cache->get('expire_test'));
    }

    /**
     * @dataProvider cacheIntegrationProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testExpireAdd(CacheInterface $cache)
    {
        static::$microtime = \microtime(true);
        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        static::$microtime++;
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        static::$microtime++;
        $this->assertNull($cache->get('expire_testa'));
    }

    /**
     * Factory method to create particular implementation. Called once per test
     */
    protected function createCacheInstance(): PsrCacheInterface
    {
        return new ArrayCache();
    }
}
