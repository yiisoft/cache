<?php

namespace Yiisoft\Cache\Tests\Memcached;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Memcached;

class MemcachedDecoratorTest extends MemcachedTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new Memcached());
    }
}
