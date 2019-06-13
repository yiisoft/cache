<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Cache\Tests;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\WinCache;

/**
 * Class for testing wincache backend.
 * @group wincache
 * @group caching
 */
class WinCacheTest extends CacheTestCase
{
    protected static $required_extensions = ['wincache'];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        if (!ini_get('wincache.ucenabled')) {
            self::markTestSkipped('Wincache user cache disabled. Skipping.');
        }
    }


    /**
     * @inheritdoc
     */
    protected function createCacheInstance(): PsrCacheInterface
    {
        return new WinCache();
    }
}
