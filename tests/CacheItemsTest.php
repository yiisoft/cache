<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\Metadata\CacheItem;
use Yiisoft\Cache\Metadata\CacheItems;

use function time;

class CacheItemsTest extends TestCase
{
    private CacheItems $items;
    private ArrayCache $cache;
    private TagDependency $dependency;

    public function setUp(): void
    {
        $this->items = new CacheItems();
        $this->cache = new ArrayCache();
        $this->dependency = new TagDependency('tag');
        $this->dependency->evaluateDependency($this->cache);
    }

    public function testSet(): void
    {
        $item1 = new CacheItem('key-1', 'value-1', null, null);
        $item2 = new CacheItem('key-2', 'value-2', time() + 3600, $this->dependency);

        $this->items->set($item1);
        $this->items->set($item2);

        $data = $this->getInaccessibleProperty($this->items, 'items');
        $this->assertSame($data, ['key-1' => $item1, 'key-2' => $item2]);
    }

    public function testRemoveAndHas(): void
    {
        $this->items->set(new CacheItem('key-1', 'value-1', null, null));
        $this->items->set(new CacheItem('key-2', 'value-2', null, null));

        $this->assertTrue($this->items->has('key-1'));
        $this->assertTrue($this->items->has('key-2'));

        $this->items->remove('key-1');
        $this->assertFalse($this->items->has('key-1'));
        $this->assertTrue($this->items->has('key-2'));

        $this->items->remove('key-2');
        $this->assertFalse($this->items->has('key-1'));
        $this->assertFalse($this->items->has('key-2'));

        $data = $this->getInaccessibleProperty($this->items, 'items');
        $this->assertSame($data, []);
    }

    public function getValue(): void
    {
        $this->items->set(new CacheItem('key', 'value', time() + 3600, $this->dependency));
        $this->assertSame('value', $this->items->getValue('key', 1.0, $this->cache));
    }

    public function getValueWithExpired(): void
    {
        $this->items->set(new CacheItem('key', 'value', time(), $this->dependency));
        $this->assertNull($this->items->getValue('key', 1.0, $this->cache));
    }

    public function getValueWithChangeDependency(): void
    {
        $this->items->set(new CacheItem('key', 'value', time() + 3600, $this->dependency));
        TagDependency::invalidate($this->cache, 'tag');
        $this->assertNull($this->items->getValue('key', 1.0, $this->cache));
    }
}
