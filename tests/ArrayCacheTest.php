<?php
namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;

/**
 * Class for testing file cache backend
 * @group caching
 */
class ArrayCacheTest extends CacheTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new ArrayCache());
    }

    public function testExpire(): void
    {
        $cache = $this->createCacheInstance();

        static::$time = \time();
        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));
        static::$time++;
        $this->assertEquals('expire_test', $cache->get('expire_test'));
        static::$time++;
        $this->assertNull($cache->get('expire_test'));
    }

    public function testExpireAdd(): void
    {
        $cache = $this->createCacheInstance();

        static::$time = \time();
        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        static::$time++;
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        static::$time++;
        $this->assertNull($cache->get('expire_testa'));
    }
}
