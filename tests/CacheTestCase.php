<?php
namespace Yiisoft\Cache;

/**
 * Mock for the time() function for caching classes.
 * @return int
 */
function time()
{
    return \Yiisoft\Cache\Tests\CacheTestCase::$time ?: \time();
}

/**
 * Mock for the microtime() function for caching classes.
 * @param bool $float
 * @return float
 */
function microtime($float = false)
{
    return \Yiisoft\Cache\Tests\CacheTestCase::$microtime ?: \microtime($float);
}

namespace Yiisoft\Cache\Tests;

use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\Cache;

/**
 * Base class for testing cache backends.
 */
abstract class CacheTestCase extends TestCase
{
    use IntegrationTestTrait;
    /**
     * @var int virtual time to be returned by mocked time() function.
     * Null means normal time() behavior.
     */
    public static $time;
    /**
     * @var float virtual time to be returned by mocked microtime() function.
     * Null means normal microtime() behavior.
     */
    public static $microtime;

    /**
     * List of extensions, that underlying cache requires
     * If one of extensions is missing - skip test
     * @var string[]
     */
    protected static $requiredExtensions = [];

    /**
     * An instance that is reused during the whole test
     */
    private $testScopeInstance;

    /**
     * Factory method to create particular implementation. Called once per test
     */
    protected abstract function createCacheInstance() : PsrCacheInterface;

    protected function tearDown()
    {
        static::$time = null;
        static::$microtime = null;
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        foreach (static::$requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                self::markTestSkipped("Required extension '{$extension}' is not loaded");
            }
        }
    }

    protected function getTestScopeInstance()
    {
        return $this->testScopeInstance ?? $this->testScopeInstance = $this->createCacheInstance();
    }

    /**
     * This function configures given cache to match some expectations
     */
    public function prepare(PsrCacheInterface $cache) : PsrCacheInterface
    {
        $this->assertTrue($cache->clear());
        $this->assertTrue($cache->set('string_test', 'string_test'));
        $this->assertTrue($cache->set('number_test', 42));
        $this->assertTrue($cache->set('array_test', ['array_test' => 'array_test']));

        return $cache;
    }

    public function preparedCacheProvider()
    {
        return [
            [$this->prepare($this->getTestScopeInstance())],
        ];
    }

    public function ordinalCacheProvider()
    {
        return [
            [$this->getTestScopeInstance()],
        ];
    }

    /**
     * @dataProvider ordinalCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testSet(PsrCacheInterface $cache)
    {
        for ($i = 0; $i < 2; $i++) {
            $this->assertTrue($cache->set('string_test', 'string_test'));
            $this->assertTrue($cache->set('number_test', 42));
            $this->assertTrue($cache->set('array_test', ['array_test' => 'array_test']));
        }
    }

    /**
     * @dataProvider preparedCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGet(PsrCacheInterface $cache)
    {
        $this->assertEquals('string_test', $cache->get('string_test'));
        $this->assertEquals(42, $cache->get('number_test'));

        $array = $cache->get('array_test');
        $this->assertArrayHasKey('array_test', $array);
        $this->assertEquals('array_test', $array['array_test']);
    }

    /**
     * @return array testing multiSet with and without expiry
     */
    public function dataProviderSetMultiple()
    {
        return [[0], [2]];
    }

    /**
     * @dataProvider dataProviderSetMultiple
     * @param int $expiry
     */
    public function testSetMultiple($expiry)
    {
        $cache = new Cache($this->getTestScopeInstance(), new NullLogger());
        $cache->clear();

        $cache->setMultiple(
            [
                'string_test' => 'string_test',
                'number_test' => 42,
                'array_test' => ['array_test' => 'array_test'],
            ],
            $expiry
        );

        $this->assertEquals('string_test', $cache->get('string_test'));

        $this->assertEquals(42, $cache->get('number_test'));

        $array = $cache->get('array_test');
        $this->assertArrayHasKey('array_test', $array);
        $this->assertEquals('array_test', $array['array_test']);
    }

    /**
     * @dataProvider preparedCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testHas(PsrCacheInterface $cache)
    {
        $this->assertTrue($cache->has('string_test'));
        // check whether exists affects the value
        $this->assertEquals('string_test', $cache->get('string_test'));

        $this->assertTrue($cache->has('number_test'));
        $this->assertFalse($cache->has('not_exists'));
    }

    /**
     * @dataProvider ordinalCacheProvider
     */
    public function testGetNonExistent(PsrCacheInterface $cache)
    {
        $this->assertNull($cache->get('non_existent_key'));
    }

    /**
     * @dataProvider ordinalCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testStoreSpecialValues(PsrCacheInterface $cache)
    {
        $this->assertTrue($cache->set('null_value', null));
        $this->assertNull($cache->get('null_value'));

        $this->assertTrue($cache->set('bool_value', true));
        $this->assertTrue($cache->get('bool_value'));
    }

    /**
     * @dataProvider preparedCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetMultiple(PsrCacheInterface $cache)
    {
        $this->assertEquals(['string_test' => 'string_test', 'number_test' => 42], $cache->getMultiple(['string_test', 'number_test']));
        // ensure that order does not matter
        $this->assertEquals(['number_test' => 42, 'string_test' => 'string_test'], $cache->getMultiple(['number_test', 'string_test']));
        $this->assertEquals(['number_test' => 42, 'non_existent_key' => null], $cache->getMultiple(['number_test', 'non_existent_key']));
    }

    /**
     * @dataProvider ordinalCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testExpire(PsrCacheInterface $cache)
    {
        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));
        usleep(500000);
        $this->assertEquals('expire_test', $cache->get('expire_test'));
        usleep(2500000);
        $this->assertNull($cache->get('expire_test'));
    }

    /**
     * @dataProvider preparedCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testDelete(PsrCacheInterface $cache)
    {
        $this->assertEquals(42, $cache->get('number_test'));
        $this->assertTrue($cache->delete('number_test'));
        $this->assertNull($cache->get('number_test'));
    }

    /**
     * @dataProvider preparedCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testClear(PsrCacheInterface $cache)
    {
        $this->assertTrue($cache->clear());
        $this->assertNull($cache->get('number_test'));
    }

    public function getOrSetCallable($cache)
    {
        return get_class($cache);
    }
}
