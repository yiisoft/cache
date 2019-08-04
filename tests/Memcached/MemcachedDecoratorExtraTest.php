<?php


namespace Yiisoft\Cache\Tests\Memcached;


use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Memcached;
use Yiisoft\Cache\MemcachedServer;
use Yiisoft\Cache\Tests\DecoratorExtraBaseTest;

class MemcachedDecoratorExtraTest extends DecoratorExtraBaseTest
{
    protected function createCacheInstance(): CacheInterface
    {
        $cache = new Cache(new Memcached());
        $cache->addServer(new MemcachedServer('127.0.0.1'));
        return $cache;
    }
}
