<?php

namespace Yiisoft\Cache\Tests\ArrayCache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ApcuCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Tests\ApcuCache\ApcuCacheTest;

class ApcuCacheDecoratorTest extends ApcuCacheTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new ApcuCache());
    }
}
