<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Tests;

use Yiisoft\Cache\Ttl;

class TtlTest extends TestCase
{
    public function testSeconds(): void
    {
        $ttl = Ttl::seconds(10);
        $this->assertSame(10, $ttl());
    }

    public function testHours(): void
    {
        $ttl = Ttl::hours(2);
        $this->assertSame(7200, $ttl());
    }

    public function testCreate(): void
    {
        $ttl = Ttl::create(sec:10, min:5, hour:1, day:1);
        $expected = 10 + 5 * 60 + 3600 + 86400;
        $this->assertSame($expected, $ttl());
    }
}
