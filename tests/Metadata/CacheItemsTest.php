<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Metadata;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\Metadata\CacheItem;
use Yiisoft\Cache\Metadata\CacheItems;
use Yiisoft\Cache\Tests\TestCase;

final class CacheItemsTest extends TestCase
{
    private CacheItems $items;
    private Cache $cache;
    private TagDependency $dependency;

    public function setUp(): void
    {
        $this->items = new CacheItems();
        $this->cache = new Cache(new ArrayCache());
        $this->dependency = new TagDependency('tag');
        $this->dependency->evaluateDependency($this->cache);
    }

    public function testSet(): void
    {
        $item1 = new CacheItem('key-1', null, null);
        $item2 = new CacheItem('key-2', 3600, $this->dependency);

        $this->items->set($item1);
        $this->items->set($item2);

        $data = $this->getInaccessibleProperty($this->items, 'items');
        $this->assertSame($data, ['key-1' => $item1, 'key-2' => $item2]);
    }

    public function testRemoveAndExpired(): void
    {
        $this->items->set(new CacheItem('key-1', -1, null));
        $this->items->set(new CacheItem('key-2', 0, null));

        $this->assertTrue($this->items->expired('key-1', 1.0, $this->cache));
        $this->assertTrue($this->items->expired('key-2', 1.0, $this->cache));

        $this->items->remove('key-1');
        $this->assertFalse($this->items->expired('key-1', 1.0, $this->cache));
        $this->assertTrue($this->items->expired('key-2', 1.0, $this->cache));

        $this->items->remove('key-2');
        $this->assertFalse($this->items->expired('key-1', 1.0, $this->cache));
        $this->assertFalse($this->items->expired('key-2', 1.0, $this->cache));

        $data = $this->getInaccessibleProperty($this->items, 'items');
        $this->assertSame($data, []);
    }

    public function testExpired(): void
    {
        $this->items->set(new CacheItem('key', -1, null));
        $this->assertTrue($this->items->expired('key', 1.0, $this->cache));

        $this->items->set(new CacheItem('key', 0, null));
        $this->assertTrue($this->items->expired('key', 1.0, $this->cache));

        $this->items->set(new CacheItem('key', 3600, null));
        $this->assertFalse($this->items->expired('key', 1.0, $this->cache));
    }

    public function testExpiredWithDependency(): void
    {
        $this->items->set(new CacheItem('key', 3600, $this->dependency));
        $this->assertFalse($this->items->expired('key', 1.0, $this->cache));

        $this->items->set(new CacheItem('key', 3600, $this->dependency));
        TagDependency::invalidate($this->cache, 'tag');
        $this->assertTrue($this->items->expired('key', 1.0, $this->cache));
    }
}
