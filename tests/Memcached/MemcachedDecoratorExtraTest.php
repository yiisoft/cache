<?php


namespace Yiisoft\Cache\Tests\Memcached;


use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Memcached;
use Yiisoft\Cache\Tests\DecoratorExtraBaseTest;

class MemcachedDecoratorExtraTest extends DecoratorExtraBaseTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new Memcached());
    }
}
