<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

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

    #[\PHPUnit\Framework\Attributes\DataProvider('ttlProvider')]
    public function testFactories(Ttl $ttl, int $expectedSeconds): void
    {
        $this->assertSame($expectedSeconds, $ttl->toSeconds());
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
