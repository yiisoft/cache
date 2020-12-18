<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\DependencyAwareCache;

final class DependencyAwareCacheTest extends TestCase
{
    private Cache $cache;
    private ArrayCache $handler;
    private DependencyAwareCache $psr;

    public function setUp(): void
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
        $this->assertSame('value', $this->cache->getOrSet('key', static fn () => 'value'));
        $this->assertSame('value', $this->psr->get('key'));
        $this->assertSame('value', $this->handler->get('key')[0]);
    }

    public function testGetAndHasAndSetWithDependency(): void
    {
        $cache = new Cache($this->handler);
        $value = $cache->getOrSet('key', fn (): string => 'value', null, new TagDependency('tag'));

        $this->assertSame('value', $value);
        $this->assertSame('value', $this->psr->get('key'));
        $this->assertSame('value', $this->handler->get('key')[0]);

        TagDependency::invalidate($cache, 'tag');
        $value = $cache->getOrSet('key', fn (): string => 'new-value', null, new TagDependency('tag'));

        $this->assertSame('new-value', $value);
        $this->assertSame('new-value', $this->psr->get('key'));
        $this->assertSame('new-value', $this->handler->get('key')[0]);

        TagDependency::invalidate($cache, 'tag');

        $this->assertNull($this->psr->get('key'));
        $this->assertFalse($this->psr->has('key'));

        $this->assertSame('new-value', $this->handler->get('key')[0]);
        $this->assertTrue($this->handler->has('key'));
    }

    public function testGetMultipleWithDependency(): void
    {
        $cache = new Cache($this->handler);
        $cache->getOrSet('key-1', fn (): string => 'value-1', null, new TagDependency('tag-1'));
        $cache->getOrSet('key-2', fn (): string => 'value-2', null, new TagDependency('tag-2'));

        $this->assertSame(['key-1' => 'value-1', 'key-2' => 'value-2'], $this->psr->getMultiple(['key-1', 'key-2']));

        TagDependency::invalidate($cache, 'tag-1');
        $this->assertSame(['key-1' => null, 'key-2' => 'value-2'], $this->psr->getMultiple(['key-1', 'key-2']));

        TagDependency::invalidate($cache, 'tag-2');
        $this->assertSame(['key-1' => null, 'key-2' => null], $this->psr->getMultiple(['key-1', 'key-2']));
    }
}
