<?php


namespace Yiisoft\Cache\Tests\ArrayCache;


use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Tests\BaseTest;

class ArrayCacheTest extends BaseTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new ArrayCache();
    }

    public function testExpire(): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        static::$time = \time();
        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));

        static::$time++;
        $this->assertTrue($cache->has('expire_test'));
        $this->assertSameExceptObject('expire_test', $cache->get('expire_test'));

        static::$time++;
        $this->assertFalse($cache->has('expire_test'));
        $this->assertNull($cache->get('expire_test'));
    }
}
