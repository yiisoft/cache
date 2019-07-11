<?php
namespace Yiisoft\Cache;

/**
 * Mock for the time() function for caching classes.
 * @return int
 */
function time(): int
{
    return \Yiisoft\Cache\Tests\CacheTest::$time ?: \time();
}

/**
 * Mock for the microtime() function for caching classes.
 * @param bool $float
 * @return float
 */
function microtime(bool $float = false): float
{
    return \Yiisoft\Cache\Tests\CacheTest::$microtime ?: \microtime($float);
}

namespace Yiisoft\Cache\Tests;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependencies\TagDependency;

/**
 * Base class for testing cache backends.
 */
abstract class CacheTest extends TestCase
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

    protected function tearDown(): void
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

    public function testSet(): void
    {
        $cache = $this->createCacheInstance();
        for ($i = 0; $i < 2; $i++) {
            $this->assertTrue($cache->set('string_test', 'string_test'));
            $this->assertTrue($cache->set('number_test', 42));
            $this->assertTrue($cache->set('array_test', ['array_test' => 'array_test']));
        }
    }

    public function testGet(): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

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

    public function testHas(): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        $this->assertTrue($cache->has('string_test'));
        // check whether exists affects the value
        $this->assertEquals('string_test', $cache->get('string_test'));

        $this->assertTrue($cache->has('number_test'));
        $this->assertFalse($cache->has('not_exists'));
    }

    public function testGetNonExistent(): void
    {
        $cache = $this->createCacheInstance();
        $this->assertNull($cache->get('non_existent_key'));
    }

    public function testStoreSpecialValues(): void
    {
        $cache = $this->createCacheInstance();

        $this->assertTrue($cache->set('null_value', null));
        $this->assertNull($cache->get('null_value'));

        $this->assertTrue($cache->set('bool_value', true));
        $this->assertTrue($cache->get('bool_value'));
    }

    public function testGetMultiple(): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        $this->assertEquals(['string_test' => 'string_test', 'number_test' => 42], $cache->getMultiple(['string_test', 'number_test']));
        // ensure that order does not matter
        $this->assertEquals(['number_test' => 42, 'string_test' => 'string_test'], $cache->getMultiple(['number_test', 'string_test']));
        $this->assertEquals(['number_test' => 42, 'non_existent_key' => null], $cache->getMultiple(['number_test', 'non_existent_key']));
    }


    public function testExpire(): void
    {
        $cache = $this->createCacheInstance();

        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));
        usleep(500000);
        $this->assertEquals('expire_test', $cache->get('expire_test'));
        usleep(2500000);
        $this->assertNull($cache->get('expire_test'));
    }

    public function testDelete(): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        $this->assertEquals(42, $cache->get('number_test'));
        $this->assertTrue($cache->delete('number_test'));
        $this->assertNull($cache->get('number_test'));
    }

    public function testClear(): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        $this->assertTrue($cache->clear());
        $this->assertNull($cache->get('number_test'));
    }

    public function testExpireAdd(): void
    {
        $cache = $this->createCacheInstance();

        $this->assertTrue($cache->add('expire_testa', 'expire_testa', 2));
        usleep(500000);
        $this->assertEquals('expire_testa', $cache->get('expire_testa'));
        usleep(2500000);
        $this->assertNull($cache->get('expire_testa'));
    }

    public function testAdd(): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        // should not change existing keys
        $this->assertEquals(42, $cache->get('number_test'));
        $this->assertFalse($cache->add('number_test', 13));


        // should store data if it's not there yet
        $this->assertNull($cache->get('add_test'));
        $this->assertTrue($cache->add('add_test', 13));
        $this->assertEquals(13, $cache->get('add_test'));
    }

    public function testAddMultiple(): void
    {
        $cache = $this->createCacheInstance();
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

    public function testGetOrSet(): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        $expected = get_class($cache);

        $this->assertEquals(null, $cache->get('something'));
        $this->assertEquals($expected, $cache->getOrSet('something', static function (CacheInterface $cache): string {
            return get_class($cache);
        }));
        $this->assertEquals($expected, $cache->get('something'));
    }

    public function testGetOrSetWithDependencies(): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        $dependency = new TagDependency('test');

        $expected = 'SilverFire';
        $loginClosure = static function (CacheInterface $cache) use (&$login): string {
            return 'SilverFire';
        };
        $this->assertEquals($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));

        // Call again with another login to make sure that value is cached
        $loginClosure = static function (CacheInterface $cache) use (&$login): string {
            return 'SamDark';
        };
        $got = $cache->getOrSet('some-login', $loginClosure, null, $dependency);
        $this->assertEquals($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));

        TagDependency::invalidate($cache, 'test');
        $expected = 'SamDark';
        $this->assertEquals($expected, $cache->getOrSet('some-login', $loginClosure, null, $dependency));
    }
}
