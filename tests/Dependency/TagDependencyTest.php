<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Dependency;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\TagDependency;

final class TagDependencyTest extends DependencyTestCase
{
    protected function createCache(): CacheInterface
    {
        // isChanged of TagDependency needs cache access.
        // Using real cache.
        return new ArrayCache();
    }

    public function testInvalidateByTag(): void
    {
        $arrayCache = $this->getCache();
        $cache = new Cache($arrayCache);

        $cache->getOrSet('item_42_price', static fn () => 13, null, new TagDependency('item_42'));
        $cache->getOrSet('item_42_total', static fn () => 26, null, new TagDependency('item_42'));

        $this->assertSame(13, $cache->getOrSet('item_42_price', static fn () => 26));
        $this->assertSame(26, $cache->getOrSet('item_42_total', static fn () => 13));

        TagDependency::invalidate($arrayCache, 'item_42');

        $this->assertNull($arrayCache->get('item_42_price'));
        $this->assertNull($arrayCache->get('item_42_total'));
    }

    public function testEmptyTags(): void
    {
        $cache = new Cache($this->getCache());
        $dependency = new TagDependency([]);
        $cache->getOrSet('item_42_price', static fn () => 13, null, $dependency);
        $this->assertSame(13, $cache->getOrSet('item_42_price', static fn () => 14));
        $this->assertSame([], $this->getInaccessibleProperty($dependency, 'data'));
    }

    public function testInvalidTag(): void
    {
        $cache = $this->getCache();
        $dependency = new TagDependency(['test', [11]]);
        $this->expectError();
        $dependency->isChanged($cache);
    }
}
