<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\cache\tests\unit;

use phpmock\phpunit\PHPMock;
use yii\cache\Cache;
use yii\cache\FileCache;

/**
 * Class for testing file cache backend.
 * @group caching
 */
class FileCacheTest extends CacheTestCase
{
    use PHPMock;
    private $_cacheInstance = null;

    /**
     * @return Cache
     */
    protected function getCacheInstance()
    {
        if ($this->_cacheInstance === null) {
            $this->_cacheInstance = new Cache(new FileCache('@yii/tests/runtime/cache'));
        }

        return $this->_cacheInstance;
    }

    public function testExpire()
    {
        $cache = $this->getCacheInstance();

        static::$time = \time();
        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));
        static::$time++;
        $this->assertEquals('expire_test', $cache->get('expire_test'));
        static::$time++;
        $this->assertNull($cache->get('expire_test'));
    }

    public function testExpireAdd()
    {
        $cache = $this->getCacheInstance();

        static::$time = \time();
        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        static::$time++;
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        static::$time++;
        $this->assertNull($cache->get('expire_testa'));
    }

    public function testCacheRenewalOnDifferentOwnership()
    {
        $cache = $this->getCacheInstance();

        $cacheValue = uniqid('value_');
        $cachePublicKey = uniqid('key_');
        $cacheInternalKey = $this->invokeMethod($cache, 'buildKey', [$cachePublicKey]);

        static::$time = \time();
        $this->assertTrue($cache->set($cachePublicKey, $cacheValue, 2));
        $this->assertSame($cacheValue, $cache->get($cachePublicKey));

        $refClass = new \ReflectionClass($cache->handler);
        $refMethodGetCacheFile = $refClass->getMethod('getCacheFile');
        $refMethodGetCacheFile->setAccessible(true);
        $cacheFile = $refMethodGetCacheFile->invoke($cache->handler, $cacheInternalKey);
        $refMethodGetCacheFile->setAccessible(false);

        // Override fileowner method so it always returns something not equal to the current user.
        $this->getFunctionMock('yii\cache', 'fileowner')->expects($this->any())->willReturn(posix_geteuid() + 15);

        $this->getFunctionMock('yii\cache', 'unlink')->expects($this->once());

        $output = array();
        $returnVar = null;
        $this->assertTrue($cache->set($cachePublicKey, uniqid('value_2_'), 2), 'Cannot rebuild cache on different file ownership');
    }
}
