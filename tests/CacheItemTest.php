<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Metadata\CacheItem;

use function time;

class CacheItemTest extends TestCase
{
    private ArrayCache $cache;
    private TagDependency $dependency;

    public function setUp(): void
    {
        $this->cache = new ArrayCache();
        $this->dependency = new TagDependency('tag');
        $this->dependency->evaluateDependency($this->cache);
    }

    public function testGetters(): void
    {
        $item = new CacheItem(
            $key = 'key',
            $value = 'value',
            $expiry = time() + 3600,
            $this->dependency,
        );

        $this->assertSame($key, $item->key());
        $this->assertSame($value, $item->value());
        $this->assertSame($expiry, $item->expiry());
        $this->assertSame($this->dependency, $item->dependency());
        $this->assertFalse($item->expired(1.0, $this->cache));
    }

    public function testExpiredThatNeverExpires(): void
    {
        $item = new CacheItem('key', 'value', null, null);
        $this->assertNull($item->expiry());
        $this->assertNull($item->dependency());
        $this->assertFalse($item->expired(1.0, $this->cache));
    }

    public function testExpiredAndUpdate(): void
    {
        $item = new CacheItem('key', 'value', time() + 3600, $this->dependency);
        $this->assertFalse($item->expired(1.0, $this->cache));

        $item->update('new-value', time(), $this->dependency);
        $this->assertTrue($item->expired(1.0, $this->cache));
    }

    public function testExpiredWithChangeDependency(): void
    {
        $item = new CacheItem('key', 'value', time() + 3600, $this->dependency);
        $this->assertFalse($item->expired(1.0, $this->cache));

        TagDependency::invalidate($this->cache, 'tag');
        $this->assertTrue($item->expired(1.0, $this->cache));
    }

    public function probablyEarlyExpirationProvider(): array
    {
        return [
            '0.1-1-false' => [0.1, 1, false],
            '1.0-1-false' => [1.0, 1, false],
            '1.0-3600-false' => [1.0, 3600, false],
            '1000000.0-1-true' => [1000000.0, 1, true],
            '0.1--1-true' => [1.0, -1, true],
        ];
    }

    /**
     * @dataProvider probablyEarlyExpirationProvider
     *
     * @param float $beta
     * @param int $expiry
     * @param bool $expired
     */
    public function testExpiredWithProbablyEarlyExpiration(float $beta, int $expiry, bool $expired): void
    {
        $item = new CacheItem('key', 'value', (time() + $expiry), $this->dependency);

        if ($expired) {
            $this->assertTrue($item->expired($beta, $this->cache));
        } else {
            $this->assertFalse($item->expired($beta, $this->cache));
        }
    }

    public function testExpiredThrownExceptionForInvalidBeta(): void
    {
        $item = new CacheItem('key', 'value', null, null);
        $this->expectException(InvalidArgumentException::class);
        $item->expired(-0.1, $this->cache);
    }
}
