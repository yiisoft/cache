<?php

declare(strict_types=1);

namespace Yiisoft\Cache;

final class Ttl
{
    private const SECONDS_IN_MINUTE = 60;
    private const SECONDS_IN_HOUR = 3600;
    private const SECONDS_IN_DAY = 86400;

    private function __construct(public readonly int $value)
    {
    }

    public static function create(
        int $sec = 0,
        int $min = 0,
        int $hour = 0,
        int $day = 0,
    ): self {
        $totalSeconds = $sec
            + $min * self::SECONDS_IN_MINUTE
            + $hour * self::SECONDS_IN_HOUR
            + $day * self::SECONDS_IN_DAY;

        return new self($totalSeconds);
    }

    public static function seconds(int $sec): self
    {
        return new self($sec);
    }

    public static function minutes(int $min): self
    {
        return new self($min * self::SECONDS_IN_MINUTE);
    }

    public static function hours(int $hour): self
    {
        return new self($hour * self::SECONDS_IN_HOUR);
    }

    public static function days(int $day): self
    {
        return new self($day * self::SECONDS_IN_DAY);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function __invoke(): int
    {
        return $this->value;
    }
}
