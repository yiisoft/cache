<?php
namespace Yiisoft\CacheOld\Tests;

use Yiisoft\CacheOld\ApcuCache;
use Yiisoft\CacheOld\Cache;
use Yiisoft\CacheOld\CacheInterface;

/**
 * Class for testing APC cache backend
 * @group apc
 * @group caching
 */
class ApcuCacheTest extends CacheTest
{
    public static function setUpBeforeClass(): void
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
        return new Cache(new ApcuCache());
    }

    public function testExpire(): void
    {
        $this->markTestSkipped('APC keys are expiring only on the next request.');
    }

    public function testExpireAdd(): void
    {
        $this->markTestSkipped('APC keys are expiring only on the next request.');
    }
}
