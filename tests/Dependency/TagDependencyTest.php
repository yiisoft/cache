<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;

use function json_encode;
use function md5;
use function time;

final class TagDependencyTest extends DependencyTestCase
{
    protected function createCache(): CacheInterface
    {
        // isChanged of TagDependency needs cache access.
        // Using real cache.
        return new Cache(new ArrayCache());
    }

    public function testCreateDependency(): void
    {
        $cache = $this->getCache();

        $cache->getOrSet('key', static fn () => 'value', null, new TagDependency('tag', 3600));
        $arrayHandler = $this->getInaccessibleProperty($cache->psr(), 'handler');
        $data = $this->getInaccessibleProperty($arrayHandler, 'cache');
        $key = md5(json_encode([TagDependency::class, 'tag']));

        $this->assertTrue(isset($data[$key]));
        $this->assertSame(['tag', time() + 3600], $data[$key]);
        $this->assertSame('value', $cache->getOrSet('key', static fn () => null));
    }

    public function testInvalidateByTag(): void
    {
        $cache = $this->getCache();

        $cache->getOrSet('item_42_price', static fn () => 13, null, new TagDependency('item_42'));
        $cache->getOrSet('item_42_total', static fn () => 26, null, new TagDependency('item_42'));

        $this->assertSame(13, $cache->getOrSet('item_42_price', static fn () => 26));
        $this->assertSame(26, $cache->getOrSet('item_42_total', static fn () => 13));

        TagDependency::invalidate($cache, 'item_42');

        $this->assertNull($cache->getOrSet('item_42_price', static fn () => null));
        $this->assertNull($cache->getOrSet('item_42_total', static fn () => null));
    }

    public function testEmptyTags(): void
    {
        $cache = $this->getCache();
        $dependency = new TagDependency([]);
        $cache->getOrSet('item_42_price', static fn () => 13, null, $dependency);
        $this->assertSame(13, $cache->getOrSet('item_42_price', static fn () => 14));
        $this->assertSame([], $this->getInaccessibleProperty($dependency, 'data'));
    }

    public function testInvalidTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TagDependency('test', 0);
    }

    public function testInvalidTag(): void
    {
        $cache = $this->getCache();
        $dependency = new TagDependency(['test', [11]]);
        $this->expectError();
        $dependency->isChanged($cache);
    }
}
