<?php

namespace Yiisoft\Cache\Tests\ArrayCache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Tests\WinCache\WinCacheTest;
use Yiisoft\Cache\WinCache;

class WinCacheDecoratorTest extends WinCacheTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new WinCache());
    }
}
