<?php

namespace Yiisoft\Cache\Tests\ArrayCache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Tests\DecoratorExtraBaseTest;

class ArrayCacheDecoratorExtraTest extends DecoratorExtraBaseTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new ArrayCache());
    }
}
