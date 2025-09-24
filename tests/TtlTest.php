<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use DateInterval;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;
use Yiisoft\Cache\Ttl;

final class TtlTest extends TestCase
{
    public function testFactoriesReturnTtlInstance(): void
    {
        $this->assertInstanceOf(Ttl::class, Ttl::seconds(10));
        $this->assertInstanceOf(Ttl::class, Ttl::minutes(5));
        $this->assertInstanceOf(Ttl::class, Ttl::hours(2));
        $this->assertInstanceOf(Ttl::class, Ttl::days(1));
        $this->assertInstanceOf(Ttl::class, Ttl::create(seconds: 1, minutes: 1, hours: 1, days: 1));
        $this->assertInstanceOf(Ttl::class, Ttl::forever());
    }

    #[DataProvider('ttlProvider')]
    public function testFactories(Ttl $ttl, ?int $expectedSeconds): void
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
            'create' => [Ttl::create(seconds: 10, minutes: 5, hours: 1, days: 1), 10 + 5 * 60 + 3600 + 86400],
            'zeroSeconds' => [Ttl::seconds(0), 0],
            'zeroCreate' => [Ttl::create(), 0],
            'forever' => [Ttl::forever(), null],
        ];
    }

    #[DataProvider('fromProvider')]
    public function testFrom(mixed $input, ?int $expectedSeconds): void
    {
        $ttl = Ttl::from($input);
        $this->assertSame($expectedSeconds, $ttl->toSeconds());
    }

    public static function fromProvider(): array
    {
        return [
            'int' => [123, 123],
            'string' => ['123', 123],
            'zero' => [0, 0],
            'zeroString' => ['0', 0],
            'null' => [null, null],
            'DateInterval_hours_minutes' => [new DateInterval('PT6H8M'), 6 * 3600 + 8 * 60],
            'DateInterval_years_days' => [new DateInterval('P2Y4D'), 2 * 365 * 24 * 3600 + 4 * 24 * 3600],
            'Ttl_instance' => [Ttl::seconds(500), 500],
            'nonNumericString' => ['abc', 0], // Converts to 0 as per current logic
        ];
    }

    public function testFromInvalidTypeThrowsException(): void
    {
        $this->expectException(TypeError::class);
        Ttl::from(1.5); // Float is invalid
    }

    public function testNegativeTtlBecomesZero(): void
    {
        $ttl = Ttl::seconds(-10);
        $this->assertSame(0, $ttl->toSeconds());
        $this->assertFalse($ttl->isForever());
    }

    public function testNegativeCreateBecomesZero(): void
    {
        $ttl = Ttl::create(seconds: -86400);
        $this->assertSame(0, $ttl->toSeconds());
        $this->assertFalse($ttl->isForever());
    }

    public function testNegativeDateIntervalBecomesZero(): void
    {
        $interval = new DateInterval('PT1H');
        $interval->invert = 1;

        $ttl = Ttl::from($interval);
        $this->assertSame(0, $ttl->toSeconds());
        $this->assertFalse($ttl->isForever());
    }

    public function testZeroTtlMeansExpired(): void
    {
        $ttl = Ttl::seconds(0);
        $this->assertSame(0, $ttl->toSeconds());
        $this->assertFalse($ttl->isForever());
    }
}
