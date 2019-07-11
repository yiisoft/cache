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

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependencies\TagDependency;

/**
 * Base class for testing cache backends.
 */
abstract class CacheTestCase extends TestCase
{
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

    abstract protected function createCacheInstance(): CacheInterface;

    protected function tearDown()
    {
        static::$time = null;
        static::$microtime = null;
    }

    /**
     * This function configures given cache to match some expectations
     */
    public function prepare(CacheInterface $cache): CacheInterface
    {
        $this->assertTrue($cache->clear());
        $this->assertTrue($cache->set('string_test', 'string_test'));
        $this->assertTrue($cache->set('number_test', 42));
        $this->assertTrue($cache->set('array_test', ['array_test' => 'array_test']));

        return $cache;
    }

    public function preparedCacheProvider(): array
    {
        return [
            [$this->prepare($this->createCacheInstance())],
        ];
    }

    public function cacheProvider(): array
    {
        return [
            [$this->createCacheInstance()],
        ];
    }

    /**
     * @dataProvider cacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testSet(PsrCacheInterface $cache): void
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
    public function testGet(PsrCacheInterface $cache): void
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
    public function dataProviderSetMultiple(): array
    {
        return [[0], [2]];
    }

    /**
     * @dataProvider dataProviderSetMultiple
     * @param int $expiry
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testSetMultiple(int $expiry): void
    {
        $cache = $this->createCacheInstance();
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
    public function testHas(PsrCacheInterface $cache): void
    {
        $this->assertTrue($cache->has('string_test'));
        // check whether exists affects the value
        $this->assertEquals('string_test', $cache->get('string_test'));

        $this->assertTrue($cache->has('number_test'));
        $this->assertFalse($cache->has('not_exists'));
    }

    /**
     * @dataProvider cacheProvider
     */
    public function testGetNonExistent(PsrCacheInterface $cache): void
    {
        $this->assertNull($cache->get('non_existent_key'));
    }

    /**
     * @dataProvider cacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testStoreSpecialValues(PsrCacheInterface $cache): void
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
    public function testGetMultiple(PsrCacheInterface $cache): void
    {
        $this->assertEquals(['string_test' => 'string_test', 'number_test' => 42], $cache->getMultiple(['string_test', 'number_test']));
        // ensure that order does not matter
        $this->assertEquals(['number_test' => 42, 'string_test' => 'string_test'], $cache->getMultiple(['number_test', 'string_test']));
        $this->assertEquals(['number_test' => 42, 'non_existent_key' => null], $cache->getMultiple(['number_test', 'non_existent_key']));
    }

    /**
     * @dataProvider cacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testExpire(PsrCacheInterface $cache): void
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
    public function testDelete(PsrCacheInterface $cache): void
    {
        $this->assertEquals(42, $cache->get('number_test'));
        $this->assertTrue($cache->delete('number_test'));
        $this->assertNull($cache->get('number_test'));
    }

    /**
     * @dataProvider preparedCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testClear(PsrCacheInterface $cache): void
    {
        $this->assertTrue($cache->clear());
        $this->assertNull($cache->get('number_test'));
    }

    /**
     * Returns data for integration test of Cache class
     */
    public function cacheIntegrationProvider(): array
    {
        return [
            [new Cache($this->createCacheInstance())],
        ];
    }

    /**
     * Returns data for integration test, prepared
     */
    public function preparedIntegrationCacheProvider(): array
    {
        return [
            [$this->prepare(new Cache($this->createCacheInstance()))]
        ];
    }

    /**
     * @dataProvider cacheIntegrationProvider
     */
    public function testArrayAccess(CacheInterface $cache): void
    {
        $cache['arrayaccess_test'] = new \stdClass();
        $this->assertInstanceOf('stdClass', $cache['arrayaccess_test']);
    }

    /**
     * @dataProvider cacheIntegrationProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testExpireAdd(CacheInterface $cache): void
    {
        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        usleep(500000);
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        usleep(2500000);
        $this->assertNull($cache->get('expire_testa'));
    }

    /**
     * @dataProvider preparedIntegrationCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testAdd(CacheInterface $cache): void
    {
        // should not change existing keys
        $this->assertEquals(42, $cache->get('number_test'));
        $this->assertFalse($cache->add('number_test', 13));


        // should store data if it's not there yet
        $this->assertNull($cache->get('add_test'));
        $this->assertTrue($cache->add('add_test', 13));
        $this->assertEquals(13, $cache->get('add_test'));
    }

    /**
     * @dataProvider cacheIntegrationProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testAddMultiple(CacheInterface $cache): void
    {
        /** @var CacheInterface $cache */
        $cache = $this->prepare($cache);
        $this->assertNull($cache->get('add_test'));

        $this->assertTrue(
            $cache->addMultiple(
                [
                    'number_test' => 13,
                    'add_test' => 13,
                ]
            )
        );

        $this->assertEquals(42, $cache->get('number_test'));
        $this->assertEquals(13, $cache->get('add_test'));
    }

    /**
     * @dataProvider preparedIntegrationCacheProvider
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetOrSet(CacheInterface $cache): void
    {
        $expected = get_class($cache);

        $this->assertEquals(null, $cache->get('something'));
        $this->assertEquals($expected, $cache->getOrSet('something', static function (CacheInterface $cache) {
            return get_class($cache);
        }));
        $this->assertEquals($expected, $cache->get('something'));
    }

    /**
     * @dataProvider preparedIntegrationCacheProvider
     */
    public function testGetOrSetWithDependencies(PsrCacheInterface $cache): void
    {
        $cache = new Cache($cache);
        $dependency = new TagDependency('test');

        $expected = 'SilverFire';
        $loginClosure = function ($cache) use (&$login) {
            return 'SilverFire';
        };
        $this->assertEquals($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));

        // Call again with another login to make sure that value is cached
        $loginClosure = function ($cache) use (&$login) {
            return 'SamDark';
        };
        $got = $cache->getOrSet('some-login', $loginClosure, null, $dependency);
        $this->assertEquals($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));

        $dependency->invalidate($cache, 'test');
        $expected = 'SamDark';
        $this->assertEquals($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));
    }
}
