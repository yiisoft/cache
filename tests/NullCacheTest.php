<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Cache;
use Yiisoft\Cache\NullCache;

class NullCacheTest extends TestCase
{
    private NullCache $cache;

    public function setUp(): void
    {
        $this->cache = new NullCache();
    }

    public function testGet(): void
    {
        $this->assertSame(42, $this->cache->get('key', 42));
    }

    public function testSet(): void
    {
        $this->assertTrue($this->cache->set('key', 42));
    }

    public function testDelete(): void
    {
        $this->assertTrue($this->cache->delete('key'));
    }

    public function testClear(): void
    {
        $this->assertTrue($this->cache->clear());
    }

    public function testGetMultiple(): void
    {
        $this->assertSame(['a' => 42, 'b' => 42], $this->cache->getMultiple(['a', 'b'], 42));
    }

    public function testSetMultiple(): void
    {
        $this->assertTrue($this->cache->setMultiple(['a' => 42]));
    }

    public function testDeleteMultiple(): void
    {
        $this->assertTrue($this->cache->deleteMultiple(['a', 'b']));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->cache->has('key'));
    }

    public function testCreateCacheAndGetOrSet(): void
    {
        $this->assertSame(42, (new Cache($this->cache))->getOrSet('key', static function () {
            return 42;
        }));
    }
}
