<?php
namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\WinCache;

/**
 * Class for testing wincache backend.
 * @group wincache
 * @group caching
 */
class WinCacheTest extends CacheTest
{
    public static function setUpBeforeClass()
    {
        if (!extension_loaded('wincache')) {
            self::markTestSkipped('Required extension "wincache" is not loaded');
        }

        if (!ini_get('wincache.ucenabled')) {
            self::markTestSkipped('Wincache user cache disabled. Skipping.');
        }
    }

    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new WinCache());
    }
}
