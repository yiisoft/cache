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

final class TagDependencyTest extends DependencyTestCase
{
    public function testCreateDependency(): void
    {
        $cache = $this->getCache();

        $cache->getOrSet('key', static fn() => 'value', null, new TagDependency('tag', 3600));
        $arrayHandler = $this->getInaccessibleProperty($cache->psr(), 'handler');
        $data = $this->getInaccessibleProperty($arrayHandler, 'cache');

        $this->assertTrue(isset($data[md5(json_encode([TagDependency::class, 'tag']))]));
        $this->assertSame('value', $cache->getOrSet('key', static fn() => null));
    }

    public function testInvalidateByTag(): void
    {
        $cache = $this->getCache();

        $cache->getOrSet('item_42_price', static fn() => 13, null, new TagDependency('item_42'));
        $cache->getOrSet('item_42_total', static fn() => 26, null, new TagDependency('item_42'));

        $this->assertSame(13, $cache->getOrSet('item_42_price', static fn() => 26, null, new TagDependency('item_42')));
        $this->assertSame(26, $cache->getOrSet('item_42_total', static fn() => 13, null, new TagDependency('item_42')));

        TagDependency::invalidate($cache, 'item_42');

        $this->assertNull($cache->getOrSet('item_42_price', static fn() => null, null, new TagDependency('item_42')));
        $this->assertNull($cache->getOrSet('item_42_total', static fn() => null, null, new TagDependency('item_42')));
    }

    public function testEmptyTags(): void
    {
        $cache = $this->getCache();
        $dependency = new TagDependency([]);
        $cache->getOrSet('item_42_price', static fn() => 13, null, $dependency);
        $this->assertSame(13, $cache->getOrSet('item_42_price', static fn() => 14));
        $this->assertSame([], $this->getInaccessibleProperty($dependency, 'data'));
    }

    public function testInvalidTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TagDependency('test', 0);
    }

    public function testInvalidTag(): void
    {
        $dependency = new TagDependency("\xB1\x31");
        $this->expectException(InvalidArgumentException::class);
        $dependency->evaluateDependency($this->getCache());
    }

    protected function createCache(): CacheInterface
    {
        // isChanged of TagDependency needs cache access.
        // Using real cache.
        return new Cache(new ArrayCache());
    }
}
