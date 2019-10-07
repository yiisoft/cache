<?php

namespace Yiisoft\Cache\Tests\Dependency;

require_once __DIR__ . '/../MockHelper.php';

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Tests\MockHelper;

class TagDependencyTest extends DependencyTestCase
{
    protected function tearDown(): void
    {
        MockHelper::resetMocks();
    }

    protected function createCache(): CacheInterface
    {
        // isChanged of TagDependency needs cache access.
        // Using real cache.
        return new Cache(new ArrayCache());
    }

    public function testInvalidateByTag(): void
    {
        $cache = $this->getCache();
        $cache->set('item_42_price', 13, null, new TagDependency('item_42'));
        $cache->set('item_42_total', 26, null, new TagDependency('item_42'));

        $this->assertSame(13, $cache->get('item_42_price'));
        $this->assertSame(26, $cache->get('item_42_total'));

        TagDependency::invalidate($cache, 'item_42');

        // keys are invalidated but are still there
        $this->assertTrue($cache->has('item_42_price'));
        $this->assertTrue($cache->has('item_42_total'));

        $this->assertNull($cache->get('item_42_price'));
        $this->assertNull($cache->get('item_42_total'));
    }

    public function testEmptyTags(): void
    {
        $cache = $this->getCache();
        $dependency = new TagDependency([]);
        $cache->set('item_42_price', 13, null, $dependency);
        $this->assertSame(13, $cache->get('item_42_price'));
        $this->assertSame([], $this->getInaccessibleProperty($dependency, 'data'));
    }

    public function testInvalidTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MockHelper::$mock_json_encode = false;
        $cache = $this->getCache();
        $dependency = new TagDependency(['test']);
        $dependency->isChanged($cache);
    }
}
