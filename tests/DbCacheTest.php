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
use Yiisoft\Cache\DbCache;

/**
 * Class for testing file cache backend.
 * @group db
 * @group caching
 */
class DbCacheTest extends CacheTestCase
{
    private $_cacheInstance;
    private $_connection;

    protected static $required_extensions = ['pdo', 'pdo_mysql'];


    protected function setUp()
    {
        parent::setUp();

        $this->getConnection()->createCommand('
            CREATE TABLE IF NOT EXISTS cache (
                id char(128) NOT NULL,
                expire int(11) DEFAULT NULL,
                data LONGBLOB,
                PRIMARY KEY (id),
                KEY expire (expire)
            );
        ')->execute();
    }

    protected function tearDown()
    {
        $this->getConnection()->createCommand('DROP TABLE IF EXISTS cache')->execute();
        parent::tearDown();
    }

    /**
     * @param  bool            $reset whether to clean up the test database
     * @return \Yiisoft\Db\Connection
     */
    public function getConnection($reset = true)
    {
        if ($this->_connection === null) {
            $databases = self::getParam('databases');
            $params = $databases['mysql'];
            $db = new \Yiisoft\Db\Connection();
            $db->dsn = $params['dsn'];
            $db->username = $params['username'];
            $db->password = $params['password'];
            if ($reset) {
                $db->open();
                $lines = explode(';', file_get_contents($params['fixture']));
                foreach ($lines as $line) {
                    if (trim($line) !== '') {
                        $db->pdo->exec($line);
                    }
                }
            }
            $this->_connection = $db;
        }

        return $this->_connection;
    }

    /**
     * @dataProvider ordinalCacheProvider
     */
    public function testExpire(\Psr\SimpleCache\CacheInterface $cache)
    {
        static::$time = \time();
        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));
        static::$time++;
        $this->assertEquals('expire_test', $cache->get('expire_test'));
        static::$time++;
        $this->assertNull($cache->get('expire_test'));
    }

    /**
     * @dataProvider ordinalCacheProvider
     */
    public function testExpireAdd(CacheInterface $cache)
    {
        static::$time = \time();
        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        static::$time++;
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        static::$time++;
        $this->assertNull($cache->get('expire_testa'));
    }

    public function testSynchronousSetWithTheSameKey()
    {
        $KEY = 'sync-test-key';
        $VALUE = 'sync-test-value';

        $cache = $this->getCacheInstance();
        static::$time = \time();

        $this->assertTrue($cache->set($KEY, $VALUE, 60));
        $this->assertTrue($cache->set($KEY, $VALUE, 60));

        $this->assertEquals($VALUE, $cache->get($KEY));
    }

    /**
     * Factory method to create particular implementation. Called once per test
     */
    protected function createCacheInstance(): PsrCacheInterface
    {
        return new DbCache(null, $this->getConnection());
    }
}
