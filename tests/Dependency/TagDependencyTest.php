<?php
namespace Yiisoft\Cache\Tests\Dependency;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\TagDependency;

class TagDependencyTest extends DependencyTestCase
{
    protected function createCache(): CacheInterface
    {
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
}
