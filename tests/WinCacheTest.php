<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Cache;
use Yiisoft\Cache\WinCache;

/**
 * Class for testing wincache backend.
 * @group wincache
 * @group caching
 */
class WinCacheTest extends CacheTestCase
{
    private $_cacheInstance = null;

    protected static $required_extensions = ['wincache'];

    /**
     * @return Cache
     */
    protected function getCacheInstance()
    {
        if (!ini_get('wincache.ucenabled')) {
            $this->markTestSkipped('Wincache user cache disabled. Skipping.');
        }

        if ($this->_cacheInstance === null) {
            $this->_cacheInstance = new Cache(new WinCache());
        }

        return $this->_cacheInstance;
    }
}
