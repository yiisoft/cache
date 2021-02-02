<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\PrefixedCache;

use function array_keys;

final class PrefixedCacheTest extends TestCase
{
    private PrefixedCache $cache;
    private string $prefix = 'myapp_';

    protected function setUp(): void
    {
        $this->cache = new PrefixedCache(new ArrayCache(), $this->prefix);
    }

    public function testSetAndGetAndDelete(): void
    {
        $this->cache->set('key', 'value', 3600);
        $this->assertSame($this->prefix . 'key', $this->getCacheKeys()[0]);
        $this->assertSame('value', $this->cache->get('key'));

        $this->cache->delete('key');
        $this->assertSame([], $this->getCacheKeys());
    }

    public function testClearAndHas(): void
    {
        $this->cache->set('key', 'value', 3600);
        $this->assertTrue($this->cache->has('key'));
        $this->assertTrue($this->cache->clear());
        $this->assertFalse($this->cache->has('key'));
    }

    public function testSetMultipleAndGetMultipleAndDeleteMultiple(): void
    {
        $this->cache->setMultiple(['key-1' => 'value-1', 'key-2' => 'value-2'], 3600);

        $this->assertSame(
            [$this->prefix . 'key-1' => 'value-1', $this->prefix . 'key-2' => 'value-2'],
            $this->cache->getMultiple(['key-1', 'key-2'])
        );

        $this->cache->deleteMultiple(['key-1', 'key-2']);
        $this->assertSame(
            [$this->prefix . 'key-1' => null, $this->prefix . 'key-2' => null],
            $this->cache->getMultiple(['key-1', 'key-2'])
        );
    }

    public function testCreateCacheAndGetOrSet(): void
    {
        $this->assertSame('value', (new Cache($this->cache))->getOrSet('key', static function () {
            return 'value';
        }));

        $this->assertSame($this->prefix . 'key', $this->getCacheKeys()[0]);
    }

    private function getCacheKeys(): array
    {
        $cache = $this->getInaccessibleProperty($this->cache, 'cache');
        $values = $this->getInaccessibleProperty($cache, 'cache');
        return array_keys($values);
    }
}
