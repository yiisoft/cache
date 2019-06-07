<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;

/**
 * Class for testing file cache backend.
 * @group caching
 */
class ArrayCacheTest extends CacheTestCase
{
    private $_cacheInstance = null;

    /**
     * @return Cache
     */
    protected function getCacheInstance()
    {
        if ($this->_cacheInstance === null) {
            $this->_cacheInstance = new Cache(new ArrayCache());
        }

        return $this->_cacheInstance;
    }

    /**
     * @dataProvider ordinalCacheProvider
     */
    public function testExpire(CacheInterface $cache)
    {
        static::$microtime = \microtime(true);
        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));
        static::$microtime++;
        $this->assertEquals('expire_test', $cache->get('expire_test'));
        static::$microtime++;
        $this->assertNull($cache->get('expire_test'));
    }

    /**
     * @dataProvider ordinalCacheProvider
     */
    public function testExpireAdd(CacheInterface $cache)
    {
        $cache = $this->getCacheInstance();

        static::$microtime = \microtime(true);
        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        static::$microtime++;
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        static::$microtime++;
        $this->assertNull($cache->get('expire_testa'));
    }
}
