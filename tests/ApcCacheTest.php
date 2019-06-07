<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Tests;

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

    protected static $required_extensions = ['apcu'];

    /**
     * @return Cache
     */
    protected function getCacheInstance()
    {
        if (!ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC is installed but not enabled. Skipping.');
        }

        if ($this->_cacheInstance === null) {
            $this->_cacheInstance = new Cache(new ApcCache());
        }

        return $this->_cacheInstance;
    }

    /**
     * @dataProvider ordinalCacheProvider
     */
    public function testExpire(CacheInterface $cache)
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
}
