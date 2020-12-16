<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use stdClass;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Exception\RemoveCacheException;
use Yiisoft\Cache\Exception\SetCacheException;
use Yiisoft\Cache\Metadata\CacheItem;

use function fclose;
use function fopen;
use function get_class;
use function json_encode;
use function md5;
use function time;

class CacheTest extends TestCase
{
    private ArrayCache $handler;

    public function setUp(): void
    {
        $this->handler = new ArrayCache();
    }

    public function testGetOrSet(): void
    {
        $cache = new Cache($this->handler);
        $value = $cache->getOrSet('key', fn (CacheInterface $cache): string => get_class($cache));
        $items = $this->getItems($cache);

        $this->assertSame('key', $items['key']->key());
        $this->assertSame(get_class($this->handler), $value);
        $this->assertNull($items['key']->dependency());
        $this->assertNull($items['key']->expiry());
        $this->assertFalse($items['key']->expired(1.0, $this->handler));
    }

    public function testGetOrSetWithTtl(): void
    {
        $cache = new Cache($this->handler);
        $value = $cache->getOrSet('key', fn (): string => 'value', 3600);
        $items = $this->getItems($cache);

        $this->assertSame('value', $value);
        $this->assertSame(time() + 3600, $items['key']->expiry());
        $this->assertFalse($items['key']->expired(1.0, $this->handler));
    }

    public function testGetOrSetWithExpiredTtl(): void
    {
        $cache = new Cache($this->handler);
        $value = $cache->getOrSet('key', fn (): string => 'value', -1);
        $items = $this->getItems($cache);

        $this->assertSame('value', $value);
        $this->assertSame(-1, $items['key']->expiry());
        $this->assertTrue($items['key']->expired(1.0, $this->handler));
    }

    public function testGetOrSetWithDependency(): void
    {
        $cache = new Cache($this->handler);

        $value = $cache->getOrSet('key', fn (): string => 'value', null, new TagDependency('tag'));
        $this->assertSame('value', $value);

        $value = $cache->getOrSet('key', fn (): string => 'new-value', null, new TagDependency('tag'));
        $this->assertSame('value', $value);

        TagDependency::invalidate($this->handler, 'tag');
        $value = $cache->getOrSet('key', fn (): string => 'new-value', null, new TagDependency('tag'));
        $this->assertSame('new-value', $value);
    }

    public function testGetOrSetWithTtlAndDependency(): void
    {
        $cache = new Cache($this->handler);

        $value = $cache->getOrSet('key', fn (): string => 'value-1', -1, new TagDependency('tag'));
        $this->assertSame('value-1', $value);

        $value = $cache->getOrSet('key', fn (): string => 'value-2', 0, new TagDependency('tag'));
        $this->assertSame('value-2', $value);

        $value = $cache->getOrSet('key', fn (): string => 'value-3', time() + 3600, new TagDependency('tag'));
        $this->assertSame('value-3', $value);

        $value = $cache->getOrSet('key', fn (): string => 'value-4', null, new TagDependency('tag'));
        $this->assertSame('value-3', $value);

        TagDependency::invalidate($this->handler, 'tag');
        $value = $cache->getOrSet('key', fn (): string => 'value-5', null, new TagDependency('tag'));
        $this->assertSame('value-5', $value);

        $value = $cache->getOrSet('key', fn (): string => 'value-6');
        $this->assertSame('value-5', $value);
    }

    public function testGetOrSetAndRemove(): void
    {
        $cache = new Cache($this->handler);

        $value = $cache->getOrSet('key', fn (): string => 'value-1');
        $this->assertSame('value-1', $value);

        $cache->remove('key');

        $value = $cache->getOrSet('key', fn (): string => 'value-2');
        $this->assertSame('value-2', $value);
    }

    public function keyDataProvider(): array
    {
        return [
            'int' => [1, '1'],
            'string' => ['asd123', 'asd123'],
            'string-md5' => [$string = 'asd_123-{z4x}', md5($string)],
            'null' => [null, $this->encode(null)],
            'bool' => [true, $this->encode(true)],
            'float' => [$float = 1.1, $this->encode($float)],
            'array' => [
                $array = [1, 'key' => 'value', 'nested' => [1, 2, 'asd_123-{z4x}']],
                $this->encode($array),
            ],
            'empty-array' => [$array = [], $this->encode($array)],
            'object' => [
                $object = new class() {
                    public string $name = 'object';
                },
                $this->encode($object),
            ],
            'empty-object' => [$object = new stdClass(), $this->encode($object)],
            'callable' => [$callable = fn () => null, $this->encode($callable)],
        ];
    }

    /**
     * @dataProvider keyDataProvider
     *
     * @param mixed $key
     * @param string $excepted
     */
    public function testGetOrSetAndRemoveWithOtherKeys($key, string $excepted): void
    {
        $cache = new Cache($this->handler);
        $cache->getOrSet($key, static fn (): string => 'value');
        $items = $this->getItems($cache);
        $this->assertSame($excepted, $items[$excepted]->key());

        $cache->remove($key);
        $items = $this->getItems($cache);
        $this->assertSame($items, []);
    }

    /**
     * @dataProvider keyDataProvider
     *
     * @param mixed $key
     * @param string $expected
     */
    public function testConstructorWithKeyPrefixAndGetOrSetWithOtherKeys($key, string $expected): void
    {
        $cache = new Cache($this->handler, null);
        $cache->getOrSet($key, static fn (): string => 'value');
        $items = $this->getItems($cache);
        $this->assertSame($expected, $items[$expected]->key());
    }

    public function testGetOrSetThrowExceptionForInvalidKey(): void
    {
        $cache = new Cache($this->handler);
        $resource = fopen('php://memory', 'r');
        $this->expectException(InvalidArgumentException::class);
        $cache->getOrSet($resource, static fn (): string => 'value');
        fclose($resource);
    }

    public function testRemoveThrowExceptionForInvalidKey(): void
    {
        $cache = new Cache($this->handler);
        $resource = fopen('php://memory', 'r');
        $this->expectException(InvalidArgumentException::class);
        $cache->remove($resource);
        fclose($resource);
    }

    public function ttlDataProvider(): array
    {
        $interval = new DateInterval('P2Y4DT6H8M');

        return [
            'null' => [null],
            'int' => [3600],
            'DateInterval' => [$interval],
        ];
    }

    /**
     * @dataProvider ttlDataProvider
     *
     * @param mixed $ttl
     */
    public function testConstructorWithOtherDefaultTtl($ttl): void
    {
        $cache = new Cache($this->handler, $ttl);
        $cache->getOrSet('key', static fn (): string => 'value');
        $items = $this->getItems($cache);
        $this->assertFalse($items['key']->expired(1.0, $this->handler));
    }

    /**
     * @dataProvider ttlDataProvider
     *
     * @param mixed $ttl
     */
    public function testGetOrSetWithOtherTtl($ttl): void
    {
        $cache = new Cache($this->handler);
        $cache->getOrSet('key', static fn (): string => 'value', $ttl);
        $items = $this->getItems($cache);
        $this->assertFalse($items['key']->expired(1.0, $this->handler));
    }

    public function invalidTtlDataProvider(): array
    {
        return [
            'float' => [1.1],
            'string' => ['a'],
            'array' => [[]],
            'bool' => [true],
            'callable' => [fn () => null],
            'object' => [new stdClass()],
        ];
    }

    /**
     * @dataProvider invalidTtlDataProvider
     *
     * @param mixed $ttl
     */
    public function testConstructorThrowExceptionForInvalidDefaultTtl($ttl): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Cache($this->handler, $ttl);
    }

    /**
     * @dataProvider invalidTtlDataProvider
     *
     * @param mixed $ttl
     */
    public function testGetOrSetThrowExceptionForInvalidTtl($ttl): void
    {
        $cache = new Cache($this->handler);
        $this->expectException(InvalidArgumentException::class);
        $cache->getOrSet('key', static fn (): string => 'value', $ttl);
    }

    public function testGetOrSetThrowSetCacheException(): void
    {
        $cache = new Cache($this->createFalseCache());
        $this->expectException(SetCacheException::class);
        $cache->getOrSet('key', static fn (): string => 'value');
    }

    public function testGetOrSetThrowAndCatchSetCacheException(): void
    {
        $cache = new Cache($this->createFalseCache());

        try {
            $cache->getOrSet('key', static fn (): string => 'value');
        } catch (SetCacheException $e) {
            $this->assertSame('key', $e->getKey());
            $this->assertSame('value', $e->getValue());
            $this->assertInstanceOf(CacheItem::class, $e->getItem());
        }
    }

    public function testRemoveThrowRemoveCacheException(): void
    {
        $cache = new Cache($this->createFalseCache());
        $this->expectException(RemoveCacheException::class);
        $cache->remove('key');
    }

    public function testRemoveThrowAndCatchRemoveCacheException(): void
    {
        $cache = new Cache($this->createFalseCache());

        try {
            $cache->remove('key');
        } catch (RemoveCacheException $e) {
            $this->assertSame('key', $e->getKey());
        }
    }

    private function createFalseCache(): CacheInterface
    {
        return new class() implements CacheInterface {
            public function get($key, $default = null)
            {
                return null;
            }

            public function set($key, $value, $ttl = null): bool
            {
                return false;
            }

            public function delete($key): bool
            {
                return false;
            }

            public function clear(): bool
            {
                return false;
            }

            public function getMultiple($keys, $default = null): iterable
            {
                return [];
            }

            public function setMultiple($values, $ttl = null): bool
            {
                return false;
            }

            public function deleteMultiple($keys): bool
            {
                return false;
            }

            public function has($key): bool
            {
                return false;
            }
        };
    }

    /**
     * @param \Yiisoft\Cache\CacheInterface $cache
     *
     * @return array<string, CacheItem>
     */
    private function getItems(\Yiisoft\Cache\CacheInterface $cache): array
    {
        $items = $this->getInaccessibleProperty($cache, 'items');
        return $this->getInaccessibleProperty($items, 'items');
    }

    /**
     * @param mixed $key
     *
     * @return string
     */
    private function encode($key): string
    {
        return md5(json_encode($key));
    }
}
