<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\DependencyAwareCache;
use Yiisoft\Cache\Metadata\CacheItem;

final class DependencyAwareCacheTest extends TestCase
{
    private Cache $cache;
    private ArrayCache $handler;
    private DependencyAwareCache $psr;

    protected function setUp(): void
    {
        $this->handler = new ArrayCache();
        $this->cache = new Cache($this->handler);
        $this->psr = new DependencyAwareCache($this->cache, $this->handler);
    }

    public function testSetAndGetAndDelete(): void
    {
        $this->psr->set('key', 'value', 3600);
        $this->assertSame('value', $this->psr->get('key'));
        $this->assertSame('value', $this->handler->get('key'));

        $this->psr->delete('key');
        $this->assertNull($this->psr->get('key'));
        $this->assertNull($this->handler->get('key'));
    }

    public function testClearAndHas(): void
    {
        $this->psr->set('key', 'value', 3600);

        $this->assertTrue($this->psr->has('key'));
        $this->assertTrue($this->handler->has('key'));

        $this->assertTrue($this->psr->clear());

        $this->assertFalse($this->psr->has('key'));
        $this->assertFalse($this->handler->has('key'));
    }

    public function testSetMultipleAndGetMultipleAndDeleteMultiple(): void
    {
        $values = ['key-1' => 'value-1', 'key-2' => 'value-2'];
        $this->psr->setMultiple(['key-1' => 'value-1', 'key-2' => 'value-2'], 3600);

        $this->assertSame($values, $this->psr->getMultiple(['key-1', 'key-2']));
        $this->assertSame($values, $this->handler->getMultiple(['key-1', 'key-2']));

        $values = ['key-1' => null, 'key-2' => null];
        $this->psr->deleteMultiple(['key-1', 'key-2']);

        $this->assertSame($values, $this->psr->getMultiple(['key-1', 'key-2']));
        $this->assertSame($values, $this->handler->getMultiple(['key-1', 'key-2']));
    }

    public function testCreateCacheAndGetOrSet(): void
    {
        $this->assertSame('value', $this->cache->getOrSet('key', static fn() => 'value'));
        $this->assertSame('value', $this->psr->get('key'));
        $this->assertSame('value', $this->handler->get('key')[0]);
    }

    public function testGetAndHasAndSetWithDependency(): void
    {
        $value = $this->cache->getOrSet('key', fn(): string => 'value', null, new TagDependency('tag'));

        $this->assertSame('value', $value);
        $this->assertSame('value', $this->psr->get('key'));
        $this->assertSame('value', $this->handler->get('key')[0]);

        TagDependency::invalidate($this->cache, 'tag');
        $value = $this->cache->getOrSet('key', fn(): string => 'new-value', null, new TagDependency('tag'));

        $this->assertSame('new-value', $value);
        $this->assertSame('new-value', $this->psr->get('key'));
        $this->assertSame('new-value', $this->handler->get('key')[0]);

        TagDependency::invalidate($this->cache, 'tag');

        $this->assertNull($this->psr->get('key'));
        $this->assertFalse($this->psr->has('key'));

        $this->assertSame('new-value', $this->handler->get('key')[0]);
        $this->assertTrue($this->handler->has('key'));
    }

    public function testGetMultipleWithDependency(): void
    {
        $this->cache->getOrSet('key-1', fn(): string => 'value-1', null, new TagDependency('tag-1'));
        $this->cache->getOrSet('key-2', fn(): string => 'value-2', null, new TagDependency('tag-2'));

        $this->assertSame(['key-1' => 'value-1', 'key-2' => 'value-2'], $this->psr->getMultiple(['key-1', 'key-2']));

        TagDependency::invalidate($this->cache, 'tag-1');
        $this->assertSame(['key-1' => null, 'key-2' => 'value-2'], $this->psr->getMultiple(['key-1', 'key-2']));

        TagDependency::invalidate($this->cache, 'tag-2');
        $this->assertSame(['key-1' => null, 'key-2' => null], $this->psr->getMultiple(['key-1', 'key-2']));
    }

    public function testGetRaw(): void
    {
        $this->assertNull($this->psr->getRaw('not-exist'));

        $this->psr->set('key-1', 'value-1');
        $this->assertSame('value-1', $this->psr->getRaw('key-1'));

        $dependency = new TagDependency('tag');
        $this->cache->getOrSet('key-2', fn(): string => 'value-2', null, $dependency);

        $this->assertSame('value-2', $this->psr->getRaw('key-2')[0]);
        $this->assertInstanceOf(CacheItem::class, $this->psr->getRaw('key-2')[1]);
        $this->assertSame('key-2', $this->psr
            ->getRaw('key-2')[1]
            ->key());
        $this->assertSame($dependency, $this->psr
            ->getRaw('key-2')[1]
            ->dependency());
    }
}
