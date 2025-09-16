<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use DateInterval;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Cache\Ttl;

final class TtlTest extends TestCase
{
    public function testFactoriesReturnTtlInstance(): void
    {
        $this->assertInstanceOf(Ttl::class, Ttl::seconds(10));
        $this->assertInstanceOf(Ttl::class, Ttl::minutes(5));
        $this->assertInstanceOf(Ttl::class, Ttl::hours(2));
        $this->assertInstanceOf(Ttl::class, Ttl::days(1));
        $this->assertInstanceOf(Ttl::class, Ttl::create(sec:1, min:1, hour:1, day:1));
    }

    #[DataProvider('ttlProvider')]
    public function testFactories(Ttl $ttl, int $expectedSeconds): void
    {
        $this->assertSame($expectedSeconds, $ttl->toSeconds());
    }

    public function testFromCoversOldNormalizeCases(): void
    {
        $this->assertSame(123, Ttl::from(123)?->toSeconds());
        $this->assertSame(123, Ttl::from('123')?->toSeconds());
        $this->assertNull(Ttl::from(null));
        $this->assertSame(0, Ttl::from(0)?->toSeconds());

        $interval1 = new DateInterval('PT6H8M');
        $this->assertSame(6 * 3600 + 8 * 60, Ttl::from($interval1)?->toSeconds());

        $interval2 = new DateInterval('P2Y4D');
        $this->assertSame(2 * 365 * 24 * 3600 + 4 * 24 * 3600, Ttl::from($interval2)?->toSeconds());
    }

    public function testInfinityReturnsNull(): void
    {
        $this->assertNull(Ttl::forever());
    }

    public static function ttlProvider(): array
    {
        return [
            'seconds' => [Ttl::seconds(10), 10],
            'minutes' => [Ttl::minutes(5), 5 * 60],
            'hours' => [Ttl::hours(2), 2 * 3600],
            'days' => [Ttl::days(1), 1 * 86400],
            'create' => [Ttl::create(sec:10, min:5, hour:1, day:1), 10 + 5 * 60 + 3600 + 86400],
            'zeroSeconds' => [Ttl::seconds(0), 0],
            'zeroCreate' => [Ttl::create(), 0],
        ];
    }
}
