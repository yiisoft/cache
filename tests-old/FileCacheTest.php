<?php
namespace Yiisoft\CacheOld\Tests;

use Psr\Log\NullLogger;
use Yiisoft\CacheOld\Cache;
use Yiisoft\CacheOld\CacheInterface;
use Yiisoft\CacheOld\FileCache;
use phpmock\phpunit\PHPMock;

/**
 * Class for testing file cache backend.
 * @group caching
 */
class FileCacheTest extends CacheTest
{
    use PHPMock;

    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new FileCache(__DIR__ . '/runtime/cache', new NullLogger()));
    }

    public function testExpire(): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

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
        $cache->clear();

        static::$time = \time();
        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        static::$time++;
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        static::$time++;
        $this->assertNull($cache->get('expire_testa'));
    }

    /**
     * We have to on separate process because of PHPMock not being able to mock a function that
     * was already called.
     * @runInSeparateProcess
     */
    public function testCacheRenewalOnDifferentOwnership(): void
    {
        if (!function_exists('posix_geteuid')) {
            $this->markTestSkipped('Can not test without posix extension installed.');
        }

        $cache = $this->createCacheInstance();
        $cache->clear();

        $cacheValue = uniqid('value_', false);
        $cacheKey = uniqid('key_', false);

        static::$time = \time();
        $this->assertTrue($cache->set($cacheKey, $cacheValue, 2));
        $this->assertSame($cacheValue, $cache->get($cacheKey));

        // Override fileowner method so it always returns something not equal to the current user
        $notCurrentEuid = posix_geteuid() + 15;
        $this->getFunctionMock('Yiisoft\CacheOld', 'fileowner')->expects($this->any())->willReturn($notCurrentEuid);
        $this->getFunctionMock('Yiisoft\CacheOld', 'unlink')->expects($this->once());

        $this->assertTrue($cache->set($cacheKey, uniqid('value_2_', false), 2), 'Cannot rebuild cache on different file ownership');
    }
}
