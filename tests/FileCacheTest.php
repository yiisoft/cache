<?php
namespace Yiisoft\Cache\Tests;

use Psr\Log\NullLogger;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\FileCache;
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
        if (!function_exists('posix_geteuid')) {
            $this->markTestSkipped('Can not test on non-POSIX OS.');
        }

        $cache = $this->createCacheInstance();

        $cacheValue = uniqid('value_', false);
        $cachePublicKey = uniqid('key_', false);

        static::$time = \time();
        $this->assertTrue($cache->set($cachePublicKey, $cacheValue, 2));
        $this->assertSame($cacheValue, $cache->get($cachePublicKey));

        // Override fileowner method so it always returns something not equal to the current user
        $notCurrentEuid = function_exists('posix_geteuid') ? posix_geteuid() + 15 : 42;
        $this->getFunctionMock('yii\cache', 'fileowner')->expects($this->any())->willReturn($notCurrentEuid);
        $this->getFunctionMock('yii\cache', 'unlink')->expects($this->once());

        $this->assertTrue($cache->set($cachePublicKey, uniqid('value_2_', false), 2), 'Cannot rebuild cache on different file ownership');
    }
}
