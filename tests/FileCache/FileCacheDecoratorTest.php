<?php

namespace Yiisoft\Cache\Tests\FileCache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\FileCache;

class FileCacheDecoratorTest extends FileCacheTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new FileCache(static::CACHE_DIRECTORY));
    }
}
