<?php
namespace Yiisoft\Cache\Tests;

use Psr\Log\NullLogger;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\FileCache;
use Yiisoft\Cache\Dependencies\TagDependency;

/**
 * @group caching
 */
class TagDependencyTest extends TestCase
{
    public function testInvalidate(): void
    {
        $cache = new Cache(new FileCache('tests/runtime/cache', new NullLogger()));

        // single tag test
        $cache->set('a1', 11, 0, new TagDependency('t1'));
        $cache->set('a2', 12, 0, new TagDependency('t1'));
        $cache->set('b1', 21, 0, new TagDependency('t2'));
        $cache->set('b2', 22, 0, new TagDependency('t2'));

        $this->assertEquals(11, $cache->get('a1'));
        $this->assertEquals(12, $cache->get('a2'));
        $this->assertEquals(21, $cache->get('b1'));
        $this->assertEquals(22, $cache->get('b2'));

        TagDependency::invalidate($cache, 't1');
        $this->assertNull($cache->get('a1'));
        $this->assertNull($cache->get('a2'));
        $this->assertEquals(21, $cache->get('b1'));
        $this->assertEquals(22, $cache->get('b2'));

        TagDependency::invalidate($cache, 't2');
        $this->assertNull($cache->get('a1'));
        $this->assertNull($cache->get('a2'));
        $this->assertNull($cache->get('b1'));
        $this->assertNull($cache->get('b2'));

        // multiple tag test
        $cache->set('a1', 11, 0, new TagDependency(['t1', 't2']));
        $cache->set('a2', 12, 0, new TagDependency('t1'));
        $cache->set('b1', 21, 0, new TagDependency(['t1', 't2']));
        $cache->set('b2', 22, 0, new TagDependency('t2'));

        $this->assertEquals(11, $cache->get('a1'));
        $this->assertEquals(12, $cache->get('a2'));
        $this->assertEquals(21, $cache->get('b1'));
        $this->assertEquals(22, $cache->get('b2'));

        TagDependency::invalidate($cache, 't1');
        $this->assertNull($cache->get('a1'));
        $this->assertNull($cache->get('a2'));
        $this->assertNull($cache->get('b1'));
        $this->assertEquals(22, $cache->get('b2'));

        TagDependency::invalidate($cache, 't2');
        $this->assertNull($cache->get('a1'));
        $this->assertNull($cache->get('a2'));
        $this->assertNull($cache->get('b1'));
        $this->assertNull($cache->get('b2'));

        $cache->set('a1', 11, 0, new TagDependency(['t1', 't2']));
        $cache->set('a2', 12, 0, new TagDependency('t1'));
        $cache->set('b1', 21, 0, new TagDependency(['t1', 't2']));
        $cache->set('b2', 22, 0, new TagDependency('t2'));

        $this->assertEquals(11, $cache->get('a1'));
        $this->assertEquals(12, $cache->get('a2'));
        $this->assertEquals(21, $cache->get('b1'));
        $this->assertEquals(22, $cache->get('b2'));

        TagDependency::invalidate($cache, ['t1', 't2']);
        $this->assertNull($cache->get('a1'));
        $this->assertNull($cache->get('a2'));
        $this->assertNull($cache->get('b1'));
        $this->assertNull($cache->get('b2'));
    }
}
