<?php
namespace Yiisoft\Cache\Tests;

use Psr\Log\NullLogger;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\FileCache;

/**
 * Class for testing file cache backend.
 * @group caching
 */
class FileCacheTest extends CacheTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new FileCache(__DIR__ . '/runtime/cache', new NullLogger()));
    }

    public function testExpire(): void
    {
        $cache = $this->createCacheInstance();

        static::$time = \time();
        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));
        static::$time++;
        $this->assertEquals('expire_test', $cache->get('expire_test'));
        static::$time++;
        $this->assertNull($cache->get('expire_test'));
    }

    public function testExpireAdd(): void
    {
        $cache = $this->createCacheInstance();

        static::$time = \time();
        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        static::$time++;
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        static::$time++;
        $this->assertNull($cache->get('expire_testa'));
    }

    public function testCacheRenewalOnDifferentOwnership(): void
    {
        $TRAVIS_SECOND_USER = getenv('TRAVIS_SECOND_USER');
        if (empty($TRAVIS_SECOND_USER)) {
            $this->markTestSkipped('Travis second user not found');
        }

        $cache = $this->createCacheInstance();

        $cacheValue = uniqid('value_');
        $cachePublicKey = uniqid('key_');
        $cacheInternalKey = $this->invokeMethod($cache, 'buildKey', [$cachePublicKey]);

        static::$time = \time();
        $this->assertTrue($cache->set($cachePublicKey, $cacheValue, 2));
        $this->assertSame($cacheValue, $cache->get($cachePublicKey));

        $refClass = new \ReflectionClass($cache->getHandler());
        $refMethodGetCacheFile = $refClass->getMethod('getCacheFile');
        $refMethodGetCacheFile->setAccessible(true);
        $cacheFile = $refMethodGetCacheFile->invoke($cache->handler, $cacheInternalKey);
        $refMethodGetCacheFile->setAccessible(false);

        $output = array();
        $returnVar = null;
        exec(sprintf('sudo chown %s %s',
            escapeshellarg($TRAVIS_SECOND_USER),
            escapeshellarg($cacheFile)
        ), $output, $returnVar);

        $this->assertSame(0, $returnVar, 'Cannot change ownership of cache file to test cache renewal');

        $this->assertTrue($cache->set($cachePublicKey, uniqid('value_2_'), 2), 'Cannot rebuild cache on different file ownership');
    }
}
