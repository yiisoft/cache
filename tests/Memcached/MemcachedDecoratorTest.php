<?php


namespace Yiisoft\Cache\Tests\Memcached;


use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Memcached;
use Yiisoft\Cache\MemcachedServer;
use Yiisoft\Cache\Tests\BaseTest;

class MemcachedDecoratorTest extends BaseTest
{
    protected function createCacheInstance(): CacheInterface
    {
        $cache = new Cache(new Memcached());
        $cache->addServer(new MemcachedServer('127.0.0.1'));
        return $cache;
    }
}
