<?php

namespace Yiisoft\Cache\Tests\ArrayCache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;

class ArrayCacheDecoratorTest extends ArrayCacheTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new ArrayCache());
    }
}
