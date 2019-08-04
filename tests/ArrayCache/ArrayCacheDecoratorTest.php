<?php


namespace Yiisoft\Cache\Tests\ArrayCache;


use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Tests\BaseTest;

class ArrayCacheDecoratorTest extends BaseTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new ArrayCache());
    }
}
