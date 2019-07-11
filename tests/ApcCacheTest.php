<?php
namespace Yiisoft\Cache\Tests;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\ApcCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;

/**
 * Class for testing APC cache backend
 * @group apc
 * @group caching
 */
class ApcCacheTest extends CacheTest
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('apcu')) {
            self::markTestSkipped('Required extension "apcu" is not loaded');
        }

        if (!ini_get('apc.enable_cli')) {
            self::markTestSkipped('APC is installed but not enabled. Enable with "apc.enable_cli" from php.ini. Skipping.');
        }
    }

    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new ApcCache());
    }

    /**
     * @dataProvider cacheProvider
     */
    public function testExpire(PsrCacheInterface $cache): void
    {
        $this->markTestSkipped('APC keys are expiring only on the next request.');
    }

    /**
     * @dataProvider cacheProvider
     */
    public function testExpireAdd(CacheInterface $cache): void
    {
        $this->markTestSkipped('APC keys are expiring only on the next request.');
    }
}
