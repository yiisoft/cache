<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use DateInterval;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\SimpleCache\CacheInterface;
use stdClass;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheKeyNormalizer;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Exception\RemoveCacheException;
use Yiisoft\Cache\Exception\SetCacheException;
use Yiisoft\Cache\Metadata\CacheItem;
use Yiisoft\Cache\DependencyAwareCache;

use function fclose;
use function fopen;
use function md5;
use function time;

final class CacheTest extends TestCase
{
    private ArrayCache $handler;

    protected function setUp(): void
    {
        $this->handler = new ArrayCache();
    }

    public function testGetOrSet(): void
    {
        $cache = new Cache($this->handler);
        $value = $cache->getOrSet('key', fn (CacheInterface $cache): string => $cache::class);
        $items = $this->getItems($cache);

        $this->assertSame('key', $items['key']->key());
        $this->assertSame(DependencyAwareCache::class, $value);
        $this->assertNull($items['key']->dependency());
        $this->assertNull($items['key']->expiry());
        $this->assertFalse($items['key']->expired(1.0, $cache));
    }

    public function testGetOrSetWithTtl(): void
    {
        $cache = new Cache($this->handler);
        $value = $cache->getOrSet('key', fn (): string => 'value', 3600);
        $items = $this->getItems($cache);

        $this->assertSame('value', $value);
        $this->assertSame(time() + 3600, $items['key']->expiry());
        $this->assertFalse($items['key']->expired(1.0, $cache));
    }

    public function testGetOrSetWithExpiredTtl(): void
    {
        $cache = new Cache($this->handler);
        $value = $cache->getOrSet('key', fn (): string => 'value', -1);
        $items = $this->getItems($cache);

        $this->assertSame('value', $value);
        $this->assertSame(-1, $items['key']->expiry());
        $this->assertTrue($items['key']->expired(1.0, $cache));
    }

    public function testGetOrSetWithDependency(): void
    {
        $cache = new Cache($this->handler);

        $value = $cache->getOrSet('key', fn (): string => 'value', null, new TagDependency('tag'));
        $this->assertSame('value', $value);

        $value = $cache->getOrSet('key', fn (): string => 'new-value', null, new TagDependency('tag'));
        $this->assertSame('value', $value);

        TagDependency::invalidate($cache, 'tag');
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

        TagDependency::invalidate($cache, 'tag');
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

    public function testGetOrSetIfNotExistItems(): void
    {
        $cache = new Cache($this->handler);
        $cache->getOrSet('key', fn (): string => 'value', -1);
        $items = $this->getInaccessibleProperty($cache, 'items');
        $this->setInaccessibleProperty($items, 'items', []);

        $this->assertSame('new-value', $cache->getOrSet('key', fn (): string => 'new-value'));
    }

    public function testHandler(): void
    {
        $cache = new Cache($this->handler);

        $this->assertInstanceOf(CacheInterface::class, $cache->psr());
        $this->assertInstanceOf(DependencyAwareCache::class, $cache->psr());
        $this->assertSame($this->getInaccessibleProperty($cache, 'psr'), $cache->psr());
    }

    public function testGetOrSetForNotSameKeyAndCacheItemKey(): void
    {
        $cache = new Cache($this->handler);
        $cache->getOrSet('key', fn (): string => 'value');

        $this->assertSame('value', $cache->getOrSet('key', fn (): string => 'new-value'));

        $this->handler->clear();
        $this->handler->set('key', ['value', new CacheItem('other-key', null, null)]);

        $this->assertSame('new-value', $cache->getOrSet('key', fn (): string => 'new-value'));
    }

    public static function stringKeyDataProvider(): array
    {
        return [
            'simple-key' => ['simple-key', true, false],
            '64-characters' => ['abs-123djj85&!%kfk^%dkk_yhfjdkfkvuywp;a#dkk2728mv&-dfl;k84_kdufu', true, false],
            '65-characters' => ['abs-123djj85&!%kfk^%dkk_yhfjdkfkvuywp;a#dkk2728mv&-dfl;k84_kdufu0', false, false],
            'psr-reserved' => ['{}()/\@:', false, true],
            'empty-string' => ['', false, true],
        ];
    }

    #[DataProvider('stringKeyDataProvider')]
    public function testKeyMatchingToHandler(string $key, bool $matched, bool $exception): void
    {
        $cache = new Cache($this->handler);
        $cache->getOrSet($key, fn () => 'value');

        $this->assertSame('value', $cache->getOrSet($key, fn () => null));

        if ($matched) {
            $this->assertTrue($cache
                ->psr()
                ->has($key));
            $this->assertSame(
                'value',
                $cache
                    ->psr()
                    ->get($key),
            );
        } else {
            if ($exception) {
                $this->expectException(InvalidArgumentException::class);
            }
            $this->assertFalse($cache
                ->psr()
                ->has($key));
            $this->assertNull($cache
                ->psr()
                ->get($key));
        }
    }

    public static function normalizeKeyDataProvider(): array
    {
        $keyNormalizer = new CacheKeyNormalizer();

        return [
            'int' => [1, '1'],
            'string' => ['asd123', 'asd123'],
            'string-md5' => [$string = 'asd_123-{z4x}', md5($string)],
            'null' => [null, $keyNormalizer->normalize(null)],
            'bool' => [true, $keyNormalizer->normalize(true)],
            'float' => [$float = 1.1, $keyNormalizer->normalize($float)],
            'array' => [
                $array = [1, 'key' => 'value', 'nested' => [1, 2, 'asd_123-{z4x}']],
                $keyNormalizer->normalize($array),
            ],
            'empty-array' => [$array = [], $keyNormalizer->normalize($array)],
            'object' => [
                $object = new class () {
                    public string $name = 'object';
                },
                $keyNormalizer->normalize($object),
            ],
            'empty-object' => [$object = new stdClass(), $keyNormalizer->normalize($object)],
            'callable' => [$callable = fn () => null, $keyNormalizer->normalize($callable)],
        ];
    }

    #[DataProvider('normalizeKeyDataProvider')]
    public function testGetOrSetAndRemoveWithOtherKeys(mixed $key, string $excepted): void
    {
        $cache = new Cache($this->handler);
        $cache->getOrSet($key, static fn (): string => 'value');
        $items = $this->getItems($cache);
        $this->assertSame($excepted, $items[$excepted]->key());

        $cache->remove($key);
        $items = $this->getItems($cache);
        $this->assertSame($items, []);
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

    public static function ttlDataProvider(): array
    {
        $interval = new DateInterval('P2Y4DT6H8M');

        return [
            'null' => [null],
            'int' => [3600],
            DateInterval::class => [$interval],
        ];
    }

    #[DataProvider('ttlDataProvider')]
    public function testConstructorWithOtherDefaultTtl(mixed $ttl): void
    {
        $cache = new Cache($this->handler, $ttl);
        $cache->getOrSet('key', static fn (): string => 'value');
        $items = $this->getItems($cache);
        $this->assertFalse($items['key']->expired(1.0, $cache));
    }

    #[DataProvider('ttlDataProvider')]
    public function testGetOrSetWithOtherTtl(mixed $ttl): void
    {
        $cache = new Cache($this->handler);
        $cache->getOrSet('key', static fn (): string => 'value', $ttl);
        $items = $this->getItems($cache);
        $this->assertFalse($items['key']->expired(1.0, $cache));
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
        return new class () implements CacheInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return null;
            }

            public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
            {
                return false;
            }

            public function delete(string $key): bool
            {
                return false;
            }

            public function clear(): bool
            {
                return false;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                return [];
            }

            public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
            {
                return false;
            }

            public function deleteMultiple(iterable $keys): bool
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
     * @return array<string, CacheItem>
     */
    private function getItems(\Yiisoft\Cache\CacheInterface $cache): array
    {
        $items = $this->getInaccessibleProperty($cache, 'items');
        return $this->getInaccessibleProperty($items, 'items');
    }
}
