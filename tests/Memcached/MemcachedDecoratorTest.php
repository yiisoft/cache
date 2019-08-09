<?php


namespace Yiisoft\Cache\Tests\Memcached;


use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Memcached;
use Yiisoft\Cache\Tests\BaseTest;

class MemcachedDecoratorTest extends BaseTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new Memcached());
    }
}
