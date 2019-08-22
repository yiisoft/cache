<?php

namespace Yiisoft\Cache\Tests\ArrayCache;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;

class ArrayCacheDecoratorTest extends ArrayCacheTest
{
    protected function createCacheInstance(): CacheInterface
    {
        return new Cache(new ArrayCache());
    }

    public function testGetInvalidKey(): void
    {
        // Cache decorator allows all types of keys
        $this->assertTrue(true);
    }

    public function testSetInvalidKey(): void
    {
        // Cache decorator allows all types of keys
        $this->assertTrue(true);
    }

    public function testDeleteInvalidKey(): void
    {
        // Cache decorator allows all types of keys
        $this->assertTrue(true);
    }

    public function testGetMultipleInvalidKeys(): void
    {
        // Cache decorator allows all types of keys
        $this->assertTrue(true);
    }

    public function testGetMultipleInvalidKeysNotIterable(): void
    {
        // Cache decorator allows all types of keys
        $this->assertTrue(true);
    }

    public function testSetMultipleInvalidKeysNotIterable(): void
    {
        // Cache decorator allows all types of keys
        $this->assertTrue(true);
    }

    public function testDeleteMultipleInvalidKeys(): void
    {
        // Cache decorator allows all types of keys
        $this->assertTrue(true);
    }

    public function testDeleteMultipleInvalidKeysNotIterable(): void
    {
        // Cache decorator allows all types of keys
        $this->assertTrue(true);
    }

    public function testHasInvalidKey(): void
    {
        // Cache decorator allows all types of keys
        $this->assertTrue(true);
    }
}
