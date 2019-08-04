<?php

namespace Yiisoft\CacheOld\Tests;

use Yiisoft\CacheOld\NullCache;

class NullCacheTest extends TestCase
{
    private function getCache(): NullCache
    {
        return new NullCache();
    }

    public function testAdd(): void
    {
        $this->assertTrue($this->getCache()->add('key', 42));
    }

    public function testDeleteMultiple(): void
    {
        $this->assertTrue($this->getCache()->deleteMultiple(['a', 'b']));
    }

    public function testSet(): void
    {
        $this->assertTrue($this->getCache()->set('key', 42));
    }

    public function testGet(): void
    {
        $this->assertSame(42, $this->getCache()->get('key', 42));
    }

    public function testGetMultiple(): void
    {
        $this->assertSame(['a' => 42, 'b' => 42], $this->getCache()->getMultiple(['a', 'b'], 42));
    }

    public function testSetMultiple(): void
    {
        $this->assertTrue($this->getCache()->setMultiple(['a' => 42]));
    }

    public function testAddMultiple(): void
    {
        $this->assertTrue($this->getCache()->addMultiple(['a' => 42]));
    }

    public function testGetOrSet(): void
    {
        $this->assertSame(42, $this->getCache()->getOrSet('key', static function () {
            return 42;
        }));
    }

    public function testDelete(): void
    {
        $this->assertTrue($this->getCache()->delete('key'));
    }

    public function testClear(): void
    {
        $this->assertTrue($this->getCache()->clear());
    }

    public function testHas(): void
    {
        $this->assertFalse($this->getCache()->has('key'));
    }
}
