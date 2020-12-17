<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use ArrayIterator;
use DateInterval;
use Exception;
use IteratorAggregate;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use Yiisoft\Cache\ArrayCache;

use function array_keys;
use function array_map;
use function is_object;
use function sleep;
use function time;

final class ArrayCacheTest extends TestCase
{
    private ArrayCache $cache;

    public function setUp(): void
    {
        $this->cache = new ArrayCache();
    }

    public function testExpire(): void
    {
        $this->cache->clear();

        $this->assertTrue($this->cache->set('expire_test', 'expire_test', 1));

        $this->assertTrue($this->cache->has('expire_test'));
        $this->assertSameExceptObject('expire_test', $this->cache->get('expire_test'));

        sleep(1);

        $this->assertFalse($this->cache->has('expire_test'));
        $this->assertNull($this->cache->get('expire_test'));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $key
     * @param $value
     *
     * @throws InvalidArgumentException
     */
    public function testSet($key, $value): void
    {
        $this->cache->clear();

        for ($i = 0; $i < 2; $i++) {
            $this->assertTrue($this->cache->set($key, $value));
        }
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $key
     * @param $value
     *
     * @throws InvalidArgumentException
     */
    public function testGet($key, $value): void
    {
        $this->cache->clear();

        $this->cache->set($key, $value);
        $valueFromCache = $this->cache->get($key, 'default');

        $this->assertSameExceptObject($value, $valueFromCache);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $key
     * @param $value
     *
     * @throws InvalidArgumentException
     */
    public function testValueInCacheCannotBeChanged($key, $value): void
    {
        $this->cache->clear();

        $this->cache->set($key, $value);
        $valueFromCache = $this->cache->get($key, 'default');

        $this->assertSameExceptObject($value, $valueFromCache);

        if (is_object($value)) {
            $originalValue = clone $value;
            $valueFromCache->test_field = 'changed';
            $value->test_field = 'changed';
            $valueFromCacheNew = $this->cache->get($key, 'default');
            $this->assertSameExceptObject($originalValue, $valueFromCacheNew);
        }
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $key
     * @param $value
     *
     * @throws InvalidArgumentException
     */
    public function testHas($key, $value): void
    {
        $this->cache->clear();

        $this->cache->set($key, $value);

        $this->assertTrue($this->cache->has($key));
        // check whether exists affects the value
        $this->assertSameExceptObject($value, $this->cache->get($key));

        $this->assertTrue($this->cache->has($key));
        $this->assertFalse($this->cache->has('not_exists'));
    }

    public function testGetNonExistent(): void
    {
        $this->cache->clear();

        $this->assertNull($this->cache->get('non_existent_key'));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $key
     * @param $value
     *
     * @throws InvalidArgumentException
     */
    public function testDelete($key, $value): void
    {
        $this->cache->clear();

        $this->cache->set($key, $value);

        $this->assertSameExceptObject($value, $this->cache->get($key));
        $this->assertTrue($this->cache->delete($key));
        $this->assertNull($this->cache->get($key));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $key
     * @param $value
     *
     * @throws InvalidArgumentException
     */
    public function testClear($key, $value): void
    {
        $this->cache->clear();
        $data = $this->dataProvider();

        foreach ($data as $datum) {
            $this->cache->set($datum[0], $datum[1]);
        }

        $this->assertTrue($this->cache->clear());
        $this->assertNull($this->cache->get($key));
    }

    /**
     * @dataProvider dataProviderSetMultiple
     *
     * @param int|null $ttl
     *
     * @throws InvalidArgumentException
     */
    public function testSetMultiple(?int $ttl): void
    {
        $this->cache->clear();

        $data = $this->getDataProviderData();

        $this->cache->setMultiple($data, $ttl);

        foreach ($data as $key => $value) {
            $this->assertSameExceptObject($value, $this->cache->get((string)$key));
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
        $this->cache->clear();

        $data = $this->getDataProviderData();
        $keys = array_map('strval', array_keys($data));

        $this->cache->setMultiple($data);

        $this->assertSameExceptObject($data, $this->cache->getMultiple($keys));
    }

    public function testDeleteMultiple(): void
    {
        $this->cache->clear();

        $data = $this->getDataProviderData();
        $keys = array_map('strval', array_keys($data));

        $this->cache->setMultiple($data);

        $this->assertSameExceptObject($data, $this->cache->getMultiple($keys));

        $this->cache->deleteMultiple($keys);

        $emptyData = array_map(static function ($v) {
            return null;
        }, $data);

        $this->assertSameExceptObject($emptyData, $this->cache->getMultiple($keys));
    }

    public function testZeroAndNegativeTtl()
    {
        $this->cache->clear();
        $this->cache->setMultiple([
            'a' => 1,
            'b' => 2,
        ]);

        $this->assertTrue($this->cache->has('a'));
        $this->assertTrue($this->cache->has('b'));

        $this->cache->set('a', 11, -1);

        $this->assertFalse($this->cache->has('a'));

        $this->cache->set('b', 22, 0);

        $this->assertFalse($this->cache->has('b'));
    }

    /**
     * @dataProvider dataProviderNormalizeTtl
     *
     * @param mixed $ttl
     * @param mixed $expectedResult
     *
     * @throws ReflectionException
     */
    public function testNormalizeTtl($ttl, $expectedResult): void
    {
        $cache = new ArrayCache();
        $this->assertSameExceptObject($expectedResult, $this->invokeMethod($cache, 'normalizeTtl', [$ttl]));
    }

    /**
     * Data provider for {@see testNormalizeTtl()}
     *
     * @throws Exception
     *
     * @return array test data
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
     *
     * @param mixed $ttl
     * @param mixed $expected
     *
     * @throws ReflectionException
     */
    public function testTtlToExpiration($ttl, $expected): void
    {
        if ($expected === 'calculate_expiration') {
            $expected = time() + $ttl;
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
     *
     * @param array $array
     * @param iterable $iterable
     *
     * @throws InvalidArgumentException
     */
    public function testValuesAsIterable(array $array, iterable $iterable): void
    {
        $this->cache->clear();

        $this->cache->setMultiple($iterable);

        $this->assertSameExceptObject($array, $this->cache->getMultiple(array_keys($array)));
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
                new ArrayIterator(['a' => 1, 'b' => 2,]),
            ],
            'IteratorAggregate' => [
                ['a' => 1, 'b' => 2,],
                new class() implements IteratorAggregate {
                    public function getIterator()
                    {
                        return new ArrayIterator(['a' => 1, 'b' => 2,]);
                    }
                },
            ],
            'generator' => [
                ['a' => 1, 'b' => 2,],
                (static function () {
                    yield 'a' => 1;
                    yield 'b' => 2;
                })(),
            ],
        ];
    }

    public function testSetWithDateIntervalTtl()
    {
        $this->cache->clear();

        $this->cache->set('a', 1, new DateInterval('PT1H'));
        $this->assertSameExceptObject(1, $this->cache->get('a'));

        $this->cache->setMultiple(['b' => 2]);
        $this->assertSameExceptObject(['b' => 2], $this->cache->getMultiple(['b']));
    }

    public function testGetInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->get(1);
    }

    public function testSetInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->set(1, 1);
    }

    public function testDeleteInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->delete(1);
    }

    public function testGetMultipleInvalidKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->getMultiple([true]);
    }

    public function testGetMultipleInvalidKeysNotIterable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->getMultiple(1);
    }

    public function testSetMultipleInvalidKeysNotIterable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->setMultiple(1);
    }

    public function testDeleteMultipleInvalidKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->deleteMultiple([true]);
    }

    public function testDeleteMultipleInvalidKeysNotIterable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->deleteMultiple(1);
    }

    public function testHasInvalidKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->has(1);
    }
}
