<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Tests;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\ApcCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;

/**
 * Class for testing APC cache backend.
 * @group apc
 * @group caching
 */
class ApcCacheTest extends CacheTestCase
{
    private $_cacheInstance = null;

    protected static $requiredExtensions = ['apcu'];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        if (!ini_get('apc.enable_cli')) {
            self::markTestSkipped('APC is installed but not enabled. Skipping.');
        }
    }

    /**
     * @dataProvider ordinalCacheProvider
     */
    public function testExpire(\Psr\SimpleCache\CacheInterface $cache)
    {
        $this->markTestSkipped('APC keys are expiring only on the next request.');
    }

    /**
     * @dataProvider ordinalCacheProvider
     */
    public function testExpireAdd(CacheInterface $cache)
    {
        $this->markTestSkipped('APC keys are expiring only on the next request.');
    }

    /**
     * @inheritdoc
     */
    protected function createCacheInstance(): PsrCacheInterface
    {
        return new ApcCache();
    }
}
