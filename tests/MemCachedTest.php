<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Tests;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\MemCached;

/**
 * Class for testing memcached cache backend.
 * @group memcached
 * @group caching
 */
class MemCachedTest extends CacheTestCase
{
    protected static $requiredExtensions = ['memcached'];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // check whether memcached is running and skip tests if not.
        if (!@stream_socket_client('127.0.0.1:11211', $errorNumber, $errorDescription, 0.5)) {
            self::markTestSkipped('No memcached server running at ' . '127.0.0.1:11211' . ' : ' . $errorNumber . ' - ' . $errorDescription);
        }
    }

    /**
     * @dataProvider ordinalCacheProvider
     */
    public function testExpire(\Psr\SimpleCache\CacheInterface $cache)
    {
        if (getenv('TRAVIS') == 'true') {
            $this->markTestSkipped('Can not reliably test memcached expiry on travis-ci.');
        }
        parent::testExpire($cache);
    }

    /**
     * @dataProvider cacheIntegrationProvider
     */
    public function testExpireAdd(CacheInterface $cache)
    {
        if (getenv('TRAVIS') == 'true') {
            $this->markTestSkipped('Can not reliably test memcached expiry on travis-ci.');
        }
        parent::testExpireAdd($cache);
    }

    /**
     * Factory method to create particular implementation. Called once per test
     */
    protected function createCacheInstance(): PsrCacheInterface
    {
        return new MemCached();
    }
}
