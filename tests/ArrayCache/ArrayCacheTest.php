<?php

namespace Yiisoft\Cache\Tests\ArrayCache;

require_once __DIR__ . '/../MockHelper.php';

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\MockHelper;
use Yiisoft\Cache\Tests\TestCase;

class ArrayCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        MockHelper::$time = null;
    }

    protected function createCacheInstance(): CacheInterface
    {
        return new ArrayCache();
    }

    public function testExpire(): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        MockHelper::$time = \time();
        $this->assertTrue($cache->set('expire_test', 'expire_test', 2));

        MockHelper::$time++;
        $this->assertTrue($cache->has('expire_test'));
        $this->assertSameExceptObject('expire_test', $cache->get('expire_test'));

        MockHelper::$time++;
        $this->assertFalse($cache->has('expire_test'));
        $this->assertNull($cache->get('expire_test'));
    }

    /**
     * @dataProvider dataProvider
     * @param $key
     * @param $value
     * @throws InvalidArgumentException
     */
    public function testSet($key, $value): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        for ($i = 0; $i < 2; $i++) {
            $this->assertTrue($cache->set($key, $value));
        }
    }

    /**
     * @dataProvider dataProvider
     * @param $key
     * @param $value
     * @throws InvalidArgumentException
     */
    public function testGet($key, $value): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $cache->set($key, $value);
        $valueFromCache = $cache->get($key, 'default');

        $this->assertSameExceptObject($value, $valueFromCache);
    }

    /**
     * @dataProvider dataProvider
     * @param $key
     * @param $value
     * @throws InvalidArgumentException
     */
    public function testValueInCacheCannotBeChanged($key, $value): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $cache->set($key, $value);
        $valueFromCache = $cache->get($key, 'default');

        $this->assertSameExceptObject($value, $valueFromCache);

        if (is_object($value)) {
            $originalValue = clone $value;
            $valueFromCache->test_field = 'changed';
            $value->test_field = 'changed';
            $valueFromCacheNew = $cache->get($key, 'default');
            $this->assertSameExceptObject($originalValue, $valueFromCacheNew);
        }
    }

    /**
     * @dataProvider dataProvider
     * @param $key
     * @param $value
     * @throws InvalidArgumentException
     */
    public function testHas($key, $value): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $cache->set($key, $value);

        $this->assertTrue($cache->has($key));
        // check whether exists affects the value
        $this->assertSameExceptObject($value, $cache->get($key));

        $this->assertTrue($cache->has($key));
        $this->assertFalse($cache->has('not_exists'));
    }

    public function testGetNonExistent(): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $this->assertNull($cache->get('non_existent_key'));
    }

    /**
     * @dataProvider dataProvider
     * @param $key
     * @param $value
     * @throws InvalidArgumentException
     */
    public function testDelete($key, $value): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $cache->set($key, $value);

        $this->assertSameExceptObject($value, $cache->get($key));
        $this->assertTrue($cache->delete($key));
        $this->assertNull($cache->get($key));
    }

    /**
     * @dataProvider dataProvider
     * @param $key
     * @param $value
     * @throws InvalidArgumentException
     */
    public function testClear($key, $value): void
    {
        $cache = $this->createCacheInstance();
        $cache = $this->prepare($cache);

        $this->assertTrue($cache->clear());
        $this->assertNull($cache->get($key));
    }

    /**
     * @dataProvider dataProviderSetMultiple
     * @param int|null $ttl
     * @throws InvalidArgumentException
     */
    public function testSetMultiple(?int $ttl): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $data = $this->getDataProviderData();

        $cache->setMultiple($data, $ttl);

        foreach ($data as $key => $value) {
            $this->assertSameExceptObject($value, $cache->get((string)$key));
        }
    }

    /**
     * @return array testing multiSet with and without expiry
     */
    public function dataProviderSetMultiple(): array
    {
        return [
            [null],
            [2],
        ];
    }

    public function testGetMultiple(): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $data = $this->getDataProviderData();
        $keys = array_map('strval', array_keys($data));

        $cache->setMultiple($data);

        $this->assertSameExceptObject($data, $cache->getMultiple($keys));
    }

    public function testDeleteMultiple(): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $data = $this->getDataProviderData();
        $keys = array_map('strval', array_keys($data));

        $cache->setMultiple($data);

        $this->assertSameExceptObject($data, $cache->getMultiple($keys));

        $cache->deleteMultiple($keys);

        $emptyData = array_map(static function ($v) {
            return null;
        }, $data);

        $this->assertSameExceptObject($emptyData, $cache->getMultiple($keys));
    }

    public function testZeroAndNegativeTtl()
    {
        $cache = $this->createCacheInstance();
        $cache->clear();
        $cache->setMultiple([
            'a' => 1,
            'b' => 2,
        ]);

        $this->assertTrue($cache->has('a'));
        $this->assertTrue($cache->has('b'));

        $cache->set('a', 11, -1);

        $this->assertFalse($cache->has('a'));

        $cache->set('b', 22, 0);

        $this->assertFalse($cache->has('b'));
    }

    /**
     * @dataProvider dataProviderNormalizeTtl
     * @param mixed $ttl
     * @param mixed $expectedResult
     * @throws ReflectionException
     */
    public function testNormalizeTtl($ttl, $expectedResult): void
    {
        $cache = new ArrayCache();
        $this->assertSameExceptObject($expectedResult, $this->invokeMethod($cache, 'normalizeTtl', [$ttl]));
    }

    /**
     * Data provider for {@see testNormalizeTtl()}
     * @return array test data
     *
     * @throws \Exception
     */
    public function dataProviderNormalizeTtl(): array
    {
        return [
            [123, 123],
            ['123', 123],
            [null, null],
            [0, 0],
            [new DateInterval('PT6H8M'), 6 * 3600 + 8 * 60],
            [new DateInterval('P2Y4D'), 2 * 365 * 24 * 3600 + 4 * 24 * 3600],
        ];
    }

    /**
     * @dataProvider ttlToExpirationProvider
     * @param mixed $ttl
     * @param mixed $expected
     * @throws ReflectionException
     */
    public function testTtlToExpiration($ttl, $expected): void
    {
        if ($expected === 'calculate_expiration') {
            MockHelper::$time = \time();
            $expected = MockHelper::$time + $ttl;
        }
        $cache = new ArrayCache();
        $this->assertSameExceptObject($expected, $this->invokeMethod($cache, 'ttlToExpiration', [$ttl]));
    }

    public function ttlToExpirationProvider(): array
    {
        return [
            [3, 'calculate_expiration'],
            [null, 0],
            [-5, -1],
        ];
    }

    /**
     * @dataProvider iterableProvider
     * @param array $array
     * @param iterable $iterable
     * @throws InvalidArgumentException
     */
    public function testValuesAsIterable(array $array, iterable $iterable): void
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $cache->setMultiple($iterable);

        $this->assertSameExceptObject($array, $cache->getMultiple(array_keys($array)));
    }

    public function iterableProvider(): array
    {
        return [
            'array' => [
                ['a' => 1, 'b' => 2,],
                ['a' => 1, 'b' => 2,],
            ],
            'ArrayIterator' => [
                ['a' => 1, 'b' => 2,],
                new \ArrayIterator(['a' => 1, 'b' => 2,]),
            ],
            'IteratorAggregate' => [
                ['a' => 1, 'b' => 2,],
                new class() implements \IteratorAggregate {
                    public function getIterator()
                    {
                        return new \ArrayIterator(['a' => 1, 'b' => 2,]);
                    }
                }
            ],
            'generator' => [
                ['a' => 1, 'b' => 2,],
                (static function () {
                    yield 'a' => 1;
                    yield 'b' => 2;
                })()
            ]
        ];
    }

    public function testSetWithDateIntervalTtl()
    {
        $cache = $this->createCacheInstance();
        $cache->clear();

        $cache->set('a', 1, new DateInterval('PT1H'));
        $this->assertSameExceptObject(1, $cache->get('a'));

        $cache->setMultiple(['b' => 2]);
        $this->assertSameExceptObject(['b' => 2], $cache->getMultiple(['b']));
    }

    public function testGetInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = $this->createCacheInstance();
        $cache->get(1);
    }

    public function testSetInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = $this->createCacheInstance();
        $cache->set(1, 1);
    }

    public function testDeleteInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = $this->createCacheInstance();
        $cache->delete(1);
    }

    public function testGetMultipleInvalidKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = $this->createCacheInstance();
        $cache->getMultiple([true]);
    }

    public function testGetMultipleInvalidKeysNotIterable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = $this->createCacheInstance();
        $cache->getMultiple(1);
    }

    public function testSetMultipleInvalidKeysNotIterable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = $this->createCacheInstance();
        $cache->setMultiple(1);
    }

    public function testDeleteMultipleInvalidKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = $this->createCacheInstance();
        $cache->deleteMultiple([true]);
    }

    public function testDeleteMultipleInvalidKeysNotIterable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = $this->createCacheInstance();
        $cache->deleteMultiple(1);
    }

    public function testHasInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $cache = $this->createCacheInstance();
        $cache->has(1);
    }
}
