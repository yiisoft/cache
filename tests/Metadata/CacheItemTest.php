<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests\Metadata;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Cache\Exception\InvalidArgumentException;
use Yiisoft\Cache\Metadata\CacheItem;
use Yiisoft\Cache\Tests\TestCase;

use function time;

final class CacheItemTest extends TestCase
{
    private Cache $cache;
    private TagDependency $dependency;

    public function setUp(): void
    {
        $this->cache = new Cache(new ArrayCache());
        $this->dependency = new TagDependency('tag');
        $this->dependency->evaluateDependency($this->cache);
    }

    public function testGetters(): void
    {
        $item = new CacheItem(
            $key = 'key',
            $ttl = 3600,
            $this->dependency,
        );

        $this->assertSame($key, $item->key());
        $this->assertSame(time() + $ttl, $item->expiry());
        $this->assertSame($this->dependency, $item->dependency());
        $this->assertFalse($item->expired(1.0, $this->cache));
    }

    public function testExpiredThatNeverExpires(): void
    {
        $item = new CacheItem('key', null, null);
        $this->assertNull($item->expiry());
        $this->assertNull($item->dependency());
        $this->assertFalse($item->expired(1.0, $this->cache));
    }

    public function testExpiredAndUpdate(): void
    {
        $item = new CacheItem('key', time() + 3600, $this->dependency);
        $this->assertFalse($item->expired(1.0, $this->cache));

        $item->update(time(), $this->dependency);
        $this->assertTrue($item->expired(1.0, $this->cache));
    }

    public function testExpiredWithChangeDependency(): void
    {
        $item = new CacheItem('key', time() + 3600, $this->dependency);
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
     * @param int $ttl
     * @param bool $expired
     */
    public function testExpiredWithProbablyEarlyExpiration(float $beta, int $ttl, bool $expired): void
    {
        $item = new CacheItem('key', $ttl, $this->dependency);

        if ($expired) {
            $this->assertTrue($item->expired($beta, $this->cache));
        } else {
            $this->assertFalse($item->expired($beta, $this->cache));
        }
    }

    public function testExpiredThrownExceptionForInvalidBeta(): void
    {
        $item = new CacheItem('key', null, null);
        $this->expectException(InvalidArgumentException::class);
        $item->expired(-0.1, $this->cache);
    }
}
