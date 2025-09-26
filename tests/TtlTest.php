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
            'nonNumericString' => ['abc', 0], // PHP (int)'abc' = 0
            'negativeString' => ['-10', -10],
            'negativeInt' => [-10, -10],
        ];
    }

    public function testFromInvalidTypeThrowsException(): void
    {
        $this->expectException(TypeError::class);
        Ttl::from(1.5); // Float is invalid
    }

    public function testFromIntervalWithInvert(): void
    {
        $interval = new DateInterval('PT1H');
        $interval->invert = 1;
        $ttl = Ttl::from($interval);

        $this->assertSame(-3600, $ttl->toSeconds());
        $this->assertTrue($ttl->isExpired());
    }

    public function testNegativeTtlBecomesZero(): void
    {
        $ttl = Ttl::seconds(-10);
        $this->assertSame(-10, $ttl->toSeconds());
        $this->assertFalse($ttl->isForever());
    }

    public function testIsForever(): void
    {
        $this->assertTrue(Ttl::forever()->isForever());
        $this->assertFalse(Ttl::seconds(1)->isForever());
    }

    public function testIsExpired(): void
    {
        $this->assertFalse(Ttl::seconds(10)->isExpired());
        $this->assertTrue(Ttl::seconds(0)->isExpired());
        $this->assertTrue(Ttl::seconds(-5)->isExpired());
        $this->assertFalse(Ttl::forever()->isExpired());
    }

    public function testStatus(): void
    {
        $this->assertSame(Ttl::EXPIRATION_LIVE, Ttl::seconds(10)->status());
        $this->assertSame(Ttl::EXPIRATION_EXPIRED, Ttl::seconds(0)->status());
        $this->assertSame(Ttl::EXPIRATION_EXPIRED, Ttl::seconds(-1)->status());
        $this->assertSame(Ttl::EXPIRATION_FOREVER, Ttl::forever()->status());
    }

    public function testToExpiration(): void
    {
        $now = time();
        $this->assertSame($now + 10, Ttl::seconds(10)->toExpiration($now));
        $this->assertSame(Ttl::EXPIRATION_EXPIRED, Ttl::seconds(0)->toExpiration($now));
        $this->assertSame(Ttl::EXPIRATION_FOREVER, Ttl::forever()->toExpiration($now));
    }

    public function testToSeconds(): void
    {
        $this->assertSame(123, Ttl::seconds(123)->toSeconds());
        $this->assertSame(0, Ttl::seconds(0)->toSeconds());
        $this->assertSame(-5, Ttl::seconds(-5)->toSeconds());
        $this->assertNull(Ttl::forever()->toSeconds());
    }

    public function testIsExpiredBehavior(): void
    {
        $this->assertFalse(Ttl::seconds(10)->isExpired());
        $this->assertTrue(Ttl::seconds(0)->isExpired());
        $this->assertTrue(Ttl::seconds(-5)->isExpired());
        $this->assertFalse(Ttl::forever()->isExpired());
    }

    public function testStatusMethod(): void
    {
        $this->assertSame(Ttl::EXPIRATION_LIVE, Ttl::seconds(10)->status());
        $this->assertSame(Ttl::EXPIRATION_EXPIRED, Ttl::seconds(0)->status());
        $this->assertSame(Ttl::EXPIRATION_EXPIRED, Ttl::seconds(-1)->status());
        $this->assertSame(Ttl::EXPIRATION_FOREVER, Ttl::forever()->status());
    }

    public function testZeroTtlMeansExpired(): void
    {
        $ttl = Ttl::seconds(0);
        $this->assertSame(0, $ttl->toSeconds());
        $this->assertFalse($ttl->isForever());
    }
}
