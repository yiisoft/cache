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
        $this->assertInstanceOf(Ttl::class, Ttl::create(sec: 1, min: 1, hour: 1, day: 1));
        $this->assertInstanceOf(Ttl::class, Ttl::forever());
    }

    #[DataProvider('ttlProvider')]
    public function testFactories(Ttl $ttl, ?int $expectedSeconds, bool $expectedIsForever = false): void
    {
        $this->assertSame($expectedSeconds, $ttl->toSeconds());
        $this->assertSame($expectedIsForever, $ttl->isForever);
    }

    public static function ttlProvider(): array
    {
        return [
            'seconds' => [Ttl::seconds(10), 10, false],
            'minutes' => [Ttl::minutes(5), 5 * 60, false],
            'hours' => [Ttl::hours(2), 2 * 3600, false],
            'days' => [Ttl::days(1), 1 * 86400, false],
            'create' => [Ttl::create(sec: 10, min: 5, hour: 1, day: 1), 10 + 5 * 60 + 3600 + 86400, false],
            'zeroSeconds' => [Ttl::seconds(0), 0, false],
            'zeroCreate' => [Ttl::create(), 0, false],
            'forever' => [Ttl::forever(), null, true],
        ];
    }

    #[DataProvider('fromProvider')]
    public function testFrom(mixed $input, ?int $expectedSeconds, bool $expectedIsForever = false): void
    {
        $ttl = Ttl::from($input);
        $this->assertSame($expectedSeconds, $ttl->toSeconds());
        $this->assertSame($expectedIsForever, $ttl->isForever);
    }

    public static function fromProvider(): array
    {
        return [
            'int' => [123, 123, false],
            'string' => ['123', 123, false],
            'zero' => [0, 0, false],
            'zeroString' => ['0', 0, false],
            'null' => [null, null, true],
            'DateInterval_hours_minutes' => [new DateInterval('PT6H8M'), 6 * 3600 + 8 * 60, false],
            'DateInterval_years_days' => [new DateInterval('P2Y4D'), 2 * 365 * 24 * 3600 + 4 * 24 * 3600, false],
            'Ttl_instance' => [Ttl::seconds(500), 500, false],
            'nonNumericString' => ['abc', 0, false], // Converts to 0 as per current logic
        ];
    }

    public function testFromInvalidTypeThrowsException(): void
    {
        $this->expectException(TypeError::class);
        Ttl::from(1.5); // Float is invalid
    }

    public function testNegativeTtlThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TTL must be non-negative.');
        Ttl::seconds(-10);
    }

    public function testNegativeCreateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TTL must be non-negative.');
        Ttl::create(sec: -86400);
    }

    public function testNegativeDateIntervalThrowsException(): void
    {
        $interval = new DateInterval('PT1H');
        $interval->invert = 1;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DateInterval must result in non-negative TTL.');
        Ttl::from($interval);
    }

    public function testInfinityReturnsNull(): void
    {
        $ttl = Ttl::from(null);
        $this->assertTrue($ttl->isForever);
        $this->assertNull($ttl->toSeconds());
    }
}
